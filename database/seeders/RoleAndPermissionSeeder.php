<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'view_dashboard',

            // Master Data: Kategori
            'view_categories',
            'create_categories',
            'update_categories',
            'delete_categories',
            'restore_categories',

            // Master Data: Merek
            'view_brands',
            'create_brands',
            'update_brands',
            'delete_brands',
            'restore_brands',

            // Master Data: Gudang
            'view_warehouses',
            'create_warehouses',
            'update_warehouses',
            'delete_warehouses',
            'restore_warehouses',

            // Master Data: Supplier
            'view_suppliers',
            'create_suppliers',
            'update_suppliers',
            'delete_suppliers',
            'restore_suppliers',

            // Master Data: Pelanggan
            'view_customers',
            'create_customers',
            'update_customers',
            'delete_customers',
            'restore_customers',

            // Master Data: Produk
            'view_products',
            'create_products',
            'update_products',
            'delete_products',
            'restore_products',
            'view_purchase_prices',
            'update_product_prices',
            'view_price_history',

            // Stock / Inventory (Phase 2 & Phase 7)
            'view_stock',
            'view_stock_mutations',
            'transfer_stock',
            'perform_stock_opname',
            'adjust_stock',
            'view_stock_opnames',
            'create_stock_opnames',
            'update_stock_opnames',
            'cancel_stock_opnames',

            // Transfers (Phase 6)
            'view_transfers',
            'create_transfers',
            'update_transfers',
            'cancel_transfers',

            // Purchasing (Phase 3+ readiness)
            'view_purchases',
            'create_purchases',
            'update_purchases',
            'cancel_purchases',
            'import_purchases',

            // Sales (Phase 3+ readiness)
            'view_sales',
            'create_sales',
            'update_sales',
            'cancel_sales',
            'apply_sales_discount',
            'override_sales_price',

            // Payments (Phase 3+ readiness)
            'view_payments',
            'record_payments',
            'update_payments',
            'cancel_payments',
            'allocate_payments',

            // Receivables (Phase 3+ readiness)
            'view_receivables',
            'view_customer_debt',

            // Production (Phase 3+ readiness)
            'view_production',
            'create_production',
            'update_production',
            'cancel_production',

            // Returns (Phase 6)
            'view_supplier_returns',
            'create_supplier_returns',
            'cancel_supplier_returns',
            'view_customer_returns',
            'create_customer_returns',
            'cancel_customer_returns',

            // Administration & Audit (Phase 8)
            'view_users',
            'create_users',
            'update_users',
            'delete_users',
            'restore_users',
            'view_roles',
            'manage_roles',
            'view_activity_logs',

            // Reports & Dashboard (Phase 9)
            'view_reports',
            'view_stock_reports',
            'view_sales_reports',
            'view_purchase_reports',
            'view_receivable_reports',
            'view_payment_reports',
            'view_production_reports',
            'view_return_reports',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate(
                ['name' => $permissionName, 'guard_name' => 'web']
            );
        }

        // Roles definition
        $roles = [
            'Owner' => $permissions, // Owner has all permissions
            'Manager' => [
                'view_dashboard',
                // Master Data Full Management
                'view_categories',
                'create_categories',
                'update_categories',
                'delete_categories',
                'restore_categories',
                'view_brands',
                'create_brands',
                'update_brands',
                'delete_brands',
                'restore_brands',
                'view_warehouses',
                'create_warehouses',
                'update_warehouses',
                'delete_warehouses',
                'restore_warehouses',
                'view_suppliers',
                'create_suppliers',
                'update_suppliers',
                'delete_suppliers',
                'restore_suppliers',
                'view_customers',
                'create_customers',
                'update_customers',
                'delete_customers',
                'restore_customers',
                'view_products',
                'create_products',
                'update_products',
                'delete_products',
                'restore_products',
                'view_purchase_prices',
                'update_product_prices',
                'view_price_history',
                // Other operations
                'view_stock',
                'view_stock_mutations',
                'transfer_stock',
                'perform_stock_opname',
                'adjust_stock',
                'view_transfers',
                'create_transfers',
                'update_transfers',
                'cancel_transfers',
                'view_purchases',
                'create_purchases',
                'update_purchases',
                'import_purchases',
                'view_sales',
                'create_sales',
                'update_sales',
                'apply_sales_discount',
                'override_sales_price',
                'view_payments',
                'record_payments',
                'update_payments',
                'allocate_payments',
                'view_receivables',
                'view_customer_debt',
                'view_production',
                'create_production',
                'update_production',
                'cancel_production',
                'view_supplier_returns',
                'create_supplier_returns',
                'cancel_supplier_returns',
                'view_customer_returns',
                'create_customer_returns',
                'cancel_customer_returns',
                'view_users',
                'view_roles',
                'view_activity_logs',
                // Phase 9 Reports
                'view_reports',
                'view_stock_reports',
                'view_sales_reports',
                'view_purchase_reports',
                'view_receivable_reports',
                'view_payment_reports',
                'view_production_reports',
                'view_return_reports',
            ],
            'Staff Penjualan' => [
                'view_dashboard',
                'view_products',
                'view_categories',
                'view_brands',
                'view_warehouses',
                'view_customers',
                'create_customers',
                'update_customers',
                'view_stock',
                'view_sales',
                'create_sales',
                'update_sales',
                'view_payments',
                'record_payments',
                'allocate_payments',
                'view_receivables',
                'view_customer_debt',
                'view_customer_returns',
                'create_customer_returns',
                'view_reports',
                'view_stock_reports',
                'view_sales_reports',
                'view_receivable_reports',
                'view_payment_reports',
                'view_return_reports',
            ],
            'Staff Gudang' => [
                'view_dashboard',
                'view_products',
                'view_categories',
                'view_brands',
                'view_warehouses',
                'view_suppliers',
                'view_stock',
                'view_stock_mutations',
                'transfer_stock',
                'perform_stock_opname',
                'adjust_stock',
                'view_stock_opnames',
                'create_stock_opnames',
                'update_stock_opnames',
                'cancel_stock_opnames',
                'view_transfers',
                'create_transfers',
                'cancel_transfers',
                'view_purchases',
                'view_production',
                'view_supplier_returns',
                'create_supplier_returns',
                'view_customer_returns',
                'create_customer_returns',
                'view_reports',
                'view_stock_reports',
                'view_production_reports',
                'view_return_reports',
            ],
            'Staff Produksi' => [
                'view_dashboard',
                'view_products',
                'view_categories',
                'view_brands',
                'view_warehouses',
                'view_stock',
                'view_production',
                'create_production',
                'update_production',
                'view_reports',
                'view_stock_reports',
                'view_production_reports',
            ],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'web']
            );

            $role->syncPermissions($rolePermissions);
        }
    }
}
