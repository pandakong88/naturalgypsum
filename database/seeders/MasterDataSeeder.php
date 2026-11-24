<?php

namespace Database\Seeders;

use App\Domains\Catalog\Enums\SatuanProduk;
use App\Domains\Catalog\Enums\TipePelanggan;
use App\Domains\Catalog\Models\Gudang;
use App\Domains\Catalog\Models\Kategori;
use App\Domains\Catalog\Models\Merek;
use App\Domains\Catalog\Models\Pelanggan;
use App\Domains\Catalog\Models\Produk;
use App\Domains\Catalog\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::first();
        $adminId = $admin?->id;

        // 1. Kategori Induk 1: PVC Plafon & Dinding (6 Sub)
        $pvc = Kategori::firstOrCreate(
            ['nama_kategori' => 'PVC Plafon & Dinding'],
            ['parent_id' => null, 'created_by' => $adminId]
        );

        $pvcGlossy = Kategori::firstOrCreate(
            ['nama_kategori' => 'Seri Glossy', 'parent_id' => $pvc->id],
            ['created_by' => $adminId]
        );

        $pvcDof = Kategori::firstOrCreate(
            ['nama_kategori' => 'Seri Dof', 'parent_id' => $pvc->id],
            ['created_by' => $adminId]
        );

        $pvcOrnamen = Kategori::firstOrCreate(
            ['nama_kategori' => 'Ornamen PVC', 'parent_id' => $pvc->id],
            ['created_by' => $adminId]
        );

        $pvcWallboard = Kategori::firstOrCreate(
            ['nama_kategori' => 'Wallboard', 'parent_id' => $pvc->id],
            ['created_by' => $adminId]
        );

        $pvcWallpanel = Kategori::firstOrCreate(
            ['nama_kategori' => 'Wallpanel', 'parent_id' => $pvc->id],
            ['created_by' => $adminId]
        );

        $pvcLis = Kategori::firstOrCreate(
            ['nama_kategori' => 'Lis PVC', 'parent_id' => $pvc->id],
            ['created_by' => $adminId]
        );

        // 2. Kategori Induk 2: Gypsum & Moulding (4 Sub)
        $gypsum = Kategori::firstOrCreate(
            ['nama_kategori' => 'Gypsum & Moulding'],
            ['parent_id' => null, 'created_by' => $adminId]
        );

        $ceilingBoard = Kategori::firstOrCreate(
            ['nama_kategori' => 'Papan Gypsum (Board)', 'parent_id' => $gypsum->id],
            ['created_by' => $adminId]
        );

        $moulding = Kategori::firstOrCreate(
            ['nama_kategori' => 'Lis Moulding', 'parent_id' => $gypsum->id],
            ['created_by' => $adminId]
        );

        $tempatLampu = Kategori::firstOrCreate(
            ['nama_kategori' => 'Tempat Lampu (Dome)', 'parent_id' => $gypsum->id],
            ['created_by' => $adminId]
        );

        $finishing = Kategori::firstOrCreate(
            ['nama_kategori' => 'Kompon & Finishing', 'parent_id' => $gypsum->id],
            ['created_by' => $adminId]
        );

        // 3. Kategori Induk 3: Rangka & Hollow (4 Sub)
        $rangka = Kategori::firstOrCreate(
            ['nama_kategori' => 'Rangka & Hollow'],
            ['parent_id' => null, 'created_by' => $adminId]
        );

        $hollowGalvanis = Kategori::firstOrCreate(
            ['nama_kategori' => 'Hollow Galvanis', 'parent_id' => $rangka->id],
            ['created_by' => $adminId]
        );

        $hollowMeni = Kategori::firstOrCreate(
            ['nama_kategori' => 'Hollow Meni', 'parent_id' => $rangka->id],
            ['created_by' => $adminId]
        );

        $metalFuring = Kategori::firstOrCreate(
            ['nama_kategori' => 'Metal Furing', 'parent_id' => $rangka->id],
            ['created_by' => $adminId]
        );

        $wallAngle = Kategori::firstOrCreate(
            ['nama_kategori' => 'Wall Angle', 'parent_id' => $rangka->id],
            ['created_by' => $adminId]
        );

        // 4. Kategori Induk 4: Aksesoris & Ricikan (3 Sub)
        $aksesoris = Kategori::firstOrCreate(
            ['nama_kategori' => 'Aksesoris & Ricikan'],
            ['parent_id' => null, 'created_by' => $adminId]
        );

        $bautSekrup = Kategori::firstOrCreate(
            ['nama_kategori' => 'Baut & Sekrup', 'parent_id' => $aksesoris->id],
            ['created_by' => $adminId]
        );

        $alatSkrap = Kategori::firstOrCreate(
            ['nama_kategori' => 'Alat & Skrap', 'parent_id' => $aksesoris->id],
            ['created_by' => $adminId]
        );

        $ricikanLainnya = Kategori::firstOrCreate(
            ['nama_kategori' => 'Ricikan Lainnya', 'parent_id' => $aksesoris->id],
            ['created_by' => $adminId]
        );

        // Brands
        $vifon = Merek::firstOrCreate(
            ['nama_merek' => 'Vifon'],
            ['created_by' => $adminId]
        );

        $natural = Merek::firstOrCreate(
            ['nama_merek' => 'Natural Gypsum'],
            ['created_by' => $adminId]
        );

        $jayaboard = Merek::firstOrCreate(
            ['nama_merek' => 'Jayaboard'],
            ['created_by' => $adminId]
        );

        $aplus = Merek::firstOrCreate(
            ['nama_merek' => 'Aplus'],
            ['created_by' => $adminId]
        );

        $kencana = Merek::firstOrCreate(
            ['nama_merek' => 'Kencana Truss'],
            ['created_by' => $adminId]
        );

        $dextone = Merek::firstOrCreate(
            ['nama_merek' => 'Dextone'],
            ['created_by' => $adminId]
        );

        // Warehouses (3 Gudang Resmi Operasional)
        Gudang::firstOrCreate(
            ['nama_gudang' => 'Toko Utama'],
            [
                'lokasi' => 'Jl. Merdeka No. 45 - Ruko Display & Kasir Retail',
                'kontak' => 'Bpk. Agus (Kasir Retail) - 0812-3456-7891',
                'created_by' => $adminId,
            ]
        );

        Gudang::firstOrCreate(
            ['nama_gudang' => 'Gudang Selatan'],
            [
                'lokasi' => 'Kawasan Industri Selatan Kav. 12 - Gudang Papan & Hollow',
                'kontak' => 'Bpk. Budi (Kepala Gudang Selatan) - 0813-9876-5432',
                'created_by' => $adminId,
            ]
        );

        Gudang::firstOrCreate(
            ['nama_gudang' => 'Gudang Utara'],
            [
                'lokasi' => 'Jl. Lingkar Utara KM 5 - Gudang Transit PVC & Profil Gypsum',
                'kontak' => 'Bpk. Deni (Kepala Gudang Utara) - 0815-5566-7788',
                'created_by' => $adminId,
            ]
        );

        // Suppliers
        Supplier::firstOrCreate(
            ['nama_supplier' => 'PT Vifon Indah Perkasa'],
            [
                'kontak' => '0812-3456-7890 (Ibu Linda - Sales)',
                'alamat' => 'Kawasan Industri Cikupa Mas Blok C-12, Tangerang, Banten',
                'email' => 'sales@vifonpvc.co.id',
                'nama_bank' => 'BCA',
                'no_rekening' => '8830192831',
                'atas_nama_rekening' => 'PT Vifon Indah Perkasa',
                'created_by' => $adminId,
            ]
        );

        Supplier::firstOrCreate(
            ['nama_supplier' => 'CV Gypsum Prima Mandiri'],
            [
                'kontak' => '0821-9876-5432 (Bpk. Joko)',
                'alamat' => 'Jl. Pahlawan Revolusi No. 88, Pondok Bambu, Jakarta Timur',
                'email' => 'prima.gypsum@gmail.com',
                'nama_bank' => 'Bank Mandiri',
                'no_rekening' => '1240008899123',
                'atas_nama_rekening' => 'CV Gypsum Prima Mandiri',
                'created_by' => $adminId,
            ]
        );

        Supplier::firstOrCreate(
            ['nama_supplier' => 'PT Jayaboard Indonesia'],
            [
                'kontak' => '0811-2233-4455 (Bpk. Aris - Key Account)',
                'alamat' => 'Gedung Menara Jayaboard Lt. 15, Jl. Jend. Sudirman Kav. 21, Jakarta Selatan',
                'email' => 'order@jayaboard.co.id',
                'nama_bank' => 'BCA',
                'no_rekening' => '5420119820',
                'atas_nama_rekening' => 'PT Jayaboard Indonesia',
                'created_by' => $adminId,
            ]
        );

        Supplier::firstOrCreate(
            ['nama_supplier' => 'CV Kencana Truss Baja'],
            [
                'kontak' => '0819-7788-9900 (Admin Logistik)',
                'alamat' => 'Kawasan Pergudangan Rungkut Industri Blok E No. 4, Surabaya',
                'email' => 'admin.surabaya@kencanatruss.id',
                'nama_bank' => 'BRI',
                'no_rekening' => '034101000987302',
                'atas_nama_rekening' => 'CV Kencana Truss Baja',
                'created_by' => $adminId,
            ]
        );

        // Customers
        Pelanggan::firstOrCreate(
            ['nama' => 'Pelanggan Umum / Tunai'],
            [
                'kontak' => '-',
                'tipe' => TipePelanggan::Umum,
                'limit_piutang' => 0.00,
                'created_by' => $adminId,
            ]
        );

        Pelanggan::firstOrCreate(
            ['nama' => 'Bpk. Hendra (Kontraktor Mitra)'],
            [
                'kontak' => '0813-1122-3344',
                'tipe' => TipePelanggan::Pemborong,
                'limit_piutang' => 25000000.00,
                'created_by' => $adminId,
            ]
        );

        Pelanggan::firstOrCreate(
            ['nama' => 'Toko Bangunan Sumber Makmur'],
            [
                'kontak' => '0857-4433-2211',
                'tipe' => TipePelanggan::Langganan,
                'limit_piutang' => 50000000.00,
                'created_by' => $adminId,
            ]
        );

        // Sample Products (Disinkronkan dengan 4 Kategori Hierarkis)
        $sampleProducts = [
            // 1. PVC Plafon & Dinding
            [
                'kode_katalog' => 'PVC-VF-001',
                'kategori_id' => $pvcGlossy->id,
                'merek_id' => $vifon->id,
                'nama' => 'Plafon PVC Glossy Motif Kayu Jati 4m',
                'ukuran' => '4m x 20cm x 8mm',
                'satuan' => SatuanProduk::Meter,
                'harga_beli' => 12500.00,
                'harga_jual' => 16500.00,
                'harga_aplikator' => 15500.00,
                'harga_grosir' => 14500.00,
                'stok_minimum' => 50.000,
                'panjang_per_roll' => 25.00,
            ],
            [
                'kode_katalog' => 'PVC-DF-001',
                'kategori_id' => $pvcDof->id,
                'merek_id' => $vifon->id,
                'nama' => 'Plafon PVC Dof Putih Minimalis 4m',
                'ukuran' => '4m x 20cm x 8mm',
                'satuan' => SatuanProduk::Meter,
                'harga_beli' => 12000.00,
                'harga_jual' => 15500.00,
                'harga_aplikator' => 14500.00,
                'harga_grosir' => 14000.00,
                'stok_minimum' => 50.000,
                'panjang_per_roll' => 25.00,
            ],
            [
                'kode_katalog' => 'PVC-ORN-001',
                'kategori_id' => $pvcOrnamen->id,
                'merek_id' => $vifon->id,
                'nama' => 'Ornamen Lampu Tengah PVC Motif Bunga 60cm',
                'ukuran' => 'Diameter 60cm',
                'satuan' => SatuanProduk::Pcs,
                'harga_beli' => 45000.00,
                'harga_jual' => 65000.00,
                'harga_aplikator' => 60000.00,
                'harga_grosir' => 55000.00,
                'stok_minimum' => 10.000,
                'panjang_per_roll' => null,
            ],
            [
                'kode_katalog' => 'PVC-WB-001',
                'kategori_id' => $pvcWallboard->id,
                'merek_id' => $vifon->id,
                'nama' => 'Wallboard WPC Lembaran 3m x 60cm',
                'ukuran' => '3m x 60cm x 9mm',
                'satuan' => SatuanProduk::Lembar,
                'harga_beli' => 110000.00,
                'harga_jual' => 140000.00,
                'harga_aplikator' => 130000.00,
                'harga_grosir' => 125000.00,
                'stok_minimum' => 15.000,
                'panjang_per_roll' => null,
            ],
            [
                'kode_katalog' => 'PVC-WP-001',
                'kategori_id' => $pvcWallpanel->id,
                'merek_id' => $vifon->id,
                'nama' => 'Fluted Wallpanel Wood Series 2.9m',
                'ukuran' => '2.9m x 16cm x 24mm',
                'satuan' => SatuanProduk::Batang,
                'harga_beli' => 48000.00,
                'harga_jual' => 65000.00,
                'harga_aplikator' => 60000.00,
                'harga_grosir' => 58000.00,
                'stok_minimum' => 20.000,
                'panjang_per_roll' => null,
            ],
            [
                'kode_katalog' => 'PVC-LIS-001',
                'kategori_id' => $pvcLis->id,
                'merek_id' => $vifon->id,
                'nama' => 'Lis Figura / Tutup PVC 4m',
                'ukuran' => 'Panjang 4m',
                'satuan' => SatuanProduk::Batang,
                'harga_beli' => 18000.00,
                'harga_jual' => 25000.00,
                'harga_aplikator' => 23000.00,
                'harga_grosir' => 22000.00,
                'stok_minimum' => 30.000,
                'panjang_per_roll' => null,
            ],

            // 2. Gypsum & Moulding
            [
                'kode_katalog' => 'BRD-NG-001',
                'kategori_id' => $ceilingBoard->id,
                'merek_id' => $jayaboard->id,
                'nama' => 'Papan Gypsum Board 9mm (120x240cm)',
                'ukuran' => '120cm x 240cm x 9mm',
                'satuan' => SatuanProduk::Lembar,
                'harga_beli' => 58000.00,
                'harga_jual' => 70000.00,
                'harga_aplikator' => 66000.00,
                'harga_grosir' => 64000.00,
                'stok_minimum' => 20.000,
                'panjang_per_roll' => null,
            ],
            [
                'kode_katalog' => 'LST-NG-001',
                'kategori_id' => $moulding->id,
                'merek_id' => $natural->id,
                'nama' => 'List Profil Gypsum Klasik Motif Ulir',
                'ukuran' => 'Panjang 2m, Lebar 8cm',
                'satuan' => SatuanProduk::Batang,
                'harga_beli' => 15000.00,
                'harga_jual' => 22500.00,
                'harga_aplikator' => 20000.00,
                'harga_grosir' => 19000.00,
                'stok_minimum' => 30.000,
                'panjang_per_roll' => null,
            ],
            [
                'kode_katalog' => 'GYP-DME-001',
                'kategori_id' => $tempatLampu->id,
                'merek_id' => $natural->id,
                'nama' => 'Dome Kubah Lampu Gypsum Polos 50cm',
                'ukuran' => 'Diameter 50cm',
                'satuan' => SatuanProduk::Pcs,
                'harga_beli' => 55000.00,
                'harga_jual' => 85000.00,
                'harga_aplikator' => 75000.00,
                'harga_grosir' => 70000.00,
                'stok_minimum' => 5.000,
                'panjang_per_roll' => null,
            ],
            [
                'kode_katalog' => 'CMP-NG-001',
                'kategori_id' => $finishing->id,
                'merek_id' => $aplus->id,
                'nama' => 'Tepung Kompon Gypsum Cornice Adhesive 20kg',
                'ukuran' => 'Zak 20 kg',
                'satuan' => SatuanProduk::Zak,
                'harga_beli' => 62000.00,
                'harga_jual' => 75000.00,
                'harga_aplikator' => 70000.00,
                'harga_grosir' => 68000.00,
                'stok_minimum' => 15.000,
                'panjang_per_roll' => null,
            ],

            // 3. Rangka & Hollow
            [
                'kode_katalog' => 'HLW-GLV-001',
                'kategori_id' => $hollowGalvanis->id,
                'merek_id' => $kencana->id,
                'nama' => 'Besi Hollow Galvanis 2x4 (0.30mm, 4m)',
                'ukuran' => '2cm x 4cm x 4m (0.30mm)',
                'satuan' => SatuanProduk::Batang,
                'harga_beli' => 18500.00,
                'harga_jual' => 24000.00,
                'harga_aplikator' => 22000.00,
                'harga_grosir' => 21000.00,
                'stok_minimum' => 40.000,
                'panjang_per_roll' => null,
            ],
            [
                'kode_katalog' => 'HLW-GLV-002',
                'kategori_id' => $hollowGalvanis->id,
                'merek_id' => $kencana->id,
                'nama' => 'Besi Hollow Galvanis 4x4 (0.30mm, 4m)',
                'ukuran' => '4cm x 4cm x 4m (0.30mm)',
                'satuan' => SatuanProduk::Batang,
                'harga_beli' => 26000.00,
                'harga_jual' => 34000.00,
                'harga_aplikator' => 31000.00,
                'harga_grosir' => 29500.00,
                'stok_minimum' => 30.000,
                'panjang_per_roll' => null,
            ],
            [
                'kode_katalog' => 'HLW-MNI-001',
                'kategori_id' => $hollowMeni->id,
                'merek_id' => $kencana->id,
                'nama' => 'Besi Hollow Meni Hijau 2x4 (4m)',
                'ukuran' => '2cm x 4cm x 4m',
                'satuan' => SatuanProduk::Batang,
                'harga_beli' => 16000.00,
                'harga_jual' => 21000.00,
                'harga_aplikator' => 19000.00,
                'harga_grosir' => 18000.00,
                'stok_minimum' => 30.000,
                'panjang_per_roll' => null,
            ],
            [
                'kode_katalog' => 'FUR-STD-001',
                'kategori_id' => $metalFuring->id,
                'merek_id' => $kencana->id,
                'nama' => 'Metal Furing Plafon Standar 4m',
                'ukuran' => 'Panjang 4m',
                'satuan' => SatuanProduk::Batang,
                'harga_beli' => 19000.00,
                'harga_jual' => 25000.00,
                'harga_aplikator' => 23000.00,
                'harga_grosir' => 22000.00,
                'stok_minimum' => 35.000,
                'panjang_per_roll' => null,
            ],
            [
                'kode_katalog' => 'WLA-GLV-001',
                'kategori_id' => $wallAngle->id,
                'merek_id' => $kencana->id,
                'nama' => 'Siku Wall Angle Galvanis 3m',
                'ukuran' => '2cm x 2cm x 3m',
                'satuan' => SatuanProduk::Batang,
                'harga_beli' => 9500.00,
                'harga_jual' => 14000.00,
                'harga_aplikator' => 12500.00,
                'harga_grosir' => 11500.00,
                'stok_minimum' => 50.000,
                'panjang_per_roll' => null,
            ],

            // 4. Aksesoris & Ricikan
            [
                'kode_katalog' => 'SKR-GEN-001',
                'kategori_id' => $bautSekrup->id,
                'merek_id' => $natural->id,
                'nama' => 'Sekrup Gypsum Hitam 6 x 1 inch',
                'ukuran' => '1 Dus (1.000 pcs)',
                'satuan' => SatuanProduk::Dus,
                'harga_beli' => 27000.00,
                'harga_jual' => 35000.00,
                'harga_aplikator' => 32000.00,
                'harga_grosir' => 30000.00,
                'stok_minimum' => 15.000,
                'panjang_per_roll' => null,
            ],
            [
                'kode_katalog' => 'ALT-SKR-001',
                'kategori_id' => $alatSkrap->id,
                'merek_id' => $natural->id,
                'nama' => 'Kape Skrap Besi Gagang Karet 5 inch',
                'ukuran' => 'Lebar 5 inch',
                'satuan' => SatuanProduk::Pcs,
                'harga_beli' => 12500.00,
                'harga_jual' => 18000.00,
                'harga_aplikator' => 16000.00,
                'harga_grosir' => 15000.00,
                'stok_minimum' => 10.000,
                'panjang_per_roll' => null,
            ],
            [
                'kode_katalog' => 'RCK-KSA-001',
                'kategori_id' => $ricikanLainnya->id,
                'merek_id' => $natural->id,
                'nama' => 'Kain Kasa Gypsum Perekat (Textile Tape Roll)',
                'ukuran' => 'Lebar 3cm x Panjang 45m',
                'satuan' => SatuanProduk::Pcs,
                'harga_beli' => 8000.00,
                'harga_jual' => 12000.00,
                'harga_aplikator' => 10500.00,
                'harga_grosir' => 9500.00,
                'stok_minimum' => 20.000,
                'panjang_per_roll' => null,
            ],
            [
                'kode_katalog' => 'RCK-SLN-001',
                'kategori_id' => $ricikanLainnya->id,
                'merek_id' => $dextone->id,
                'nama' => 'Lem Sealant Silikon Tabung Netral Dextone',
                'ukuran' => 'Tabung 300ml',
                'satuan' => SatuanProduk::Pcs,
                'harga_beli' => 22000.00,
                'harga_jual' => 30000.00,
                'harga_aplikator' => 27000.00,
                'harga_grosir' => 25500.00,
                'stok_minimum' => 12.000,
                'panjang_per_roll' => null,
            ],
        ];

        foreach ($sampleProducts as $pData) {
            $kode = $pData['kode_katalog'];
            unset($pData['kode_katalog']);
            $pData['created_by'] = $adminId;

            Produk::updateOrCreate(
                ['kode_katalog' => $kode],
                $pData
            );
        }

        // Bersihkan kategori lama yang bukan bagian dari 4 Kategori Induk & 17 Sub-Kategori resmi ini
        $validRootNames = [
            'PVC Plafon & Dinding',
            'Gypsum & Moulding',
            'Rangka & Hollow',
            'Aksesoris & Ricikan',
        ];

        $validSubNames = [
            'Seri Glossy',
            'Seri Dof',
            'Ornamen PVC',
            'Wallboard',
            'Wallpanel',
            'Lis PVC',
            'Papan Gypsum (Board)',
            'Lis Moulding',
            'Tempat Lampu (Dome)',
            'Kompon & Finishing',
            'Hollow Galvanis',
            'Hollow Meni',
            'Metal Furing',
            'Wall Angle',
            'Baut & Sekrup',
            'Alat & Skrap',
            'Ricikan Lainnya',
        ];

        $validRootIds = Kategori::whereIn('nama_kategori', $validRootNames)->whereNull('parent_id')->pluck('id');
        $validSubIds = Kategori::whereIn('nama_kategori', $validSubNames)
            ->whereIn('parent_id', $validRootIds)
            ->pluck('id');

        $validAllIds = $validRootIds->merge($validSubIds);

        Kategori::whereNotIn('id', $validAllIds)
            ->whereDoesntHave('produks')
            ->forceDelete();
    }
}
