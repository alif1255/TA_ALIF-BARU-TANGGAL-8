{{-- resources/views/transaction/online.blade.php --}}
<x-layout>
  <x-slot:title>Pesanan Online (Menunggu Diambil)</x-slot:title>

  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">Daftar Pesanan Online</h5>
      <a href="{{ route('transaction.index') }}" class="btn btn-outline-secondary btn-sm">← POS / Transaksi</a>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table" id="online-orders-table">
          <thead>
            <tr>
              <th>No</th>
              <th>Faktur</th>
              <th>Pelanggan</th>
              <th>Total</th>
              <th>Tanggal</th>
              <th>Status</th>
              <th style="width:160px">Aksi</th>
            </tr>
          </thead>
          <tbody>
            @foreach($orders as $order)
              <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $order->invoice }}</td>
                <td>{{ optional($order->user)->name }}</td>
                <td>@indo_currency($order->total)</td>
                <td>{{ $order->created_at->format('d-m-Y H:i') }}</td>
                <td><span class="badge bg-warning text-dark">Menunggu Diambil</span></td>
                <td>
                  {{-- Tombol detail transaksi opsional --}}
                  <button class="btn btn-sm btn-info" data-id="{{ $order->id }}" onclick="showDetail(this)">
                    <i class="fas fa-list"></i> Detail
                  </button>

                  {{-- Tombol proses pembayaran --}}
                  <button class="btn btn-sm btn-success text-white"
                          data-id="{{ $order->id }}"
                          data-invoice="{{ $order->invoice }}"
                          data-total="{{ (int) $order->total }}"
                          onclick="openProcess(this)">
                    <i class="fas fa-check"></i> Proses
                  </button>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>

  {{-- Modal proses pembayaran --}}
  <div class="modal fade" id="process_modal" tabindex="-1" aria-labelledby="processLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="processLabel">Proses Pembayaran</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
        </div>
        <div class="modal-body">
          <form id="process_form" onsubmit="return false;">
            @csrf
            <input type="hidden" id="order_id">
            <div class="mb-2">
              <label class="form-label">Faktur</label>
              <input type="text" class="form-control" id="faktur" readonly>
            </div>
            <div class="mb-2">
              <label class="form-label">Total</label>
              <input type="text" class="form-control" id="total_fmt" readonly>
            </div>
            <div class="mb-2">
              <label class="form-label">Metode Pembayaran</label>
              <select id="metode" class="form-select">
                @foreach($payment_methods as $pm)
                  <option value="{{ $pm->name }}">{{ $pm->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="mb-2" id="cash_wrap">
              <label class="form-label">Uang Diterima (Tunai)</label>
              <div class="input-group">
                <input type="text" class="form-control" id="amount" placeholder="Masukkan uang tunai">
                <button class="btn btn-outline-secondary" type="button" id="btn_exact">Uang Pas</button>
              </div>
            </div>
            <div class="mb-2">
              <label class="form-label">Kembalian</label>
              <input type="text" class="form-control" id="change" readonly>
            </div>
            <div class="mb-0">
              <label class="form-label">Catatan (opsional)</label>
              <textarea id="note" class="form-control" placeholder="Pesanan online diambil"></textarea>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button class="btn btn-primary" id="btn_process_confirm">Selesaikan</button>
        </div>
      </div>
    </div>
  </div>

  @push('scripts')
  <script>
    // Inisialisasi DataTable
    $(function(){
      if ($.fn.DataTable) {
        $('#online-orders-table').DataTable({ pageLength: 10, order: [[4,'asc']] });
      }
    });

    // Tampilkan modal detail transaksi (opsional)
    function showDetail(btn) {
      const id = btn.dataset.id;
      $.get(`/report/transaction/${id}`, function(html) {
        $('#transaction_detail_modal .modal-body').html(html);
        $('#transaction_detail_modal').modal('show');
      }).fail(function(){
        toastr.error('Gagal memuat detail.');
      });
    }

    // Buka modal proses pembayaran dan isi data pesanan
    function openProcess(btn) {
      const id      = btn.dataset.id;
      const invoice = btn.dataset.invoice;
      const total   = parseInt(btn.dataset.total || 0);

      $('#order_id').val(id);
      $('#faktur').val(invoice);

      if (typeof indo_currency === 'function') {
        $('#total_fmt').val(indo_currency(total, true));
      } else {
        $('#total_fmt').val(total);
      }

      $('#metode').val('Tunai').trigger('change');
      $('#amount').val('');
      $('#change').val('');
      $('#note').val('');
      $('#process_modal').modal('show');
    }

    // Tampilkan/ sembunyikan input uang tunai berdasarkan metode
    $('#metode').on('change', function() {
      const isCash = $(this).val() === 'Tunai';
      if (isCash) {
        $('#cash_wrap').show();
        $('#amount').val('');
        $('#change').val('');
      } else {
        $('#cash_wrap').hide();
        const total = parseInt(($('#total_fmt').val() || '').replace(/\D/g,'')) || 0;
        $('#amount').val(total);
        $('#change').val(0);
      }
    });

    // Hitung kembalian real-time
    $('#amount').on('input', function() {
      const paid  = parseInt(($(this).val() || '').replace(/\D/g,'')) || 0;
      const total = parseInt(($('#total_fmt').val() || '').replace(/\D/g,'')) || 0;
      const chg   = paid - total;
      if (typeof indo_currency === 'function') {
        $('#change').val(chg >= 0 ? indo_currency(chg, true) : '');
      } else {
        $('#change').val(chg >= 0 ? chg : '');
      }
    });

    // Isi uang pas sesuai total
    $('#btn_exact').on('click', function() {
      const total = parseInt(($('#total_fmt').val() || '').replace(/\D/g,'')) || 0;
      if (typeof indo_currency === 'function') {
        $('#amount').val(indo_currency(total, true));
      } else {
        $('#amount').val(total);
      }
      $('#change').val(0);
    });

    // Kirim permintaan proses pembayaran
    $('#btn_process_confirm').on('click', function() {
      const id      = $('#order_id').val();
      const method  = $('#metode').val();
      const amountN = parseInt(($('#amount').val() || '').replace(/\D/g,'')) || 0;
      const totalN  = parseInt(($('#total_fmt').val()  || '').replace(/\D/g,'')) || 0;

      if (method === 'Tunai' && amountN < totalN) {
        return toastr.warning('Uang tunai kurang dari total!');
      }

      const changeN = amountN - totalN;
      const note    = $('#note').val();

      $.ajax({
        url: `/transaction/online-orders/${id}/process`,
        type: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        data: { payment_method: method, amount: amountN, change: changeN, note },
        success: function(res) {
          if (res.status === 'success') {
            toastr.success(res.message || 'Berhasil diproses.');
            $('#process_modal').modal('hide');
            // Hapus baris dari tabel
            $(`#online-orders-table button[data-id="${id}"]`).closest('tr').remove();
          } else {
            toastr.error(res.message || 'Gagal memproses.');
          }
        },
        error: function(err) {
          console.error(err);
          toastr.error('Gagal memproses.');
        }
      });
    });
  </script>
  @endpush
</x-layout>
