<x-layout>
    <!--
      View: Daftar Purchase Order
      Tampilan ini menggunakan card dan table Bootstrap agar serasi dengan modul lainnya.
      Tombol "Buat PO Baru" ditempatkan di header kartu dan daftar PO ditampilkan
      dalam tabel responsif.
    -->
    <x-slot:title>Daftar Purchase Order</x-slot:title>

    @if (session('success'))
        <x-alert type="success" :message="session('success')" />
    @endif

    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Daftar Purchase Order</h5>
                    <a href="{{ route('purchase-orders.create') }}" class="btn btn-primary btn-sm">
                        Buat PO Baru
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nomor PO</th>
                                    <th>Supplier</th>
                                    <th>Tanggal</th>
                                    <th>Status</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($orders as $po)
                                    <tr>
                                        <td>{{ $po->po_number }}</td>
                                        <td>{{ $po->supplier->name }}</td>
                                        <td>{{ $po->po_date }}</td>
                                        <td>
                                            @php
                                                $badgeClass = match($po->status) {
                                                    'draft' => 'bg-warning text-dark',
                                                    'validated' => 'bg-primary',
                                                    'received' => 'bg-success',
                                                    default => 'bg-secondary'
                                                };
                                            @endphp
                                            <span class="badge {{ $badgeClass }}">{{ ucfirst($po->status) }}</span>
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('purchase-orders.show', $po->id) }}" class="btn btn-info btn-sm me-1">Lihat</a>
<a href="{{ action([\App\Http\Controllers\PurchaseOrderController::class, 'exportPDF'], $po->id) }}" class="btn btn-success btn-sm">Cetak PDF</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layout>