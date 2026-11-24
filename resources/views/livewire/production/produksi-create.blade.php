<div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0">Catat Hasil Produksi</h4>
            <p class="text-muted small mb-0">Tambah stok barang jadi / hasil produksi ke gudang penyimpanan</p>
        </div>
        <a href="{{ route('produksi.index') }}" class="btn btn-outline-secondary">
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
                <h6 class="fw-bold mb-0 text-primary"><i class="bi bi-info-circle me-1"></i> Informasi Produksi</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Tanggal Selesai Produksi <span class="text-danger">*</span></label>
                        <input type="date" wire:model="tanggal" class="form-control @error('tanggal') is-invalid @enderror">
                        @error('tanggal') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Gudang Tujuan Masuk <span class="text-danger">*</span></label>
                        <select wire:model="gudang_id" class="form-select @error('gudang_id') is-invalid @enderror">
                            <option value="">-- Pilih Gudang Penerima --</option>
                            @foreach ($gudangs as $g)
                                <option value="{{ $g->id }}">{{ $g->nama_gudang }}</option>
                            @endforeach
                        </select>
                        @error('gudang_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-12">
                        <label class="form-label fw-semibold">Catatan / Shift Produksi</label>
                        <textarea wire:model="catatan" class="form-control" rows="2" placeholder="Catatan shift / batch / keterangan operator..."></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-primary"><i class="bi bi-box-seam me-1"></i> Rincian Barang Jadi yang Dihasilkan</h6>
                <button type="button" wire:click="addItem" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Baris
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4" style="width: 50px;">#</th>
                                <th style="min-width: 300px;">Produk Hasil Produksi <span class="text-danger">*</span></th>
                                <th style="width: 250px;">Jumlah Output <span class="text-danger">*</span></th>
                                <th style="width: 80px;" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $index => $item)
                                <tr>
                                    <td class="ps-4 text-muted">{{ $index + 1 }}</td>
                                    <td>
                                        <select wire:model="items.{{ $index }}.produk_id" class="form-select @error('items.' . $index . '.produk_id') is-invalid @enderror">
                                            <option value="">-- Pilih Produk --</option>
                                            @foreach ($produks as $p)
                                                <option value="{{ $p->id }}">{{ $p->nama }} ({{ $p->kode_katalog ?? 'No SKU' }}) [{{ $p->satuan->label() }}]</option>
                                            @endforeach
                                        </select>
                                        @error('items.' . $index . '.produk_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </td>
                                    <td>
                                        <div class="input-group">
                                            <input type="number" step="0.001" wire:model="items.{{ $index }}.jumlah" class="form-control @error('items.' . $index . '.jumlah') is-invalid @enderror" placeholder="0.00">
                                            <button type="button" wire:click="openRollConverter({{ $index }})" class="btn btn-outline-secondary" title="Konversi Roll/Meter">
                                                <i class="bi bi-calculator"></i>
                                            </button>
                                        </div>
                                        @error('items.' . $index . '.jumlah') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                    </td>
                                    <td class="text-center">
                                        <button type="button" wire:click="removeItem({{ $index }})" class="btn btn-sm btn-outline-danger" @disabled(count($items) === 1)>
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('produksi.index') }}" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary px-4">
                <i class="bi bi-check-circle me-1"></i> Simpan Output Produksi
            </button>
        </div>
    </form>

    <!-- Modal Roll Calculator -->
    @if ($isRollModalOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h6 class="modal-title fw-bold"><i class="bi bi-calculator me-1"></i> Kalkulator Konversi Roll</h6>
                        <button type="button" class="btn-close" wire:click="closeRollConverter"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Jumlah Roll</label>
                            <input type="number" step="1" wire:model.live="rollCount" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Panjang per Roll (Meter)</label>
                            <input type="number" step="0.01" wire:model.live="rollLength" class="form-control">
                        </div>
                        <div class="p-3 bg-light rounded text-center">
                            <span class="text-muted small d-block">Hasil Konversi Meter:</span>
                            <h4 class="fw-bold text-primary mb-0">{{ (float) $rollCount * (float) $rollLength }} Meter</h4>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" wire:click="closeRollConverter">Tutup</button>
                        <button type="button" class="btn btn-primary btn-sm" wire:click="applyRollConversion">Terapkan ke Jumlah</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
