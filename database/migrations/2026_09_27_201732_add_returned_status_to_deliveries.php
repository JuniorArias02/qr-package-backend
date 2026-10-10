<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE deliveries MODIFY COLUMN status ENUM('assigned', 'delivered', 'pending_assignment', 'returned') DEFAULT 'assigned'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE deliveries MODIFY COLUMN status ENUM('assigned', 'delivered', 'pending_assignment') DEFAULT 'assigned'");
    }
};
