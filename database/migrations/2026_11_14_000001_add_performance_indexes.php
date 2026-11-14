<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected function safeAddIndex(string $table, array|string $columns, string $indexName): void
    {
        try {
            Schema::table($table, function (Blueprint $blueprint) use ($columns, $indexName) {
                $blueprint->index($columns, $indexName);
            });
        } catch (\Throwable $e) {
            // Ignore if index already exists from a previous partial run
        }
    }

    protected function safeDropIndex(string $table, string $indexName): void
    {
        try {
            Schema::table($table, function (Blueprint $blueprint) use ($indexName) {
                $blueprint->dropIndex($indexName);
            });
        } catch (\Throwable $e) {
            // Ignore if index does not exist
        }
    }

    public function up(): void
    {
        $this->safeAddIndex('inventory_items', ['is_active', 'current_quantity', 'minimum_stock_level'], 'idx_inv_items_active_qty');
        $this->safeAddIndex('inventory_items', ['category_id', 'created_at'], 'idx_inv_items_cat_created');

        $this->safeAddIndex('inventory_transactions', ['transaction_date', 'transaction_type'], 'idx_inv_tx_date_type');
        $this->safeAddIndex('inventory_transactions', ['user_id', 'transaction_date'], 'idx_inv_tx_user_date');
        $this->safeAddIndex('inventory_transactions', ['inventory_item_id', 'transaction_date'], 'idx_inv_tx_item_date');

        $this->safeAddIndex('purchases', 'purchase_date', 'idx_purchases_date');
        $this->safeAddIndex('purchases', ['supplier_id', 'purchase_date'], 'idx_purchases_supplier_date');

        $this->safeAddIndex('purchase_items', ['inventory_item_id', 'id'], 'idx_purchase_items_item_id');

        $this->safeAddIndex('audit_logs', 'created_at', 'idx_audit_logs_created');
        $this->safeAddIndex('audit_logs', ['action', 'created_at'], 'idx_audit_logs_action_created');

        $this->safeAddIndex('users', ['role_id', 'is_active'], 'idx_users_role_active');
    }

    public function down(): void
    {
        $this->safeDropIndex('inventory_items', 'idx_inv_items_active_qty');
        $this->safeDropIndex('inventory_items', 'idx_inv_items_cat_created');

        $this->safeDropIndex('inventory_transactions', 'idx_inv_tx_date_type');
        $this->safeDropIndex('inventory_transactions', 'idx_inv_tx_user_date');
        $this->safeDropIndex('inventory_transactions', 'idx_inv_tx_item_date');

        $this->safeDropIndex('purchases', 'idx_purchases_date');
        $this->safeDropIndex('purchases', 'idx_purchases_supplier_date');

        $this->safeDropIndex('purchase_items', 'idx_purchase_items_item_id');

        $this->safeDropIndex('audit_logs', 'idx_audit_logs_created');
        $this->safeDropIndex('audit_logs', 'idx_audit_logs_action_created');

        $this->safeDropIndex('users', 'idx_users_role_active');
    }
};
