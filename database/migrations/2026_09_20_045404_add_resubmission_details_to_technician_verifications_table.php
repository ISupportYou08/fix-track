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
        Schema::table('technician_verifications', function (Blueprint $table) {
            $table->text('resubmission_notes')->nullable()->after('decision_reason');
            $table->unsignedTinyInteger('resubmission_count')->default(0)->after('resubmission_notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('technician_verifications', function (Blueprint $table) {
            $table->dropColumn(['resubmission_notes', 'resubmission_count']);
        });
    }
};
