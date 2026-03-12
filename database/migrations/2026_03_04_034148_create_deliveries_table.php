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
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained()->onDelete('cascade');
            $table->foreignId('driver_id')->constrained()->onDelete('cascade');
            $table->foreignId('coordinator_id')->constrained()->onDelete('cascade');
            $table->foreignId('daily_batch_id')->constrained()->onDelete('cascade');

            $table->integer('delivery_order');
            $table->string('qr_url')->nullable();

            $table->enum('status', ['assigned', 'delivered'])->default('assigned');
            $table->text('observation')->nullable();

            $table->dateTime('assigned_at')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};
