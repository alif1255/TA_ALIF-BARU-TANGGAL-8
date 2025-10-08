<x-layout>
    <!--
      This view renders the form for creating a new Purchase Order (PO).
      To maintain visual consistency with other internal pages, the layout uses
      Bootstrap‑style cards and form groups rather than raw Tailwind classes.
      When a supplier is selected, a list of available products is fetched via
      the `/api/suppliers/{id}/items` endpoint. Users can add multiple items
      to the order and specify quantities — prices are not collected at this
      stage because they will be determined later by the supplier. Each item
      row includes a delete button so rows can be removed before submission.
    -->
    <x-slot:title>Buat Purchase Order</x-slot:title>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Buat Purchase Order</h5>
                    <!-- Link back to the PO index page -->
                    <a href="{{ route('purchase-orders.index') }}"
                       class="btn btn-warning btn-sm text-white">Kembali</a>
                </div>
                <div class="card-body">
                    <!-- Display validation errors -->
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('purchase-orders.store') }}">
                        @csrf
                        <div class="form-group mb-3">
                            <label for="supplier_id" class="form-label">Supplier <span class="text-danger">*</span></label>
                            <select name="supplier_id" id="supplier_id" class="form-select" required>
                                <option value="">Pilih Supplier</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group mb-3">
                            <label for="po_date" class="form-label">Tanggal PO <span class="text-danger">*</span></label>
                            <input type="date" name="po_date" id="po_date" class="form-control" required>
                        </div>

                        <!-- Container for dynamic item rows -->
                        <div id="items-container" class="mb-2">
                            <h6 class="fw-bold">Barang</h6>
                        </div>

                        <!-- Button to add new item row; hidden until a supplier is selected -->
                        <button type="button" id="addItemBtn" onclick="addItemRow()"
                                class="btn btn-secondary btn-sm d-none">Tambah Barang</button>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">Simpan PO</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Keep track of the current index for form array inputs
        let itemIndex = 0;
        // Will hold the list of products returned by the supplier API
        let itemsList = [];

        // When the supplier dropdown changes, fetch products for that supplier
        document.getElementById('supplier_id').addEventListener('change', function () {
            const supplierId = this.value;
            // Hide the 'add item' button and clear any existing item rows if no supplier is selected
            document.getElementById('addItemBtn').classList.add('d-none');
            document.getElementById('items-container').innerHTML = '<h6 class="fw-bold">Barang</h6>';
            itemIndex = 0;

            if (!supplierId) {
                return;
            }

            // Fetch products from the correct API endpoint
            fetch(`/api/suppliers/${supplierId}/items`)
                .then(response => response.json())
                .then(data => {
                    itemsList = data;
                    // Reveal the button to add items now that products are loaded
                    document.getElementById('addItemBtn').classList.remove('d-none');
                    // Add an initial item row automatically for convenience
                    addItemRow();
                })
                .catch(error => console.error('Gagal mengambil produk:', error));
        });

        /**
         * Create a new row for item inputs.
         * Setiap baris memungkinkan pengguna memilih produk dan memasukkan jumlah.
         * Harga satuan tidak diinput di sini karena akan ditentukan oleh supplier.
         * Setiap baris juga memiliki tombol hapus untuk menghapus baris jika diperlukan.
         */
        function addItemRow() {
            const container = document.getElementById('items-container');
            const row = document.createElement('div');
            row.classList.add('row', 'align-items-end', 'g-2', 'mb-2');

            // Build the product options for this supplier
            let options = '<option value="">Pilih Barang</option>';
            itemsList.forEach(item => {
                options += `<option value="${item.product_name}">${item.product_name}</option>`;
            });

            // Insert columns for product select and quantity. Harga tidak diinput pada tahap ini.
            row.innerHTML = `
                <div class="col-md-8">
                    <select name="items[${itemIndex}][product_name]" class="form-select" required>
                        ${options}
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="number" name="items[${itemIndex}][quantity]" class="form-control" placeholder="Qty" min="1" required>
                </div>
                <div class="col-md-1 text-end">
                    <button type="button" class="btn btn-danger btn-sm remove-item-row" title="Hapus">&times;</button>
                </div>
            `;

            // Attach an event listener to remove the row when the delete button is clicked
            row.querySelector('.remove-item-row').addEventListener('click', function () {
                row.remove();
            });

            container.appendChild(row);
            itemIndex++;
        }
    </script>
</x-layout>