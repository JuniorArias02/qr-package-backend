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
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('referencia_qr');
            $table->string('direccion');
            $table->string('nombre_receptor');
            $table->string('telefono_receptor');
            $table->string('estado');
            $table->unsignedBigInteger('user_id_asignado')->nullable();
            $table->timestamps();

            $table->foreign('user_id_asignado')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
