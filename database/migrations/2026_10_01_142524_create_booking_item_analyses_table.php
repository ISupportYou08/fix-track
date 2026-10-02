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
        Schema::create('booking_item_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('image_path');
            $table->string('image_original_name');
            $table->string('image_mime_type', 100);
            $table->string('detected_item_name', 120);
            $table->string('suggested_service_code')->index();
            $table->string('suggested_service_category');
            $table->decimal('confidence', 5, 4);
            $table->text('explanation')->nullable();
            $table->string('model');
            $table->timestamp('confirmed_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_item_analyses');
    }
};
