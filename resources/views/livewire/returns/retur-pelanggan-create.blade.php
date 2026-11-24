<div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0">Catat Retur dari Pelanggan</h4>
            <p class="text-muted small mb-0">Terima pengembalian barang dari pembeli dan tambahkan ke stok gudang</p>
        </div>
        <a href="{{ route('retur-pelanggan.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>

    @error('general')
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-1"></i> {{ $message }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @enderror

    <form wire:submit="save">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold mb-0 text-primary"><i class="bi bi-info-circle me-1"></i> Formulir Retur Pelanggan</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Tanggal Retur <span class="text-danger">*</span></label>
                        <input type="date" wire:model="tanggal" class="form-control @error('tanggal') is-invalid @enderror">
                        @error('tanggal') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Pelanggan <span class="text-danger">*</span></label>
                        <select wire:model.live="pelanggan_id" class="form-select @error('pelanggan_id') is-invalid @enderror">
                            <option value="">-- Pilih Pelanggan --</option>
                            @foreach ($pelanggans as $p)
                                <option value="{{ $p->id }}">{{ $p->nama_pelanggan }} ({{ $p->tipe->label() }})</option>
                            @endforeach
                        </select>
                        @error('pelanggan_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Faktur Penjualan Terkait (Opsional)</label>
                        <select wire:model="penjualan_id" class="form-select" @disabled(!$pelanggan_id)>
                            <option value="">-- Tanpa Referensi Faktur --</option>
                            @foreach ($penjualans as $pj)
                                <option value="{{ $pj->id }}">{{ $pj->no_penjualan }} ({{ $pj->tanggal->format('d/m/Y') }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Produk yang Diretur <span class="text-danger">*</span></label>
                        <select wire:model.live="produk_id" class="form-select @error('produk_id') is-invalid @enderror">
                            <option value="">-- Pilih Produk --</option>
                            @foreach ($produks as $pr)
                                <option value="{{ $pr->id }}">{{ $pr->nama }} [{{ $pr->satuan->label() }}]</option>
                            @endforeach
                        </select>
                        @error('produk_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Gudang Penerima <span class="text-danger">*</span></label>
                        <select wire:model="gudang_id" class="form-select @error('gudang_id') is-invalid @enderror">
                            <option value="">-- Pilih Gudang --</option>
                            @foreach ($gudangs as $g)
                                <option value="{{ $g->id }}">{{ $g->nama_gudang }}</option>
                            @endforeach
                        </select>
                        @error('gudang_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Jumlah Retur <span class="text-danger">*</span></label>
                        <input type="number" step="0.001" wire:model="jumlah" class="form-control @error('jumlah') is-invalid @enderror" placeholder="0.00">
                        @error('jumlah') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Harga Retur (Rp)</label>
                        <input type="number" step="0.01" wire:model="harga_retur" class="form-control @error('harga_retur') is-invalid @enderror" placeholder="0.00">
                        @error('harga_retur') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-12">
                        <label class="form-label fw-semibold">Alasan Retur</label>
                        <textarea wire:model="alasan" class="form-control" rows="2" placeholder="Barang kelebihan order / salah warna / rusak..."></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('retur-pelanggan.index') }}" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary px-4">
                <i class="bi bi-check-circle me-1"></i> Simpan Retur Pelanggan
            </button>
        </div>
    </form>
</div>
