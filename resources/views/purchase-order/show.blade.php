<x-layout>
    <!--
      View: Detail Purchase Order
      Tampilan ini menampilkan detail lengkap sebuah Purchase Order (PO) beserta
      daftar item yang dipesan.  Desain menggunakan card Bootstrap dengan
      badges untuk status, tombol tindakan (cetak PDF, validasi, unggah
      invoice) serta tabel responsif untuk item.  Format ini serasi dengan
      tampilan form dan daftar PO lainnya.
    -->
    <x-slot:title>Detail Purchase Order</x-slot:title>

    {{-- tampilkan pesan sukses atau error dari sesi --}}
    @if (session('success'))
        <x-alert type="success" :message="session('success')" />
    @endif
    @if (session('error'))
        <x-alert type="danger" :message="session('error')" />
    @endif

    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Detail Purchase Order</h5>
                    <a href="{{ route('purchase-orders.index') }}" class="btn btn-warning btn-sm text-white">Kembali</a>
                </div>
                <div class="card-body">
                    <!-- Informasi PO dalam tabel ringkasan -->
                    @php
                        $badgeClass = match($po->status) {
                            'draft'     => 'bg-warning text-dark',
                            'validated' => 'bg-primary',
                            'received'  => 'bg-success',
                            default     => 'bg-secondary'
                        };
                    @endphp
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered w-auto mb-0">
                            <tbody>
                                <tr>
                                    <th>No. PO</th>
                                    <td>{{ $po->po_number }}</td>
                                </tr>
                                <tr>
                                    <th>Supplier</th>
                                    <td>{{ $po->supplier->name }}</td>
                                </tr>
                                <tr>
                                    <th>Tanggal</th>
                                    <td>{{ \Carbon\Carbon::parse($po->po_date)->format('d M Y') }}</td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td><span class="badge {{ $badgeClass }}">{{ ucfirst($po->status) }}</span></td>
                                </tr>
                                @if ($po->invoice_image_path)
                                <tr>
                                    <th>Invoice</th>
                                    <td>
                                        @if (\Illuminate\Support\Str::endsWith($po->invoice_image_path, ['.jpg', '.jpeg', '.png']))
                                            <img src="{{ asset('storage/' . $po->invoice_image_path) }}" alt="Invoice" class="img-thumbnail" style="max-width:200px;">
                                        @else
                                            <a href="{{ asset('storage/' . $po->invoice_image_path) }}" target="_blank">Lihat Invoice</a>
                                        @endif
                                    </td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>

                    <!-- Tabel item PO -->
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Nama Barang</th>
                                    <th class="text-end">Qty</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($po->items as $item)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $item->product_name }}</td>
                                        <td class="text-end">{{ $item->quantity }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="2" class="text-end">Total Qty</th>
                                    <th class="text-end">
                                        {{ $po->items->sum('quantity') }}
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Tombol tindakan -->
                    <div class="mt-4 d-flex flex-column flex-md-row justify-content-md-end align-items-md-center gap-2">
                        <!-- Cetak PDF menggunakan helper action agar sesuai dengan rute controller -->
                        <a href="{{ action([\App\Http\Controllers\PurchaseOrderController::class, 'exportPDF'], $po->id) }}" class="btn btn-primary btn-sm">
                            Cetak PDF
                        </a>
                        @if ($po->status === 'draft')
                            <!-- Form untuk validasi PO -->
                            <form action="{{ action([\App\Http\Controllers\PurchaseOrderController::class, 'validatePO'], $po->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="btn btn-success btn-sm">Konfirmasi Supplier</button>
                            </form>
                        @elseif ($po->status === 'validated')
                            <!-- Form untuk upload invoice -->
                            <form action="{{ action([\App\Http\Controllers\PurchaseOrderController::class, 'uploadInvoice'], $po->id) }}" method="POST" enctype="multipart/form-data" class="d-flex align-items-center gap-2">
                                @csrf
                                <input type="file" name="invoice" accept="image/*,application/pdf" class="form-control form-control-sm" required>
                                <button type="submit" class="btn btn-secondary btn-sm">Unggah Invoice</button>
                            </form>
                        @elseif ($po->status === 'received')
                            <span class="badge bg-success">PO Selesai</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layout>