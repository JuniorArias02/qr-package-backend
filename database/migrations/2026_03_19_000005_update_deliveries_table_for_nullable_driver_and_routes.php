<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->foreignId('driver_id')->nullable()->change();
            $table->foreignId('route_id')
                ->nullable()
                ->after('daily_batch_id')
                ->constrained('routes')
                ->nullOnDelete();
        });

        // Modify enum values for status column
        DB::statement("ALTER TABLE deliveries MODIFY COLUMN status ENUM('pending_assignment', 'assigned', 'in_route', 'delivered', 'failed') NOT NULL DEFAULT 'pending_assignment'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE deliveries MODIFY COLUMN status ENUM('assigned', 'delivered') NOT NULL DEFAULT 'assigned'");

        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropForeign(['route_id']);
            $table->dropColumn('route_id');
            $table->foreignId('driver_id')->nullable(false)->change();
        });
    }
};
