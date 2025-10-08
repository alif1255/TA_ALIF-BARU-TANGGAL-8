<?php

namespace App\Http\Controllers;

use App\Models\{PurchaseOrder, PurchaseOrderItem, Supplier, SupplierProduct};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Controller for managing Purchase Orders (PO).
 *
 * This version includes a fix to the validation rules for item quantities.  The
 * original code contained a typo in the array key ("qua    ntity") which
 * caused Laravel's validator to look for a non‑existent field, producing
 * errors like "The items.0.qua ntity field is required.".  The corrected
 * version uses "quantity" as the key.  Additionally, this file can be
 * extended for further customizations.
 */
class PurchaseOrderController extends Controller
{
    // Display all Purchase Orders
    public function index()
    {
        $orders = PurchaseOrder::with('supplier')->latest()->get();
        return view('purchase-order.index', compact('orders'));
    }

    // Show form to create a new Purchase Order
    public function create()
    {
        $suppliers = Supplier::orderBy('name')->get();
        return view('purchase-order.form', compact('suppliers'));
    }

    // Store a new Purchase Order
    public function store(Request $request)
    {
        // Corrected validation: use "quantity" instead of the mis‑spelled key
        $request->validate([
            'supplier_id'          => 'required|exists:suppliers,id',
            'po_date'              => 'required|date',
            'items'                => 'required|array|min:1',
            'items.*.product_name' => 'required|string',
            'items.*.quantity'     => 'required|integer|min:1',
            // Unit price is not required when creating PO; it will be determined later.
        ]);

        DB::beginTransaction();
        
        try {
            $po = PurchaseOrder::create([
                'supplier_id' => $request->supplier_id,
                'po_date'     => $request->po_date,
                'created_by'  => auth()->id(),
                'po_number'   => $this->generatePONumber(),
                'status'      => 'draft',
            ]);

            foreach ($request->items as $item) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'product_name'      => $item['product_name'],
                    'quantity'          => $item['quantity'],
                    // Save unit_price as zero (or null) because price will be set by supplier later
                    'unit_price'        => 0,
                ]);
            }

            DB::commit();
            return redirect()->route('purchase-orders.index')->with('success', 'PO berhasil disimpan');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Gagal menyimpan PO: ' . $e->getMessage()]);
        }
    }

    // Display details for a given Purchase Order
    public function show($id)
    {
        $po = PurchaseOrder::with('items', 'supplier')->findOrFail($id);
        return view('purchase-order.show', compact('po'));
    }

    // Validate a Purchase Order (change status to validated)
    public function validatePO($id)
    {
        $po = PurchaseOrder::findOrFail($id);
        $po->update(['status' => 'validated']);
        return back()->with('success', 'PO berhasil divalidasi');
    }

    // Upload an invoice image and mark PO as received
    public function uploadInvoice(Request $request, $id)
    {
        // Allow PDF and image uploads.  Use the "file" validation rule instead of
        // "image" so that PDF files are accepted.  Limit file size to 2MB.
        $request->validate([
            'invoice' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048'
        ]);

        $po = PurchaseOrder::findOrFail($id);
        if ($po->invoice_image_path && Storage::disk('public')->exists($po->invoice_image_path)) {
            Storage::disk('public')->delete($po->invoice_image_path);
        }
        $path = $request->file('invoice')->store('invoices', 'public');
        $po->update([
            'invoice_image_path' => $path,
            'status'             => 'received'
        ]);
        return redirect()->route('purchase-orders.index')->with('success', 'Invoice berhasil diunggah dan status diperbarui.');
    }

    // Export PO as PDF
    public function exportPDF($id)
    {
        $po = PurchaseOrder::with('items', 'supplier')->findOrFail($id);
        $pdf = Pdf::loadView('purchase-order.pdf', compact('po'));
        $filename = str_replace(['/', '\\'], '-', $po->po_number);
        return $pdf->download("PO_{$filename}.pdf");
    }

    // API endpoint: get items for a supplier
    public function getItemsBySupplier($supplierId)
    {
        $supplier = Supplier::with('supplierProducts')->findOrFail($supplierId);
        $items = $supplier->supplierProducts->map(function ($product) {
            return [
                'id'           => $product->id,
                'product_name' => $product->product_name,
            ];
        });
        return response()->json($items);
    }

    // Helper: generate sequential PO numbers (format: PO-0001/MM/YYYY)
    private function generatePONumber()
    {
        $count = PurchaseOrder::count() + 1;
        $month = date('m');
        $year  = date('Y');
        return sprintf("PO-%04d/%s/%s", $count, $month, $year);
    }
}