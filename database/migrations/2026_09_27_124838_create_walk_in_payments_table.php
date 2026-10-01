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
        Schema::create('walk_in_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('walk_in_entry_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('status', 20)->default('pending')->index();
            $table->enum('method', ['cash'])->default('cash');
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('walk_in_payments');
    }
};
