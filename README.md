# Natural Gypsum & PVC — Inventory & Operations Management System

Sistem manajemen inventaris, operasional toko, dan transaksi multi-gudang untuk bisnis material bangunan (Natural Gypsum & PVC).

---

## 🚀 Technology Stack

- **Framework**: Laravel 13.x
- **PHP**: PHP 8.3.x
- **Database**: MySQL (Development/Production), SQLite (:memory: for Tests)
- **Frontend**: Blade, Bootstrap 5.3.x, Bootstrap Icons, Livewire 4.x, Alpine.js (Livewire-managed)
- **Authorization**: Spatie Laravel Permission 8.x
- **Testing**: Pest 4.x
- **Asset Bundler**: Vite 8.x + Sass

---

## 📋 Requirements

- PHP `>= 8.3` (with `pdo`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`)
- Composer `>= 2.x`
- Node.js `>= 18.x` & npm
- MySQL / MariaDB

---

## 🛠️ Installation & Setup

1. **Clone the repository:**
   ```bash
   git clone <repository-url>
   cd "natural gypsum and pvc by pandakong"
   ```

2. **Install PHP dependencies:**
   ```bash
   composer install
   ```

3. **Install JavaScript dependencies:**
   ```bash
   npm install
   ```

4. **Set up environment configuration:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Configure database credentials in `.env`:**
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=natural_gypsum
   DB_USERNAME=root
   DB_PASSWORD=your_password
   ```

6. **Run database migrations and seed default roles/permissions:**
   ```bash
   php artisan migrate --seed
   ```

   *Default Seeded User:*
   - **Email:** `owner@naturalgypsum.test`
   - **Password:** `password`
   - **Role:** `Owner`

7. **Compile frontend assets:**
   ```bash
   npm run build
   ```

8. **Start development server:**
   ```bash
   php artisan serve
   ```
   For frontend hot-reloading during development:
   ```bash
   npm run dev
   ```

---

## 🧪 Running Tests

Execute test suite with Pest:
```bash
php artisan test
```
Or run directly via Pest binary:
```bash
vendor/bin/pest
```

---

## 🏗️ Architecture Overview

The application follows a **Pragmatic Modular Monolith + Domain-Oriented Structure**:

```text
app/
├── Domains/
│   ├── Catalog/           # Master Data Produk, Kategori, Merek
│   ├── Inventory/         # Stok, Ledger Mutasi Stok, Transfer, Opname
│   ├── Purchasing/        # Pembelian & Inbound multi-gudang
│   ├── Sales/             # Penjualan, Faktur, Kasir/POS
│   ├── Receivables/       # Pelacakan Piutang Pelanggan
│   ├── Production/        # Produksi Moulding/List (Output)
│   ├── Returns/           # Retur Supplier & Retur Pelanggan
│   ├── Reports/           # Laporan Operasional & Keuangan
│   └── Administration/    # User & Hak Akses
├── Livewire/              # UI Reaktif & Komponen Halaman
├── Models/                # Model Utama (e.g. User)
└── Support/               # Helper & Utilitas Bersama
```

Detailed architectural specifications and business rules can be found in `docs/architecture.md` and `Master Specification — Natural Gypsum & PVC.md`.

---

## 🔒 Default Roles & Permissions

- **Owner**: Akses penuh seluruh sistem dan konfigurasi.
- **Manager**: Manajemen harga, transaksi, dan persetujuan stok opname.
- **Staff Penjualan**: Penjualan, pembayaran kasir, pencatatan piutang.
- **Staff Gudang**: Penerimaan barang, mutasi stok, stok opname, transfer gudang.
- **Staff Produksi**: Pencatatan output produksi moulding.

All permissions can be re-seeded idempotently at any time:
```bash
php artisan db:seed --class=RoleAndPermissionSeeder
```
