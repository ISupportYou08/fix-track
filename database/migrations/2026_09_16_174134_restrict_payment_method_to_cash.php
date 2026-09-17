<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('alter table payments alter column method type varchar(30) using method::varchar(30)');
            DB::statement("alter table payments alter column method set default 'cash'");
            DB::statement('alter table payments alter column method set not null');
            DB::statement("alter table payments add constraint payments_method_cash_check check (method = 'cash')");

            return;
        }

        Schema::table('payments', function (Blueprint $table): void {
            $table->enum('method', ['cash'])->default('cash')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('alter table payments drop constraint if exists payments_method_cash_check');

            return;
        }

        Schema::table('payments', function (Blueprint $table): void {
            $table->string('method', 30)->default('cash')->change();
        });
    }
};
