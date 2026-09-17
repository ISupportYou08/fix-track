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
            $table->string('payment_method', 20)->default('cash')->after('status');
            $table->timestamp('cancelled_at')->nullable()->after('completed_at');
            $table->foreignId('cancelled_by')->nullable()->after('cancelled_at')->constrained('users')->nullOnDelete();
            $table->text('cancellation_reason')->nullable()->after('cancelled_by');
            $table->decimal('customer_latitude', 10, 7)->nullable()->after('cancellation_reason');
            $table->decimal('customer_longitude', 10, 7)->nullable()->after('customer_latitude');
            $table->boolean('location_sharing_enabled')->default(false)->after('customer_longitude');
            $table->timestamp('location_updated_at')->nullable()->after('location_sharing_enabled');
            $table->index(['technician_id', 'status', 'checked_in_at'], 'walk_in_entries_technician_status_joined_index');
            $table->index(['status', 'location_sharing_enabled'], 'walk_in_entries_status_location_sharing_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('walk_in_entries', function (Blueprint $table) {
            $table->dropIndex('walk_in_entries_technician_status_joined_index');
            $table->dropIndex('walk_in_entries_status_location_sharing_index');
            $table->dropConstrainedForeignId('cancelled_by');
            $table->dropColumn([
                'payment_method',
                'cancelled_at',
                'cancellation_reason',
                'customer_latitude',
                'customer_longitude',
                'location_sharing_enabled',
                'location_updated_at',
            ]);
        });
    }
};
