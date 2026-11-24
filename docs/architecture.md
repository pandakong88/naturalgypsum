# Architecture & Foundation Guide — Natural Gypsum & PVC

This document outlines the software architecture, technical decisions, and coding standards for the **Natural Gypsum & PVC Inventory & Operations Management System**.

---

## 1. Architectural Philosophy

The application is architected as a **Pragmatic Modular Monolith + Domain-Oriented Structure**.

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

### Guiding Rules
- **No Over-Engineering**: Avoid microservices, Redis requirements, event sourcing, or excessive repository abstractions.
- **Shared Models**: Core Laravel models (such as `User`) remain in `app/Models/User.php`.
- **Domain Focus**: Business logic, actions, DTOs, domain models, and domain services live in their respective domain namespaces under `app/Domains/`.

---

## 2. Request & Execution Flow

```text
Browser
  ↓
Blade / Livewire Component
  ↓
Form Request / Component Validation
  ↓
Action / Command DTO
  ↓
Domain Service
  ↓
DB Transaction / Model / Mutasi Stok Ledger
```

- **Livewire Components** remain thin, handling UI state, pagination, filtering, and delegating business logic to domain Actions or Services.
- **Database Transactions** ensure atomicity for multi-step mutations (e.g. sales checkout + stock deduction + mutasi_stok ledger creation).

---

## 3. Authorization & Permissions (RBAC)

The system uses `spatie/laravel-permission` for role-based access control.

### Core Structure
```text
User  ──(assignRole)──>  Role  ──(givePermissionTo)──>  Permissions
```

### Default Roles (Seeded Idempotently)
1. `Owner` — Full administrative & business operations privileges.
2. `Manager` — Supervisory privileges, price overrides, stock opname approval.
3. `Staff Penjualan` — POS, sales invoices, payment recording, receivables viewing.
4. `Staff Gudang` — Inbound receiving, stock opname, warehouse transfers.
5. `Staff Produksi` — Production output recording.

### Strict Rules
- **Never Hardcode Roles**: Never write checks like `if ($user->role === 'owner')`.
- **Check Permissions**: Always verify granular permissions, e.g., `$user->can('view_purchase_prices')` or `@can('create_sales')`.
- **Backend Enforcement**: Hiding a UI button or sidebar menu does not replace backend authorization.

---

## 4. Stock & Inventory Rules (Phase 2+)

1. **Transaction-Driven Stock**: Stock amounts are never modified directly via arbitrary updates.
2. **Mutasi Stok Ledger**: Every stock change creates a corresponding `mutasi_stok` record linking to the source document (`referensi`, `referensi_type`, `referensi_id`).
3. **Dedicated Service**: All mutations must go through `StockService`.

---

## 5. Database Conventions

| Attribute | Convention | Example |
|---|---|---|
| **Naming** | `snake_case` (tables & columns) | `produk_id`, `panjang_per_roll` |
| **Financial Values** | `DECIMAL(15,2)` | `harga_beli`, `total_akhir` |
| **Quantities** | `DECIMAL(15,3)` | `jumlah`, `stok_minimum` |
| **Deletions** | Soft deletes for master & transaction entities | `deleted_at` timestamp |
| **Units** | PVC vinyl in `meter` (roll = conversion), Board in `lembar`, Moulding in `batang` | Base units stored in stock |

---

## 6. Frontend Stack

- **Template Engine**: Laravel Blade
- **CSS Framework**: Bootstrap 5.3.x + Bootstrap Icons (compiled via Vite + Sass)
- **Reactivity**: Livewire 4.x (manages Alpine.js internally without duplicate instances)
- **UI Responsiveness**: Standard mobile-friendly collapsible sidebar and clean business layout.

---

## 7. Testing Strategy

- **Test Framework**: Pest 4.x (`tests/Feature/`, `tests/Unit/`)
- **Database Isolation**: In-memory SQLite during testing with `RefreshDatabase`
- **Coverage Focus**: Authentication, permission gates, transactional stock invariants, sales/purchasing calculations.
