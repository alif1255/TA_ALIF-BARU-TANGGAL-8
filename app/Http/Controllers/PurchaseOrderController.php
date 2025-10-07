<?php

namespace App\Http\Controllers;

use App\Models\{PurchaseOrder, PurchaseOrderItem, Supplier, SupplierProduct};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;


class PurchaseOrderController extends Controller
{
    // Display all Purchase Orders
    public function index()
    {
        $orders = PurchaseOrder::with('supplier')->latest()->get();
        return view('purchase-order.index', compact('orders'));
    }

    // Create new Purchase Order
    public function create()
    {
        $suppliers = Supplier::orderBy('name')->get();
        return view('purchase-order.form', compact('suppliers'));
    }

    // Store new Purchase Order
    public function store(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'po_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_name' => 'required|string',
            'items.*.qua    ntity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            $po = PurchaseOrder::create([
                'supplier_id' => $request->supplier_id,
                'po_date' => $request->po_date,
                'created_by' => auth()->id(),
                'po_number' => $this->generatePONumber(),
                'status' => 'draft',
            ]);

            foreach ($request->items as $item) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'product_name' => $item['product_name'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                ]);
            }

            DB::commit();
            return redirect()->route('purchase-orders.index')->with('success', 'PO berhasil disimpan');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Gagal menyimpan PO: ' . $e->getMessage()]);
        }
    }

    // Show PO details
    public function show($id)
    {
        $po = PurchaseOrder::with('items', 'supplier')->findOrFail($id);
        return view('purchase-order.show', compact('po'));
    }

    // Validate PO
    public function validatePO($id)
    {
        $po = PurchaseOrder::findOrFail($id);
        $po->update(['status' => 'validated']);
        return back()->with('success', 'PO berhasil divalidasi');
    }

    // Upload Invoice
    public function uploadInvoice(Request $request, $id)
{
    $request->validate([
        'invoice' => 'required|image|mimes:jpg,jpeg,png,pdf|max:2048'
    ]);

    $po = PurchaseOrder::findOrFail($id);

    // Hapus file lama jika ada
    if ($po->invoice_image_path && Storage::disk('public')->exists($po->invoice_image_path)) {
        Storage::disk('public')->delete($po->invoice_image_path);
    }

    $path = $request->file('invoice')->store('invoices', 'public');

    $po->update([
        'invoice_image_path' => $path,
        'status' => 'received'
    ]);

    return redirect()->route('purchase-orders.index')->with('success', 'Invoice berhasil diunggah dan status diperbarui.');
}

    // Export PDF
    public function exportPDF($id)
    {
    $po = PurchaseOrder::with('items', 'supplier')->findOrFail($id);
    $pdf = \PDF::loadView('purchase-order.pdf', compact('po'));

    $filename = str_replace(['/', '\\'], '-', $po->po_number);
    return $pdf->download("PO_{$filename}.pdf");
    }

    // Get items by supplier
    public function getItemsBySupplier($supplierId)
    {
        $supplier = Supplier::with('supplierProducts')->findOrFail($supplierId);

        $items = $supplier->supplierProducts->map(function ($product) {
            return [
                'id' => $product->id,
                'product_name' => $product->product_name,
            ];
        });

        return response()->json($items);
    }

    
    // Get supplier products for API
    private function generatePONumber()
    {
        $count = PurchaseOrder::count() + 1;
        $month = date('m');
        $year = date('Y');
        return sprintf("PO-%04d/%s/%s", $count, $month, $year);
    }
}
