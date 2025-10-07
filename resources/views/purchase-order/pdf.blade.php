<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Purchase Order - {{ $po->po_number }}</title>
  <style>
    body { font-family: sans-serif; font-size: 12px; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { border: 1px solid #000; padding: 5px; }
    th { background-color: #f0f0f0; }
    h2 { margin: 0; }
  </style>
</head>
<body>

  <h2>Purchase Order</h2>
  <p><strong>Nomor PO:</strong> {{ $po->po_number }}</p>
  <p><strong>Supplier:</strong> {{ $po->supplier->name }}</p>
  <p><strong>Tanggal:</strong> {{ $po->po_date }}</p>

  <table>
    <thead>
      <tr>
        <th>No</th>
        <th>Nama Produk</th>
        <th>Jumlah</th>
        <th>Harga Satuan</th>
        <th>Subtotal</th>
      </tr>
    </thead>
    <tbody>
      @php $total = 0; @endphp
      @foreach ($po->items as $index => $item)
        @php $subtotal = $item->quantity * $item->unit_price; $total += $subtotal; @endphp
        <tr>
          <td>{{ $index + 1 }}</td>
          <td>{{ $item->product_name }}</td>
          <td>{{ $item->quantity }}</td>
          <td>@indo_currency($item->unit_price)</td>
          <td>@indo_currency($subtotal)</td>
        </tr>
      @endforeach
    </tbody>
    <tfoot>
      <tr>
        <td colspan="4" style="text-align: right;"><strong>Total</strong></td>
        <td><strong>@indo_currency($total)</strong></td>
      </tr>
    </tfoot>
  </table>

  <p style="margin-top: 40px;">Dicetak pada: {{ now()->format('d M Y H:i') }}</p>

</body>
</html>
