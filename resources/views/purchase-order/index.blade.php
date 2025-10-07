<x-layout>
  <x-slot:title>Daftar Purchase Order</x-slot:title>

  @if (session('success'))
    <x-alert type="success" :message="session('success')" />
  @endif

  <div class="flex justify-between items-center mb-4">
    <h1 class="text-xl font-bold">Daftar Purchase Order</h1>
    <a href="{{ route('purchase-orders.create') }}"
      class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow">
      Buat PO Baru
    </a>
  </div>

  <div class="overflow-x-auto">
    <table class="table-auto w-full">
      <thead>
        <tr>
          <th>Nomor PO</th>
          <th>Supplier</th>
          <th>Tanggal</th>
          <th>Status</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        @foreach($orders as $po)
        <tr>
          <td>{{ $po->po_number }}</td>
          <td>{{ $po->supplier->name }}</td>
          <td>{{ $po->po_date }}</td>
          <td>
            <span class="px-2 py-1 rounded text-white
              {{ $po->status == 'draft' ? 'bg-yellow-500' : ($po->status == 'validated' ? 'bg-blue-500' : 'bg-green-600') }}">
              {{ ucfirst($po->status) }}
            </span>
          </td>
          <td>
            <a href="{{ route('purchase-orders.show', $po->id) }}" class="text-blue-600">Lihat</a> |
            <a href="{{ route('purchase-orders.pdf', $po->id) }}" class="text-green-600">Cetak PDF</a>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</x-layout>
