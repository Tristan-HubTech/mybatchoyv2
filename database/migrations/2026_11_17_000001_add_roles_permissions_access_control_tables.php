<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'supplier_id')) {
                $table->foreignId('supplier_id')
                    ->nullable()
                    ->after('role_id')
                    ->constrained('suppliers')
                    ->nullOnDelete();
            }
        });

        Schema::table('purchases', function (Blueprint $table) {
            if (!Schema::hasColumn('purchases', 'fulfillment_mode')) {
                $table->string('fulfillment_mode', 24)->default('direct_received')->after('notes');
                $table->string('supplier_status', 24)->default('confirmed')->after('fulfillment_mode');
                $table->string('delivery_status', 32)->default('delivered')->after('supplier_status');
                $table->string('invoice_number', 100)->nullable()->after('delivery_status');
                $table->text('delivery_notes')->nullable()->after('invoice_number');
                $table->timestamp('supplier_responded_at')->nullable()->after('delivery_notes');
                $table->timestamp('delivered_at')->nullable()->after('supplier_responded_at');
                $table->boolean('received_in_inventory')->default(true)->after('delivered_at');
            }
        });

        Schema::create('business_access_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('system_admin_id')->constrained('users')->cascadeOnDelete();
            $table->string('scope', 40);
            $table->text('reason');
            $table->unsignedSmallInteger('duration_minutes')->default(30);
            $table->string('status', 24)->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamps();

            $table->index(['system_admin_id', 'status', 'expires_at'], 'bar_admin_status_exp_idx');
        });

        Schema::create('stock_adjustment_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->string('request_type', 24)->default('adjustment');
            $table->decimal('previous_quantity', 12, 3)->default(0);
            $table->decimal('counted_quantity', 12, 3)->default(0);
            $table->string('reason', 255);
            $table->string('status', 24)->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('review_notes', 255)->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at'], 'sar_status_created_idx');
        });

        Schema::create('technical_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('maintenance_mode')->default(false);
            $table->string('maintenance_notice', 255)->nullable();
            $table->unsignedSmallInteger('log_retention_days')->default(90);
            $table->unsignedTinyInteger('security_lockout_attempts')->default(5);
            $table->unsignedSmallInteger('security_lockout_minutes')->default(15);
            $table->unsignedSmallInteger('api_rate_limit_per_minute')->default(60);
            $table->unsignedSmallInteger('backup_retention_days')->default(30);
            $table->boolean('owner_can_edit_technical')->default(false);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('technical_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('category', 32)->default('technical');
            $table->string('action', 100);
            $table->string('target_type')->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->text('details');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamps();

            $table->index(['category', 'created_at'], 'tal_cat_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('technical_audit_logs');
        Schema::dropIfExists('technical_settings');
        Schema::dropIfExists('stock_adjustment_requests');
        Schema::dropIfExists('business_access_requests');

        Schema::table('purchases', function (Blueprint $table) {
            $cols = [
                'fulfillment_mode',
                'supplier_status',
                'delivery_status',
                'invoice_number',
                'delivery_notes',
                'supplier_responded_at',
                'delivered_at',
                'received_in_inventory',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('purchases', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'supplier_id')) {
                $table->dropConstrainedForeignId('supplier_id');
            }
        });
    }
};

