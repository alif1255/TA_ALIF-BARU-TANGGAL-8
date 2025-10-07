<x-layout>
  <x-slot:title>Detail Purchase Order</x-slot:title>

  <div class="container">
    <h1 class="text-xl font-bold mb-4">Detail Purchase Order</h1>

    <div class="mb-4">
      <strong>Nomor PO:</strong> {{ $po->po_number }}<br>
      <strong>Supplier:</strong> {{ $po->supplier->name }}<br>
      <strong>Tanggal PO:</strong> {{ $po->po_date }}<br>
      <strong>Status:</strong>
      <span class="inline-block px-2 py-1 rounded text-white text-sm
        {{ $po->status === 'draft' ? 'bg-yellow-500' :
           ($po->status === 'validated' ? 'bg-blue-500' :
           ($po->status === 'received' ? 'bg-green-600' : 'bg-gray-500')) }}">
        {{ ucfirst($po->status) }}
      </span>
    </div>

    <table class="table-auto w-full border mb-6">
      <thead class="bg-gray-200">
        <tr>
          <th class="border px-2 py-1">Nama Produk</th>
          <th class="border px-2 py-1">Jumlah</th>
          <th class="border px-2 py-1">Harga Satuan</th>
          <th class="border px-2 py-1">Subtotal</th>
        </tr>
      </thead>
      <tbody>
        @php $total = 0; @endphp
        @foreach ($po->items as $item)
          @php $subtotal = $item->quantity * $item->unit_price; $total += $subtotal; @endphp
          <tr>
            <td class="border px-2 py-1">{{ $item->product_name }}</td>
            <td class="border px-2 py-1">{{ $item->quantity }}</td>
            <td class="border px-2 py-1">@indo_currency($item->unit_price)</td>
            <td class="border px-2 py-1">@indo_currency($subtotal)</td>
          </tr>
        @endforeach
      </tbody>
      <tfoot>
        <tr class="bg-gray-100 font-semibold">
          <td colspan="3" class="text-right px-2 py-1 border">Total</td>
          <td class="border px-2 py-1">@indo_currency($total)</td>
        </tr>
      </tfoot>
    </table>

    <div class="flex gap-4 mt-4">
      <a href="{{ route('purchase-orders.pdf', $po->id) }}"
         class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">
        Export PDF
      </a>

      @if($po->status !== 'received')
        <form method="POST" action="{{ route('purchase-orders.upload-invoice', $po->id) }}" enctype="multipart/form-data">
          @csrf
          <label class="block font-semibold mb-1">Unggah Invoice (Gambar)</label>
          <input type="file" name="invoice" required class="block mb-2 border px-2 py-1 rounded w-full">
          <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
            Upload Invoice & Tandai Diterima
          </button>
        </form>
      @endif
    </div>

    @if($po->invoice_image_path)
      <div class="mt-6">
        <p class="font-semibold">Invoice Telah Diupload:</p>
        <img src="{{ asset('storage/' . $po->invoice_image_path) }}" alt="Invoice" class="w-1/3 border mt-2 shadow">
      </div>
    @endif
  </div>
</x-layout>
