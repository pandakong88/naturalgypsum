<div>
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="bi bi-shop text-primary me-2"></i>Profil Perusahaan & Pengaturan Toko
            </h4>
            <p class="text-muted small mb-0">Kelola identitas resmi toko, kontak, logo, serta daftar rekening bank yang dicetak pada dokumen faktur, surat jalan, dan struk kasir.</p>
        </div>
    </div>

    @if (session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm d-flex align-items-center" role="alert">
            <i class="bi bi-check-circle-fill fs-5 me-2"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <h6 class="alert-heading fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Terdapat kesalahan pengisian:</h6>
            <ul class="mb-0 ps-3 small">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form wire:submit="save">
        <div class="row g-4">
            <!-- Kolom Kiri: Form Data Toko & Rekening -->
            <div class="col-lg-8">
                <!-- Card 1: Identitas Toko -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                        <h6 class="card-title fw-bold mb-0 text-primary">
                            <i class="bi bi-building me-2"></i>Identitas Utama Toko
                        </h6>
                        <span class="badge bg-primary-subtle text-primary border">Wajib diisi</span>
                    </div>
                    <div class="card-body p-3 p-md-4">
                        <div class="row g-3">
                            <div class="col-md-7">
                                <label class="form-label fw-bold small text-dark">
                                    Nama Toko / Perusahaan <span class="text-danger">*</span>
                                </label>
                                <input
                                    type="text"
                                    wire:model="nama_toko"
                                    class="form-control @error('nama_toko') is-invalid @enderror"
                                    placeholder="Contoh: NATURAL GYPSUM & PVC"
                                >
                                @error('nama_toko') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-5">
                                <label class="form-label fw-bold small text-dark">
                                    No. Telepon / WhatsApp Kasir
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-whatsapp text-success"></i></span>
                                    <input
                                        type="text"
                                        wire:model="telepon"
                                        class="form-control @error('telepon') is-invalid @enderror"
                                        placeholder="Contoh: 0812-3456-7890"
                                    >
                                </div>
                                @error('telepon') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold small text-dark">
                                    Slogan / Bidang Usaha
                                </label>
                                <input
                                    type="text"
                                    wire:model="slogan"
                                    class="form-control @error('slogan') is-invalid @enderror"
                                    placeholder="Contoh: Distributor & Toko Material Plafon, Gypsum, Rangka Hollow & PVC"
                                >
                                @error('slogan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <div class="form-text small">Teks ini akan tercantum di bawah nama toko pada kop faktur dan surat jalan.</div>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold small text-dark">
                                    Alamat Lengkap Toko / Gudang Pusat
                                </label>
                                <textarea
                                    wire:model="alamat"
                                    rows="2"
                                    class="form-control @error('alamat') is-invalid @enderror"
                                    placeholder="Contoh: Jl. Raya Utama No. 88, Pergudangan Barat, Jawa Timur"
                                ></textarea>
                                @error('alamat') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-dark">
                                    Email Toko
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                                    <input
                                        type="email"
                                        wire:model="email"
                                        class="form-control @error('email') is-invalid @enderror"
                                        placeholder="info@naturalgypsum.com"
                                    >
                                </div>
                                @error('email') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-dark">
                                    Website / Medsos
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-globe"></i></span>
                                    <input
                                        type="text"
                                        wire:model="website"
                                        class="form-control @error('website') is-invalid @enderror"
                                        placeholder="naturalgypsum.com"
                                    >
                                </div>
                                @error('website') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Rekening Bank Pembayaran Transfer -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h6 class="card-title fw-bold mb-0 text-success">
                                <i class="bi bi-credit-card-2-front me-2"></i>Rekening Bank Resmi Toko (Transfer)
                            </h6>
                            <small class="text-muted">Nomor rekening ini akan dicetak otomatis di Faktur & Struk Thermal saat pelanggan bayar transfer atau tempo.</small>
                        </div>
                        <button type="button" wire:click="addRekening" class="btn btn-sm btn-outline-success w-100 w-sm-auto">
                            <i class="bi bi-plus-circle me-1"></i>Tambah Bank
                        </button>
                    </div>
                    <div class="card-body p-2.5 p-md-4">
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle mb-0 table-mobile-cards">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 140px;">Nama Bank</th>
                                        <th>Nomor Rekening</th>
                                        <th>Atas Nama (A.N.)</th>
                                        <th style="width: 50px;" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($rekening_bank as $idx => $rek)
                                        <tr wire:key="rekening-row-{{ $idx }}">
                                            <td data-label="Nama Bank">
                                                <input
                                                    type="text"
                                                    wire:model="rekening_bank.{{ $idx }}.nama_bank"
                                                    class="form-control form-control-sm text-uppercase fw-bold font-monospace"
                                                    placeholder="BCA / MANDIRI"
                                                >
                                            </td>
                                            <td data-label="Nomor Rekening">
                                                <input
                                                    type="text"
                                                    wire:model="rekening_bank.{{ $idx }}.nomor_rekening"
                                                    class="form-control form-control-sm font-monospace"
                                                    placeholder="123-456-7890"
                                                >
                                            </td>
                                            <td data-label="Atas Nama (A.N.)">
                                                <input
                                                    type="text"
                                                    wire:model="rekening_bank.{{ $idx }}.atas_nama"
                                                    class="form-control form-control-sm"
                                                    placeholder="Nama Pemilik Rekening"
                                                >
                                            </td>
                                            <td class="text-end text-md-center">
                                                <button
                                                    type="button"
                                                    wire:click="removeRekening({{ $idx }})"
                                                    class="btn btn-sm btn-outline-danger py-1 px-2 d-inline-flex align-items-center"
                                                    title="Hapus baris rekening"
                                                >
                                                    <i class="bi bi-trash"></i>
                                                    <span class="d-md-none ms-1 small">Hapus Rekening</span>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Syarat & Ketentuan Nota (Catatan Kaki Faktur) -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="card-title fw-bold mb-0 text-dark">
                            <i class="bi bi-receipt me-2"></i>Catatan Kaki Dokumen (Syarat & Ketentuan Faktur)
                        </h6>
                    </div>
                    <div class="card-body p-3 p-md-4">
                        <label class="form-label fw-bold small text-dark">
                            Teks Catatan Kaki / Ketentuan Toko
                        </label>
                        <textarea
                            wire:model="catatan_kaki_faktur"
                            rows="2"
                            class="form-control @error('catatan_kaki_faktur') is-invalid @enderror"
                            placeholder="Contoh: Barang yang sudah dibeli tidak dapat ditukar/dikembalikan tanpa perjanjian tertulis."
                        ></textarea>
                        @error('catatan_kaki_faktur') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text small">Teks ini akan tercetak di bagian catatan bawah faktur penjualan dan tanda terima.</div>
                    </div>
                </div>
            </div>

            <!-- Kolom Kanan: Upload Logo & Live Preview Dokumen -->
            <div class="col-lg-4">
                <!-- Card Upload Logo -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="card-title fw-bold mb-0 text-dark">
                            <i class="bi bi-image me-2"></i>Logo Toko (Kop Nota)
                        </h6>
                    </div>
                    <div class="card-body p-4 text-center">
                        <div class="mb-3 d-flex justify-content-center">
                            @if ($logo)
                                <div class="border rounded p-2 bg-light text-center position-relative">
                                    <img src="{{ $logo->temporaryUrl() }}" alt="Preview Logo" class="img-fluid" style="max-height: 100px;">
                                    <div class="small text-muted mt-1"><i class="bi bi-clock-history me-1"></i>Logo baru (belum disimpan)</div>
                                </div>
                            @elseif ($logo_path)
                                <div class="border rounded p-2 bg-light text-center position-relative">
                                    <img src="{{ asset('storage/' . $logo_path) }}" alt="Logo Toko" class="img-fluid" style="max-height: 100px;">
                                    <div class="mt-2">
                                        <button type="button" wire:click="deleteLogo" class="btn btn-xs btn-outline-danger py-1 px-2" style="font-size: 0.75rem;">
                                            <i class="bi bi-trash me-1"></i>Kembali ke Logo Default
                                        </button>
                                    </div>
                                </div>
                            @else
                                <div class="border rounded p-3 w-100 text-center" style="background-color: #FAF8F5;">
                                    <img src="{{ asset('images/logo-natural-modern-horizontal.svg') }}" alt="Logo Rebrand Natural Gypsum & PVC" class="img-fluid mb-2" style="max-height: 82px;">
                                    <div>
                                        <span class="badge bg-dark-subtle text-dark border small">
                                            <i class="bi bi-patch-check-fill text-warning me-1"></i>Logo Rebrand Modern Aktif
                                        </span>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="mb-2">
                            <input
                                type="file"
                                wire:model="logo"
                                class="form-control form-control-sm @error('logo') is-invalid @enderror"
                                accept="image/png,image/jpeg,image/webp"
                            >
                            @error('logo') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-text small text-muted">Format: PNG, JPG, WebP. Maks 2MB. Disarankan berlatar belakang transparan atau putih.</div>
                    </div>
                </div>

                <!-- Card Live Preview Kop Nota -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="card-title fw-bold mb-0 text-dark">
                            <i class="bi bi-eye me-2"></i>Live Preview Kop Faktur
                        </h6>
                    </div>
                    <div class="card-body p-3 bg-light border-bottom">
                        <div class="p-3 bg-white border rounded shadow-xs" style="font-size: 11px; line-height: 1.4;">
                            <div class="d-flex align-items-start gap-2 mb-1">
                                <img
                                    src="{{ $logo ? $logo->temporaryUrl() : ($logo_path ? asset('storage/' . $logo_path) : asset('images/logo-natural-modern-kop.svg')) }}"
                                    alt="Preview Logo Kop"
                                    style="height: 40px; width: auto; object-fit: contain; flex-shrink: 0;"
                                >
                                <div>
                                    <div class="fw-bold text-dark fs-6 lh-sm">{{ $nama_toko ?: 'NAMA TOKO ANDA' }}</div>
                                    <div class="text-muted small">{{ $slogan ?: 'Slogan / Keterangan Usaha' }}</div>
                                </div>
                            </div>
                            <div class="text-secondary small mt-1">
                                <i class="bi bi-geo-alt me-1"></i>{{ $alamat ?: 'Alamat toko belum diisi' }}
                            </div>
                            <div class="text-secondary small">
                                <i class="bi bi-telephone me-1"></i>{{ $telepon ?: 'No. telp / WhatsApp' }}
                            </div>

                            <hr class="my-2 border-secondary-subtle">

                            <div class="fw-bold text-primary mb-1">INFO REKENING TRANSFER:</div>
                            @php
                                $validBanks = array_filter($rekening_bank, fn($b) => filled($b['nama_bank'] ?? null) || filled($b['nomor_rekening'] ?? null));
                            @endphp
                            @if (count($validBanks) > 0)
                                @foreach ($validBanks as $vb)
                                    <div class="font-monospace small">
                                        &bull; <strong>{{ strtoupper($vb['nama_bank'] ?? 'BANK') }}:</strong> {{ $vb['nomor_rekening'] ?? '-' }} (a.n. {{ $vb['atas_nama'] ?? '-' }})
                                    </div>
                                @endforeach
                            @else
                                <div class="text-muted small fst-italic">Belum ada rekening yang diisi.</div>
                            @endif

                            @if (filled($catatan_kaki_faktur))
                                <div class="mt-2 pt-1 border-top border-dashed text-muted" style="font-size: 9.5px;">
                                    * {{ $catatan_kaki_faktur }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Tombol Simpan -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-3">
                        <button type="submit" class="btn btn-primary w-100 py-2.5 fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2">
                            <span wire:loading.remove wire:target="save"><i class="bi bi-check-lg fs-5"></i> Simpan Profil Perusahaan</span>
                            <span wire:loading wire:target="save">
                                <span class="spinner-border spinner-border-sm" role="status"></span> Menyimpan...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
