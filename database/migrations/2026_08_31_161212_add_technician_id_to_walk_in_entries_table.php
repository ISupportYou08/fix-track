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
        if (! Schema::hasTable('walk_in_entries') || Schema::hasColumn('walk_in_entries', 'technician_id')) {
            return;
        }

        Schema::table('walk_in_entries', function (Blueprint $table) {
            $table->foreignId('technician_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('walk_in_entries') || ! Schema::hasColumn('walk_in_entries', 'technician_id')) {
            return;
        }

        Schema::table('walk_in_entries', function (Blueprint $table) {
            $table->dropForeign(['technician_id']);
            $table->dropIndex(['technician_id']);
            $table->dropColumn('technician_id');
        });
    }
};
