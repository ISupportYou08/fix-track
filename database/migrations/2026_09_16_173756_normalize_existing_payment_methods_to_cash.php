<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('payments')->update(['method' => 'cash']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        /**
         * Previous payment methods cannot be restored after normalization.
         */
    }
};
