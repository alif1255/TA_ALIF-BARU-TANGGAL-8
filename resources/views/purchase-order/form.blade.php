<x-layout>
  <x-slot:title>Buat Purchase Order</x-slot:title>

  <div class="container">
    <h1 class="text-xl font-bold mb-4">Buat Purchase Order</h1>

    @if ($errors->any())
  <div class="bg-red-100 text-red-700 p-4 rounded mb-4">
    <ul class="list-disc pl-5">
      @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif
    <form method="POST" action="{{ route('purchase-orders.store') }}">
      @csrf

      <div class="mb-4">
        <label for="supplier_id" class="block font-semibold">Supplier</label>
        <select name="supplier_id" id="supplier_id" required class="w-full border px-2 py-1 rounded">
          <option value="">-- Pilih Supplier --</option>
          @foreach($suppliers as $supplier)
            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
          @endforeach
        </select>
      </div>

      <div class="mb-4">
        <label for="po_date" class="block font-semibold">Tanggal PO</label>
        <input type="date" name="po_date" required class="w-full border px-2 py-1 rounded">
      </div>

      <div id="items-container">
        <h3 class="font-bold mt-4">Barang</h3>
      </div>

      <button type="button" onclick="addItemRow()" id="addItemBtn"
        class="bg-gray-600 text-white px-3 py-1 rounded mt-2 hidden">Tambah Barang</button>

      <br><br>
      <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Simpan PO</button>
    </form>
  </div>

  <script>
    let itemIndex = 0;
    let itemsList = [];

    document.getElementById('supplier_id').addEventListener('change', function () {
      const supplierId = this.value;
      if (!supplierId) return;

      fetch(`/supplier/${supplierId}/products`)
        .then(response => response.json())
        .then(data => {
          itemsList = data;
          itemIndex = 0;
          document.getElementById('items-container').innerHTML = '<h3 class="font-bold mt-4">Barang</h3>';
          document.getElementById('addItemBtn').classList.remove('hidden');
          addItemRow();
        })
        .catch(error => console.error('Gagal mengambil produk:', error));
    });

    function addItemRow() {
      const container = document.getElementById('items-container');
      const row = document.createElement('div');
      row.classList.add('item-row', 'mb-3', 'p-2', 'border', 'rounded', 'bg-gray-50');

      let options = '<option value="">-- Pilih Barang --</option>';
      itemsList.forEach(item => {
        options += `<option value="${item.product_name}">${item.product_name}</option>`;
      });

      row.innerHTML = `
        <select name="items[${itemIndex}][product_name]" class="w-full border px-2 py-1 mb-2 rounded" required>
            ${itemsList.map(p => `<option value="${p.product_name}">${p.product_name}</option>`).join('')}
        </select>
        <input type="number" name="items[${itemIndex}][quantity]" placeholder="Qty" class="w-full border px-2 py-1 mb-2 rounded" required>
        <input type="number" name="items[${itemIndex}][unit_price]" placeholder="Harga Satuan" class="w-full border px-2 py-1 rounded" required>
      `;

      container.appendChild(row);
      itemIndex++;
    }
  </script>
</x-layout>
