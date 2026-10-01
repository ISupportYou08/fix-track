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
        DB::table('users')
            ->where('role', 'staff')
            ->where('email', 'admin@fixtrack.test')
            ->update([
                'name' => 'Sample Staff',
                'email' => 'staff@fixtrack.test',
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('users')
            ->where('role', 'staff')
            ->where('email', 'staff@fixtrack.test')
            ->where('name', 'Sample Staff')
            ->update([
                'name' => 'Sample Administrator',
                'email' => 'admin@fixtrack.test',
            ]);
    }
};
