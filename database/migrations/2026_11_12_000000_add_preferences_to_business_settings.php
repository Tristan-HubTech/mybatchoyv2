<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_settings', function (Blueprint $table) {
            $table->string('logo_path')->nullable();
            $table->unsignedSmallInteger('session_timeout_minutes')->default(20);
            $table->decimal('default_minimum_stock_level', 12, 3)->default(0);
            $table->decimal('default_reorder_level', 12, 3)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('business_settings', function (Blueprint $table) {
            $table->dropColumn([
                'logo_path',
                'session_timeout_minutes',
                'default_minimum_stock_level',
                'default_reorder_level',
            ]);
        });
    }
};
