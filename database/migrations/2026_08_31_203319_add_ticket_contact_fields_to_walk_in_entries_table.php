<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('walk_in_entries', function (Blueprint $table) {
            $table->string('reference')->nullable()->unique()->after('technician_id');
            $table->string('customer_email')->nullable()->after('customer_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('walk_in_entries', function (Blueprint $table) {
            $table->dropUnique(['reference']);
            $table->dropColumn(['reference', 'customer_email']);
        });
    }
};
