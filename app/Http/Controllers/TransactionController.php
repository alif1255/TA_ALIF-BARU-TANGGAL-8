<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Customer;
use App\Models\Item;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TransactionController extends Controller
{
    /**
     * Pindahkan item dari Cart -> TransactionDetail + kurangi stok
     * (Dipakai ketika menyimpan transaksi, baik offline (paid) maupun online (debt))
     */
    private function move_cart($transaction)
    {
        $carts = Cart::where('user_id', Auth::user()->id)->get();

        foreach ($carts as $cart) {
            $item = Item::find($cart->item_id);
            $item->stock -= $cart->qty;
            $item->save();

            $transaction_detail = new TransactionDetail();
            $transaction_detail->transaction_id = $transaction->id;
            $transaction_detail->item_id = $cart->item_id;
            $transaction_detail->item_price = calculate_price($cart->item, $cart->qty);
            $transaction_detail->qty = $cart->qty;
            $transaction_detail->total = $cart->subtotal;
            $transaction_detail->save();
        }
    }

    /**
     * FINALIZE PEMBAYARAN (dipakai OFFLINE & ONLINE)
     * - Menetapkan payment_method_id, status, amount, change, note.
     * - Tidak mengubah user_id transaksi (online tetap customer).
     * - Untuk non-tunai: amount otomatis = total, change = 0.
     */
    protected function finalizePaymentCommon(
        Transaction $transaction,
        string $paymentMethodName,
        int $amount,
        int $change,
        ?string $note = null
    ): void {
        $payment = PaymentMethod::where('name', $paymentMethodName)->first();
        if (!$payment) {
            abort(422, 'Metode pembayaran tidak ditemukan.');
        }

        $total = (int) $transaction->total;
        $isCash = strtolower($paymentMethodName) === 'tunai';

        $transaction->payment_method_id = $payment->id;
        $transaction->status            = 'paid';
        $transaction->amount            = $isCash ? $amount : $total;
        $transaction->change            = $isCash ? max(0, $change) : 0;

        // Satukan note lama + baru + cap waktu + nama kasir
        $kasir = optional(Auth::user())->name;
        $stamp = now()->format('d/m/Y H:i');
        $append = trim(($note ?: '') . " (diproses: {$kasir}, {$stamp})");
        $transaction->note = trim(implode(' | ', array_filter([
            $transaction->note,
            $append,
        ])));

        $transaction->save();
    }

    /** POS (offline) index */
public function index(): View
{
    $user = User::find(Auth::user()->id);

    // Ambil daftar pesanan online (status debt oleh customer)
    $orders = Transaction::with('user')
        ->where('status', 'debt')
        ->whereHas('user', function($q) {
            $q->where('role', 'customer');
        })
        ->orderBy('created_at', 'asc')
        ->get();

    return view('transaction.index', [
        'user'            => $user,
        'customers'       => Customer::orderBy('name')->get(),
        'items'           => Item::orderBy('name')->get(),
        'payment_methods' => PaymentMethod::orderBy('name')->get(),
        'carts'           => $user->carts,
        'orders'          => $orders, // gunakan key 'orders' agar sesuai dengan view
    ]);
}



    /**
     * POS (offline) — Simpan & langsung BAYAR
     * Dulu kamu set field pembayaran langsung di sini. Sekarang:
     * - Buat transaksi (invoice, total, dll)
     * - Pindahkan cart -> detail
     * - FINALIZE pembayarannya via finalizePaymentCommon()
     */
    public function store(Request $request): string
    {
        // Validasi dasar dari POS
        $request->validate([
            'invoice'        => 'required|string',
            'invoice_no'     => 'required|numeric',
            'total'          => 'required|numeric|min:0',
            'discount'       => 'nullable|numeric|min:0',
            'payment_method' => 'required|string',     // nama metode (mis. "Tunai")
            'amount'         => 'nullable|numeric|min:0',
            'change'         => 'nullable|numeric|min:0',
            'note'           => 'nullable|string|max:255',
            'customer_id'    => 'nullable|numeric'
        ]);

        $transaction = new Transaction();
        $transaction->user_id    = Auth::user()->id;
        if ($request->customer_id && (int)$request->customer_id !== 0) {
            $transaction->customer_id = $request->customer_id;
        }
        $transaction->invoice    = $request->invoice;
        $transaction->invoice_no = $request->invoice_no;
        $transaction->total      = (int) $request->total;
        $transaction->discount   = (int) ($request->discount ?? 0);
        // status & pembayaran akan di-set oleh finalizePaymentCommon
        $transaction->note       = $request->note ?? null;
        $transaction->save();

        // Pindahkan cart -> detail + kurangi stok
        $this->move_cart($transaction);

        // VALIDASI & finalize pembayaran offline
        $method = $request->payment_method;
        $total  = (int) $transaction->total;
        $isCash = strtolower($method) === 'tunai';
        $amount = (int) ($request->amount ?? 0);
        $change = (int) ($request->change ?? 0);

        if ($isCash && $amount < $total) {
            return json_encode(['status' => 'error', 'message' => 'Jumlah uang tunai kurang dari total!']);
        }

        $this->finalizePaymentCommon($transaction, $method, $amount, $change, $request->note);

        // (Opsional) kosongkan cart kasir setelah selesai
        Cart::where('user_id', Auth::user()->id)->delete();

        return json_encode(['status' => 'success', 'message' => 'Transaksi berhasil']);
    }

    /** Hapus transaksi (dipakai di laporan) */
    public function destroy(Transaction $transaction): RedirectResponse
    {
        $transaction->delete();
        return redirect()->route('report.transaction.index')
            ->with('status', 'Berhasil menghapus data penjualan');
    }

    /**
     * Simpan transaksi sebagai HUTANG (debt) — ini dipakai juga untuk "pesanan online menunggu diambil"
     * Tidak set payment_method/amount/change di sini.
     */
    public function save_transaction(Request $request): string
    {
        $request->validate([
            'invoice'     => 'required|string',
            'invoice_no'  => 'required|numeric',
            'total'       => 'required|numeric|min:0',
            'customer_id' => 'nullable|numeric'
        ]);

        $transaction = new Transaction();
        $transaction->user_id    = Auth::user()->id; // di POS: user kasir; di online: user customer
        if ($request->customer_id && (int)$request->customer_id !== 0) {
            $transaction->customer_id = $request->customer_id;
        }
        $transaction->invoice    = $request->invoice;
        $transaction->invoice_no = $request->invoice_no;
        $transaction->total      = (int) $request->total;
        $transaction->status     = 'debt'; // menunggu diambil / belum dibayar
        $transaction->save();

        $this->move_cart($transaction);

        return json_encode(['status' => 'success', 'message' => 'Transaksi berhasil']);
    }

    /** Ambil daftar item (POS) */
    public function get_items(Request $request): string|View
    {
        $items = Item::orderBy('name')->get();

        if ($request->json) {
            return json_encode($items);
        }

        return view('transaction.items', [
            'items' => $items
        ]);
    }

    /** Generate invoice & invoice_no harian */
    public function get_invoice(): string
    {
        if (Transaction::whereDate('created_at', Carbon::today())->exists()) {
            $invoice = intval(Transaction::whereDate('created_at', Carbon::today())->max('invoice_no')) + 1;
        } else {
            $invoice = 1;
        }

        $invoice_no = $invoice;
        $invoice    = env('INVOICE_PREFIX') . date('dmy') . str_pad($invoice, 4, "0", STR_PAD_LEFT);

        return json_encode(['invoice' => $invoice, 'invoice_no' => $invoice_no]);
    }

    /**
     * Halaman daftar pesanan online (status 'debt' & dibuat oleh customer)
     * HANYA daftar, tidak menampilkan item/cart — proses melalui modal kecil.
     */
    public function onlineOrders()
    {
        $orders = Transaction::with(['user'])
            ->where('status', 'debt')
            ->whereHas('user', function ($q) {
                $q->where('role', 'customer');
            })
            ->orderBy('created_at', 'asc')
            ->get();

        $payment_methods = PaymentMethod::orderBy('name')->get();

        return view('transaction.online', compact('orders', 'payment_methods'));
    }


     public function processOnline(Request $request, Transaction $transaction)
    {
        if ($transaction->status !== 'debt' || optional($transaction->user)->role !== 'customer') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Pesanan tidak valid atau sudah diproses.'
            ], 422);
        }

        $validated = $request->validate([
            'payment_method' => 'required|string',
            'amount'         => 'nullable|numeric|min:0', // khusus tunai
            'change'         => 'nullable|numeric|min:0',
            'note'           => 'nullable|string|max:255',
        ]);

        $total  = (int) $transaction->total;
        $method = $validated['payment_method'];
        $isCash = strtolower($method) === 'tunai';
        $amount = (int) ($validated['amount'] ?? 0);
        $change = (int) ($validated['change'] ?? 0);

        // Validasi untuk pembayaran tunai: jumlah uang harus cukup
        if ($isCash && $amount < $total) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Jumlah uang tunai kurang dari total pembayaran.'
            ], 422);
        }

        // Gunakan logika pembayaran yang sama dengan offline
        $this->finalizePaymentCommon($transaction, $method, $amount, $change, $validated['note'] ?? null);

        // Kosongkan keranjang customer terkait (jika masih ada sisa)
        Cart::where('user_id', $transaction->user_id)->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Transaksi online berhasil diproses.'
        ]);
    }
}
