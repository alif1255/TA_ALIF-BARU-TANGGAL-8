{{--
  Improved version of resources/views/transaction/online.blade.php
  for marketplace orders. This template displays marketplace orders
  waiting to be picked up (status pending_pickup) and provides
  a modal to process them at the cashier.
--}}

<x-layout>
    <x-slot:title>Pesanan Marketplace (Menunggu Diambil)</x-slot:title>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Daftar Pesanan Marketplace</h5>
            <a href="{{ route('transaction.index') }}" class="btn btn-outline-secondary btn-sm">← POS / Transaksi</a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped" id="marketplace-orders-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Kode Pesanan</th>
                            <th>Nama Pengambil</th>
                            <th>No. HP</th>
                            <th>Total</th>
                            <th>Tanggal</th>
                            <th style="width:160px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $order->code }}</td>
                                <td>{{ $order->pickup_name ?? $order->customer_name }}</td>
                                <td>{{ $order->phone }}</td>
                                <td>@indo_currency($order->total_price)</td>
                                <td>{{ \Carbon\Carbon::parse($order->created_at)->format('d-m-Y H:i') }}</td>
                                <td>
                                    {{-- Tombol detail opsional (menampilkan item pesanan) --}}
                                    <button class="btn btn-sm btn-info" data-id="{{ $order->id }}" onclick="showOrderDetail(this)">
                                        <i class="fas fa-list"></i> Detail
                                    </button>

                                    {{-- Tombol proses pembayaran/pengambilan --}}
                                    <button class="btn btn-sm btn-success text-white"
                                            data-id="{{ $order->id }}"
                                            data-code="{{ $order->code }}"
                                            data-total="{{ (int) $order->total_price }}"
                                            onclick="openMarketplaceProcess(this)">
                                        <i class="fas fa-check"></i> Proses
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">Belum ada pesanan marketplace yang menunggu.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Modal proses pesanan marketplace --}}
    <div class="modal fade" id="process_marketplace_modal" tabindex="-1" aria-labelledby="processMarketplaceLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="processMarketplaceLabel">Proses Pengambilan Pesanan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <form id="marketplace_process_form" onsubmit="return false;">
                        @csrf
                        <input type="hidden" id="mp_order_id">
                        <div class="mb-2">
                            <label class="form-label">Kode Pesanan</label>
                            <input type="text" class="form-control" id="mp_code" readonly>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Total</label>
                            <input type="text" class="form-control" id="mp_total_fmt" readonly>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Metode Pembayaran</label>
                            <select id="mp_metode" class="form-select">
                                @foreach($payment_methods as $pm)
                                    <option value="{{ $pm->name }}">{{ $pm->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2" id="mp_cash_wrap">
                            <label class="form-label">Uang Diterima (Tunai)</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="mp_amount" placeholder="Masukkan uang tunai">
                                <button class="btn btn-outline-secondary" type="button" id="mp_btn_exact">Uang Pas</button>
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Kembalian</label>
                            <input type="text" class="form-control" id="mp_change" readonly>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Catatan (opsional)</label>
                            <textarea id="mp_note" class="form-control" placeholder="Catatan pesanan"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-primary" id="mp_btn_process_confirm">Selesaikan</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            // Fallback currency formatter using Intl API
            function mpFormatCurrency(num) {
                try {
                    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(num);
                } catch (e) {
                    return num;
                }
            }

            $(function() {
                // Initialize DataTable for marketplace orders
                const mpTable = $('#marketplace-orders-table').DataTable({
                    pageLength: 10,
                    order: [[5, 'asc']],
                    language: typeof datatableLanguageOptions !== 'undefined' ? datatableLanguageOptions : undefined,
                    columnDefs: [
                        { targets: [6], orderable: false, searchable: false }
                    ]
                });
                window.tableMarketplaceOrders = mpTable;
            });

            // Show details of marketplace order (optional implementation)
            function showOrderDetail(btn) {
                const id = btn.dataset.id;
                // Anda bisa membuat endpoint untuk mengembalikan detail
                $.get(`/marketplace/order/${id}`, function(html) {
                    $('#transaction_detail_modal .modal-body').html(html);
                    $('#transaction_detail_modal').modal('show');
                }).fail(function() {
                    toastr.error('Gagal memuat detail pesanan.');
                });
            }

            // Open modal and prefill for marketplace order processing
            function openMarketplaceProcess(btn) {
                const id    = btn.dataset.id;
                const code  = btn.dataset.code;
                const total = parseInt(btn.dataset.total || 0);

                $('#mp_order_id').val(id);
                $('#mp_code').val(code);
                if (typeof indo_currency === 'function') {
                    $('#mp_total_fmt').val(indo_currency(total, true));
                } else {
                    $('#mp_total_fmt').val(mpFormatCurrency(total));
                }
                // reset payment fields
                $('#mp_metode').val($('#mp_metode option:first').val()).trigger('change');
                $('#mp_amount').val('');
                $('#mp_change').val('');
                $('#mp_note').val('');
                $('#mp_btn_process_confirm').prop('disabled', false);
                $('#process_marketplace_modal').modal('show');
            }

            // Toggle cash fields based on selected payment method
            $('#mp_metode').on('change', function() {
                const isCash = $(this).val().toLowerCase() === 'tunai';
                if (isCash) {
                    $('#mp_cash_wrap').show();
                    $('#mp_amount').val('');
                    $('#mp_change').val('');
                } else {
                    $('#mp_cash_wrap').hide();
                    const total = parseInt(($('#mp_total_fmt').val() || '').replace(/\D/g, '')) || 0;
                    $('#mp_amount').val(total);
                    $('#mp_change').val(0);
                }
            });

            // Calculate change for cash payments
            $('#mp_amount').on('input', function() {
                const paid  = parseInt(($(this).val() || '').replace(/\D/g, '')) || 0;
                const total = parseInt(($('#mp_total_fmt').val() || '').replace(/\D/g, '')) || 0;
                const chg   = paid - total;
                if (typeof indo_currency === 'function') {
                    $('#mp_change').val(chg >= 0 ? indo_currency(chg, true) : '');
                } else {
                    $('#mp_change').val(chg >= 0 ? mpFormatCurrency(chg) : '');
                }
            });

            // Set cash amount equal to total
            $('#mp_btn_exact').on('click', function() {
                const total = parseInt(($('#mp_total_fmt').val() || '').replace(/\D/g, '')) || 0;
                if (typeof indo_currency === 'function') {
                    $('#mp_amount').val(indo_currency(total, true));
                } else {
                    $('#mp_amount').val(mpFormatCurrency(total));
                }
                $('#mp_change').val(0);
            });

            // Submit marketplace order processing
            $('#mp_btn_process_confirm').on('click', function() {
                const $btn  = $(this);
                $btn.prop('disabled', true);
                const id    = $('#mp_order_id').val();
                const method= $('#mp_metode').val();
                const amountN= parseInt(($('#mp_amount').val() || '').replace(/\D/g, '')) || 0;
                const totalN = parseInt(($('#mp_total_fmt').val() || '').replace(/\D/g, '')) || 0;

                // Validate cash
                if (method.toLowerCase() === 'tunai' && amountN < totalN) {
                    toastr.warning('Uang tunai kurang dari total!');
                    $btn.prop('disabled', false);
                    return;
                }

                const changeN = amountN - totalN;
                const note    = $('#mp_note').val();

                $.ajax({
                    url: `/transaction/online-orders/${id}/process`,
                    type: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    data: { payment_method: method, amount: amountN, change: changeN, note: note },
                    success: function(res) {
                        if (res.status === 'success') {
                            toastr.success(res.message || 'Berhasil diproses.');
                            $('#process_marketplace_modal').modal('hide');
                            // Remove row from DataTable
                            const row = $('#marketplace-orders-table button[data-id="' + id + '"]').closest('tr');
                            if (window.tableMarketplaceOrders) {
                                window.tableMarketplaceOrders.row(row).remove().draw();
                            } else {
                                row.remove();
                            }
                        } else {
                            toastr.error(res.message || 'Gagal memproses.');
                        }
                    },
                    error: function(err) {
                        console.error(err);
                        toastr.error('Gagal memproses.');
                    },
                    complete: function() {
                        $btn.prop('disabled', false);
                    }
                });
            });
        </script>
    @endpush
</x-layout>
