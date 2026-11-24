# NATURAL GYPSUM & PVC

## Sistem Manajemen Stok & Operasional Toko

**Document Version:** 1.0  
**Status:** Approved / Locked  
**Target:** Production-ready internal business application

---

# 1. PROJECT OBJECTIVE

Build a web-based inventory and operational management system for a building-materials business selling and producing products such as:

-   PVC Vinyl Vifon
-   Ceiling boards
-   Self-produced moulding/list
-   Other future building-material products

The application must support multiple warehouses and provide reliable stock visibility, transaction history, purchasing, sales, payments, receivables, production, returns, stock opname, reporting, authorization, and auditability.

The application is intended for:

-   Owner
-   Manager
-   Sales staff
-   Warehouse staff
-   Production staff

The system must prioritize:

> **Simple operational UI + strong backend business rules + reliable stock integrity.**

The database may be complex internally, but the user interface must remain simple and practical for ordinary shop staff.

---

# 2. CORE PRINCIPLES

These principles are mandatory.

## 2.1 Stock is transaction-driven

Stock MUST NOT be directly edited from the UI.

All stock changes must occur through recorded business transactions.

Examples:

-   Purchase
-   Sale
-   Transfer
-   Production
-   Supplier return
-   Customer return
-   Internal use
-   Stock opname

The stock ledger is represented by `mutasi_stok`.

---

## 2.2 Never bypass the stock engine

Stock changes MUST go through `StockService`.

Forbidden:

-   Direct stock update from Blade
-   Direct stock update from Livewire
-   Direct stock update from Controller
-   Arbitrary `$stok->update(...)`
-   Direct stock manipulation from unrelated models

Required conceptual flow:

```text
Business Action
    ↓
DB Transaction
    ↓
StockService
    ↓
Update stok
    ↓
Create mutasi_stok
    ↓
Commit
```

---

## 2.3 Business rules belong in backend

The UI may assist users, but backend validation is the source of truth.

Never rely on:

-   JavaScript validation alone
-   Hidden form fields
-   Disabled buttons
-   UI visibility
-   Client-side calculations

for security or business integrity.

---

## 2.4 Auditability

Important business operations must remain traceable.

The system must answer:

-   What happened?
-   When?
-   Who performed it?
-   Which document caused it?
-   What stock changed?
-   What payment occurred?

---

# 3. TECHNOLOGY STACK

Use:

```text
Laravel 13.x
PHP 8.3.x
MySQL
Blade
Livewire 4.x
Bootstrap 5.3.x
Alpine.js
Spatie Laravel Permission
Laravel Excel
Laravel Scheduler
Laravel Filesystem
Pest
Git
GitHub
Hostinger
```

Current development PHP:

```text
PHP 8.3.27
```

## PHP compatibility rule

The project MUST support PHP 8.3.

Do NOT introduce dependencies, syntax, or framework features requiring PHP 8.4+ without explicit approval.

Verify package compatibility with:

-   Laravel 13
-   PHP 8.3
-   Hostinger production environment

Do not blindly use the newest package version if compatibility is uncertain.

---

# 4. ARCHITECTURE

Use:

> Pragmatic Modular Monolith + Domain-Oriented Structure.

Do NOT build:

-   Microservices
-   Distributed services
-   Unnecessary repositories
-   Excessive abstraction
-   Over-engineered Clean Architecture

Suggested structure:

```text
app/
├── Domains/
│   ├── Catalog/
│   │   ├── Actions/
│   │   ├── DTOs/
│   │   ├── Models/
│   │   ├── Services/
│   │   └── Policies/
│   │
│   ├── Inventory/
│   │   ├── Actions/
│   │   ├── DTOs/
│   │   ├── Models/
│   │   ├── Services/
│   │   ├── Events/
│   │   └── Policies/
│   │
│   ├── Purchasing/
│   ├── Sales/
│   ├── Receivables/
│   ├── Production/
│   ├── Returns/
│   ├── Reports/
│   └── Administration/
│
├── Livewire/
├── Models/
├── Policies/
└── Support/
```

Framework/shared models such as `User` may remain in:

```text
app/Models/User.php
```

Do not force every model into a domain.

---

# 5. REQUEST FLOW

Use this conceptual flow:

```text
Browser
   ↓
Blade / Livewire
   ↓
Validation
   ↓
Action
   ↓
Domain Service
   ↓
Model / Database
```

Controllers and Livewire components must remain relatively thin.

Business logic should not be duplicated between:

-   Controller
-   Livewire
-   Service
-   Model
-   JavaScript

---

# 6. DOMAIN STRUCTURE

Main domains:

```text
Dashboard
Master Data
Inventory
Purchasing
Sales
Receivables
Production
Returns
Reports
Administration
```

---

# 7. DATABASE DESIGN

## 7.1 kategori

Fields:

```text
id
nama_kategori
created_by
updated_by
created_at
updated_at
deleted_at
```

`nama_kategori` should be unique where appropriate.

---

## 7.2 merek

Fields:

```text
id
nama_merek
created_by
updated_by
created_at
updated_at
deleted_at
```

Example:

```text
Vifon
```

---

## 7.3 gudang

Fields:

```text
id
nama_gudang
lokasi
created_by
updated_by
created_at
updated_at
deleted_at
```

---

## 7.4 users

Use Laravel's normal authentication user structure.

Do NOT create:

```text
users.role
```

Authorization is handled using Spatie Laravel Permission.

---

## 7.5 pelanggan

Fields:

```text
id
nama
kontak
tipe
limit_piutang
created_by
updated_by
created_at
updated_at
deleted_at
```

Customer types:

```text
umum
pemborong
langganan
```

Money:

```text
DECIMAL(15,2)
```

---

## 7.6 supplier

Fields:

```text
id
nama_supplier
kontak
created_by
updated_by
created_at
updated_at
deleted_at
```

---

# 8. PRODUK

Fields:

```text
id
kategori_id
merek_id nullable
kode_katalog
nama
ukuran
satuan
panjang_per_roll nullable
harga_beli
harga_jual
stok_minimum
created_by
updated_by
created_at
updated_at
deleted_at
```

Quantity:

```text
DECIMAL(15,3)
```

Money:

```text
DECIMAL(15,2)
```

---

# 9. PRODUCT UNIT RULES

## PVC Vinyl

Base unit:

```text
meter
```

Roll is packaging, not the inventory base unit.

Example:

```text
1 roll = 25 meter
```

Purchase may arrive as:

```text
1 roll
```

but stock becomes:

```text
25 meter
```

Sales are recorded in:

```text
meter
```

Use:

```text
panjang_per_roll
```

to support conversion.

---

## Board

Unit:

```text
lembar
```

---

## Moulding / List

Unit:

```text
batang
```

Raw production materials are outside current scope.

---

# 10. STOCK

## stok

Fields:

```text
id
produk_id
gudang_id
jumlah
created_at
updated_at
```

Constraint:

```text
UNIQUE(produk_id, gudang_id)
```

`stok` represents current stock state.

Do NOT store total stock directly on `produk`.

---

# 11. STOCK MUTATION

## mutasi_stok

Fields:

```text
id
produk_id
gudang_id
user_id
jenis
jumlah
referensi
referensi_type
referensi_id
tanggal
created_at
updated_at
```

Mutation types:

```text
pembelian_masuk
produksi_masuk
penjualan_keluar
transfer
retur_ke_supplier
retur_dari_pelanggan
pemakaian_internal
penyesuaian_opname
```

`jenis` should preferably be represented by a PHP Enum while stored as a database string.

`referensi` stores human-readable document number.

`referensi_type` and `referensi_id` provide traceability to the source transaction.

---

# 12. PURCHASE

## pembelian

```text
id
supplier_id
user_id
no_kontainer
tanggal
total
created_by
updated_by
created_at
updated_at
```

## detail_pembelian

```text
id
pembelian_id
produk_id
gudang_id
jumlah
harga_beli_satuan
subtotal
created_at
updated_at
```

One container represents one purchase transaction.

One purchase may distribute products to multiple warehouses.

Large purchases should support Excel/CSV import.

---

# 13. PRODUCTION

## produksi

```text
id
user_id
gudang_id
tanggal
catatan
created_by
updated_by
created_at
updated_at
```

## detail_produksi

```text
id
produksi_id
produk_id
jumlah
```

Production currently tracks output only.

No raw material consumption system is required in this version.

Effect:

```text
produksi_masuk
```

---

# 14. SALES

## penjualan

```text
id
pelanggan_id
gudang_id
user_id
biaya_ongkir
total_akhir
status_pembayaran
total_dibayar
sisa_piutang
tanggal
created_by
updated_by
created_at
updated_at
```

## detail_penjualan

```text
id
penjualan_id
produk_id
harga_satuan
jenis_diskon
nilai_diskon
jumlah
subtotal
```

Transaction prices must be stored in the transaction detail.

Do not dynamically recalculate historical sales using current product prices.

---

# 15. PAYMENT

## pembayaran

```text
id
pelanggan_id
tanggal
jumlah_bayar
metode
user_id
catatan
```

## alokasi_pembayaran

```text
id
pembayaran_id
penjualan_id
jumlah_dialokasikan
```

Payment allocation is MANUAL.

Do NOT implement FIFO allocation unless explicitly requested later.

One payment may be allocated to multiple invoices.

---

# 16. PAYMENT STATUS

Allowed values:

```text
belum_bayar
sebagian
lunas
```

Rules:

```text
total_dibayar = 0
→ belum_bayar

total_dibayar > 0
AND total_dibayar < total
→ sebagian

total_dibayar = total
→ lunas
```

Payment status does not control stock movement.

Goods may leave the warehouse even when the invoice is unpaid.

---

# 17. RETURNS

## Supplier Return

```text
retur_supplier
```

Fields:

```text
supplier_id
produk_id
gudang_id
user_id
pembelian_id nullable
jumlah
alasan
tanggal
```

Effect:

```text
retur_ke_supplier
stock decreases
```

---

## Customer Return

```text
retur_pelanggan
```

Fields:

```text
penjualan_id nullable
produk_id
gudang_id
user_id
jumlah
harga_retur
alasan
tanggal
```

`harga_retur` is manually entered and must be preserved.

Effect:

```text
retur_dari_pelanggan
stock increases
```

---

# 18. STOCK OPNAME

User enters physical quantity.

System calculates:

```text
difference = physical_stock - system_stock
```

Example:

```text
System = 100
Physical = 93
Difference = -7
```

Create:

```text
penyesuaian_opname
```

Document:

```text
SO-YYYY-XXXXXX
```

Do not directly edit stock.

---

# 19. INTERNAL USE

Internal consumption decreases stock.

Mutation:

```text
pemakaian_internal
```

Document:

```text
PI-YYYY-XXXXXX
```

---

# 20. DOCUMENT NUMBERS

Generate automatically.

Formats:

```text
PB-YYYY-000001
PJ-YYYY-000001
BY-YYYY-000001
TR-YYYY-000001
PRD-YYYY-000001
RS-YYYY-000001
RP-YYYY-000001
SO-YYYY-000001
PI-YYYY-000001
```

Rules:

-   Unique
-   Automatically generated
-   Not casually editable
-   Not based on database ID
-   Sequence per transaction type/year
-   Safe under concurrent requests

NEVER implement using:

```php
Model::count() + 1
```

Use a dedicated `DocumentNumberService` with proper concurrency protection.

---

# 21. TRANSACTION ATOMICITY

Business transactions must use database transactions.

Example:

```text
DB::transaction()
    ↓
Create transaction
    ↓
Create details
    ↓
StockService
    ↓
Update stock
    ↓
Create mutation
    ↓
Commit
```

Any failure must rollback all related changes.

---

# 22. STOCK SHORTAGE

Stock shortage is a WARNING, not a hard block.

Example:

```text
Stock = 10
Sale = 15
```

Show warning:

```text
Stok tidak mencukupi.
Stok tersedia: 10
Jumlah penjualan: 15
```

User may choose:

```text
Tetap Simpan
```

If confirmed:

```text
Stock = -5
```

Negative stock is therefore allowed by business rule.

---

# 23. STOCK SERVICE

StockService is the central gatekeeper.

It should provide operations conceptually equivalent to:

```text
increase()
decrease()
transfer()
adjust()
```

Responsibilities:

-   Validate relevant stock operation
-   Lock rows where required
-   Update current stock
-   Create mutation ledger
-   Preserve references
-   Operate within DB transactions

No other application layer should bypass it.

---

# 24. INVENTORY MODEL

Use:

```text
stok
```

for current state.

Use:

```text
mutasi_stok
```

for historical ledger.

Do NOT recalculate current stock from the entire mutation ledger on every page.

`stok` is the operational current state.

`mutasi_stok` is the trusted historical audit trail.

---

# 25. ROLES

Default roles:

```text
Owner
Manager
Staff Penjualan
Staff Gudang
Staff Produksi
```

These are default roles, not hardcoded application logic.

Authorization must be permission-based.

---

# 26. PERMISSIONS

Permission groups include:

```text
view_dashboard

view_products
create_products
update_products
delete_products
restore_products
view_purchase_prices
update_product_prices
view_price_history

view_categories
create_categories
update_categories
delete_categories

view_brands
create_brands
update_brands
delete_brands

view_warehouses
create_warehouses
update_warehouses
delete_warehouses

view_customers
create_customers
update_customers
delete_customers
view_customer_credit_limit
update_customer_credit_limit

view_suppliers
create_suppliers
update_suppliers
delete_suppliers

view_stock
view_stock_mutations
transfer_stock
perform_stock_opname
adjust_stock

view_purchases
create_purchases
update_purchases
cancel_purchases
import_purchases

view_sales
create_sales
update_sales
cancel_sales
apply_sales_discount
override_sales_price

view_payments
record_payments
update_payments
cancel_payments
allocate_payments

view_receivables
view_customer_debt

view_production
create_production
update_production
cancel_production

view_supplier_returns
create_supplier_returns
cancel_supplier_returns

view_customer_returns
create_customer_returns
cancel_customer_returns

view_sales_reports
view_purchase_reports
view_stock_reports
view_receivable_reports
view_production_reports
view_mutation_reports

view_users
create_users
update_users
delete_users
restore_users

view_roles
create_roles
update_roles
delete_roles
assign_roles

view_activity_logs
```

---

# 27. AUTHORIZATION RULE

Never use:

```php
if ($user->role === 'owner')
```

as the primary authorization architecture.

Use:

```text
Permission
+
Policy
+
Scope
+
Business Rule
```

UI hiding is only usability.

Backend authorization is security.

---

# 28. HIGH-RISK PERMISSIONS

Treat these as sensitive:

```text
adjust_stock
cancel_sales
cancel_purchases
cancel_payments
override_sales_price
update_customer_credit_limit
delete master data
manage users
manage roles
```

These operations should be auditable.

---

# 29. AUDIT

Activity log answers:

> Who did what?

Stock mutation answers:

> What happened to stock?

Do not use one as a replacement for the other.

Important actions should be logged, including:

-   Price changes
-   Stock adjustments
-   Stock opname
-   Transaction cancellation
-   Payments
-   Credit limit changes
-   User changes
-   Role/permission changes

---

# 30. PRICE HISTORY

Before updating product price:

```text
Current price
      ↓
riwayat_harga
      ↓
Update produk
```

Store:

```text
produk_id
harga_beli
harga_jual
berlaku_sejak
user_id
```

Historical sales must retain their transaction price.

---

# 31. UI/UX

Primary principle:

> Database may be complex. UI must remain simple.

Use:

```text
Bootstrap 5.3
Blade
Livewire
Alpine.js
```

Operational language should be simple and familiar to shop staff.

---

# 32. MAIN NAVIGATION

Sidebar:

```text
Dashboard

Master Data
 ├── Produk
 ├── Kategori
 ├── Merek
 ├── Gudang
 ├── Pelanggan
 └── Supplier

Stok
 ├── Stok Saat Ini
 ├── Mutasi Stok
 ├── Transfer Gudang
 └── Stok Opname

Pembelian

Penjualan

Piutang

Produksi

Retur
 ├── Retur Supplier
 └── Retur Pelanggan

Laporan

Administrasi
 ├── User
 ├── Role & Permission
 ├── Riwayat Harga
 └── Activity Log
```

Menu visibility follows permissions.

---

# 33. DASHBOARD

Show useful operational KPIs:

```text
Penjualan hari ini
Pembelian hari ini
Total piutang
Produk stok menipis
Produk habis
Aktivitas terbaru
Stok per gudang
Estimasi nilai stok
```

Do not overload dashboard with unnecessary charts.

Tables and numbers have priority over charts.

---

# 34. STOCK STATUS

Statuses:

```text
AMAN
MENIPIS
HABIS
```

Based on:

```text
stok.jumlah
produk.stok_minimum
```

Low stock condition:

```text
stok.jumlah <= produk.stok_minimum
```

---

# 35. SALES UX

Sales should prioritize speed.

Support:

-   Catalog code search
-   Product search
-   Quantity
-   Price
-   Discount
-   Shipping
-   Payment
-   Customer

When stock is insufficient:

```text
Warning
+
Tetap Simpan
```

Do not hard-block according to current business rules.

---

# 36. PURCHASE UX

Purchase supports:

-   Supplier
-   Container number
-   Date
-   Product
-   Warehouse
-   Quantity
-   Purchase price

One purchase can distribute products to multiple warehouses.

Large purchases should support Excel/CSV import.

---

# 37. RECEIVABLE UX

Show:

```text
Customer
Total Sales
Paid
Outstanding
```

Invoice-level view must be available.

Payment allocation is manual.

Do not automatically apply FIFO.

---

# 38. REPORTING

Required reports:

### Sales

Filter:

```text
date
customer
warehouse
user
payment status
```

Show:

```text
total sales
transaction count
total paid
total receivable
```

### Purchases

Filter:

```text
date
supplier
warehouse
product
```

### Stock

Filter:

```text
warehouse
category
brand
product
status
```

### Mutation

Filter:

```text
date
product
warehouse
mutation type
user
```

### Receivables

Show:

```text
customer
total sales
paid
outstanding
```

Aging is NOT included until due-date business rules are defined.

### Payments

Show:

```text
payment number
date
customer
amount
method
user
allocations
```

### Production

Show:

```text
date
production number
product
quantity
warehouse
user
```

### Returns

Separate supplier and customer return reports.

---

# 39. STOCK VALUE

Estimated stock value:

```text
stock quantity × current product purchase price
```

Label clearly:

> Estimasi Nilai Stok

Do NOT represent this as formal accounting inventory valuation.

Do NOT implement formal profit/loss reporting until a costing/HPP methodology is explicitly defined.

---

# 40. EXPORT

Operational reports may support:

```text
Excel
CSV
```

PDF should be used where useful for:

-   Transaction documents
-   Selected reports
-   Printable documents

Do not make every table require PDF support.

---

# 41. SECURITY

Mandatory:

```text
Authentication
Authorization
CSRF protection
Password hashing
Rate limiting
Secure sessions
HTTPS production
Input validation
Mass-assignment protection
SQL injection protection
XSS protection
Secure file uploads
```

Production:

```text
APP_DEBUG=false
```

Secrets must never be committed to Git.

---

# 42. FILE UPLOAD

For Excel/CSV/PDF:

```text
Upload
 ↓
Validate file
 ↓
Parse
 ↓
Validate content
 ↓
Preview
 ↓
User confirmation
 ↓
Commit
```

Validate:

-   Extension
-   MIME
-   Size
-   Structure
-   Required columns
-   Data types
-   Business rules

---

# 43. DEPLOYMENT

Target:

```text
Hostinger
```

Production:

```text
Laravel 13
PHP 8.3
MySQL
Database Queue
Laravel Scheduler
Laravel Filesystem
```

Do not require:

```text
Redis
Docker
Microservices
VPS
Node server
```

unless explicitly introduced later.

---

# 44. QUEUE

Use:

```text
QUEUE_CONNECTION=database
```

Potential queued tasks:

-   Large imports
-   Report exports
-   Notifications
-   Document generation

Queue worker must be configured on production infrastructure.

Do not assume database queue automatically processes jobs.

---

# 45. SCHEDULER

Use Laravel Scheduler.

Server cron should invoke:

```text
php artisan schedule:run
```

Laravel determines which scheduled tasks should execute.

Do not create unnecessary individual cron jobs for every task.

---

# 46. BACKUP

Production data must be backed up.

Backup should include:

```text
Database
Important application files
Uploaded documents
```

Backups should be stored separately from the primary production data where practical.

Restore procedures must be tested.

---

# 47. TESTING

Use Pest.

Testing priority:

```text
Stock
Sales
Purchases
Payments
Receivables
Transfers
Returns
Stock Opname
Authorization
Document Numbers
Transaction Atomicity
Concurrency
```

---

# 48. CRITICAL TEST CASES

Must test:

```text
Purchase increases stock

Sale decreases stock

Sale with insufficient stock can continue after warning

Transfer decreases source and increases destination atomically

Production increases stock

Supplier return decreases stock

Customer return increases stock

Internal use decreases stock

Stock opname creates adjustment

Payment status:
0 → belum_bayar
partial → sebagian
full → lunas

Payment allocation cannot exceed payment

Payment allocation cannot exceed invoice balance

Historical transaction prices remain unchanged

Document numbers remain unique

Concurrent document generation does not duplicate numbers

Concurrent stock updates do not cause lost updates

Failed transactions rollback completely

Unauthorized actions are rejected server-side
```

---

# 49. DEFINITION OF DONE

A feature is NOT complete merely because its UI works.

A feature is complete when applicable:

```text
UI
Validation
Authorization
Business Logic
Database Integrity
Audit
Testing
Error Handling
Responsive UX
```

are complete.

Critical business features must have automated tests.

---

# 50. DEVELOPMENT RULES

Antigravity MUST:

1. Inspect existing project structure before creating files.
2. Reuse existing architecture when appropriate.
3. Avoid duplicate business logic.
4. Avoid unnecessary abstractions.
5. Use database transactions for multi-table business operations.
6. Use Form Requests/DTOs/Actions appropriately.
7. Keep Livewire components manageable.
8. Keep controllers thin.
9. Keep business rules out of Blade.
10. Never bypass StockService.
11. Never expose sensitive information through UI or Livewire payloads.
12. Never hardcode role checks where permissions should be used.
13. Use database constraints in addition to application validation.
14. Use proper indexes for frequently queried fields.
15. Prevent N+1 queries.
16. Use pagination for large datasets.
17. Preserve historical transaction data.
18. Prefer cancellation/reversal over destructive transaction deletion.
19. Write tests for critical business rules.
20. Do not introduce infrastructure complexity without clear need.

---

# 51. ABSOLUTE STOCK RULE

The following rule has highest priority:

> NEVER BYPASS THE STOCK ENGINE.

Forbidden:

```text
$stock->jumlah = ...
$stock->save()
```

from arbitrary application code.

Required:

```text
Business Action
→ StockService
→ DB Transaction
→ Stock Update
→ Mutation
```

Any implementation violating this rule must be considered incorrect.

---

# 52. IMPLEMENTATION PHILOSOPHY

Build incrementally.

Do NOT generate the entire application in one uncontrolled operation.

Use phases.

Each phase must:

```text
Implement
 ↓
Migrate
 ↓
Test
 ↓
Review
 ↓
Fix
 ↓
Proceed
```

Do not proceed to the next critical domain while the current domain has unresolved integrity issues.

---

# 53. FUTURE-READY BUT NOT OVERBUILT

The architecture should be capable of future expansion toward:

-   Multiple branches
-   More warehouses
-   More users
-   More product categories
-   More payment gateways
-   Advanced reporting
-   Barcode
-   Better notifications
-   More detailed production
-   More sophisticated costing
-   Additional business modules

However:

> Do not implement future features merely because the architecture can support them.

Build only what is currently required.

---

# 54. CURRENT OUT-OF-SCOPE ITEMS

Do NOT implement unless explicitly requested:

```text
Raw material production tracking
Formal accounting
Formal profit/loss
Advanced HPP costing
Automatic FIFO payment allocation
Microservices
Redis
Complex ERP workflow engine
Multi-branch operational implementation
Advanced barcode workflow
Mobile application
Payment gateway
AI features
```

These may be added later.

---

# 55. FINAL QUALITY PRINCIPLE

The system must prioritize:

```text
Correctness
>
Data Integrity
>
Security
>
Auditability
>
Maintainability
>
Operational Simplicity
>
Visual Sophistication
```

A beautiful interface with incorrect stock is unacceptable.

A simple interface with reliable transactions is preferable.

---

# END OF MASTER SPECIFICATION

PHASE 0
Project Foundation
├── Laravel setup
├── environment
├── database
├── base layout
├── authentication
└── testing foundation

PHASE 1
Master Data
├── kategori
├── merek
├── gudang
├── produk
├── supplier
└── pelanggan

PHASE 2
Inventory Core
├── stok
├── mutasi_stok
├── StockService
├── document number
└── stock dashboard

PHASE 3
Purchasing
├── pembelian
├── detail pembelian
└── stock integration

PHASE 4
Sales
├── penjualan
├── detail penjualan
├── discount
├── shipping
└── stock integration

PHASE 5
Payment & Receivables
├── pembayaran
├── alokasi
├── invoice status
└── piutang

PHASE 6
Transfer / Production / Returns
├── transfer
├── produksi
├── retur supplier
├── retur pelanggan
└── internal use

PHASE 7
Stock Opname
└── adjustment

PHASE 8
Authorization & Audit
├── roles
├── permissions
├── policies
└── activity log

PHASE 9
Reports & Dashboard

PHASE 10
Import / Export / Print

PHASE 11
Security Hardening & QA

PHASE 12
Production Deployment
