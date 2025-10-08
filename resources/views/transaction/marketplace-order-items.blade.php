<table class="table table-bordered mb-0">
    <thead>
        <tr>
            <th style="width: 10%">#</th>
            <th>Nama Barang</th>
            <th style="width: 15%">Harga</th>
            <th style="width: 10%">Qty</th>
            <th style="width: 15%">Subtotal</th>
        </tr>
    </thead>
    <tbody>
        @forelse($items as $item)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $item->name }}</td>
                <td>@indo_currency($item->price)</td>
                <td>{{ $item->qty }}</td>
                <td>@indo_currency($item->price * $item->qty)</td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="text-center text-muted">Tidak ada item pada pesanan ini.</td>
            </tr>
        @endforelse
    </tbody>
</table>
