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
            $table->decimal('walk_in_rating', 3, 2)->default(0)->after('years_experience')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('technician_verifications', function (Blueprint $table) {
            $table->dropIndex(['walk_in_rating']);
            $table->dropColumn('walk_in_rating');
        });
    }
};
