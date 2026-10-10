<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('routes', function (Blueprint $table) {
            $table->geometry('polygon')->nullable()->after('active');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->cascadeOnDelete()->after('company_id');
        });
    }

    public function down(): void
    {
        Schema::table('routes', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn(['polygon', 'branch_id']);
        });
    }
};
