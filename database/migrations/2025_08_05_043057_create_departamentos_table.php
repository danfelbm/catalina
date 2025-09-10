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
        Schema::create('departamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('territorio_id')->constrained('territorios')->onDelete('cascade');
            $table->string('nombre');
            $table->string('codigo', 10)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            
            $table->index(['territorio_id', 'nombre']);
            $table->index('activo');
            $table->unique(['territorio_id', 'nombre']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('departamentos');
    }
};
