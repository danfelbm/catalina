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
        // Tabla de categorías de madurez
        Schema::create('reporte_madurez_categorias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre'); // Ej: "CUESTIONES ESTRATÉGICAS"
            $table->string('codigo')->unique(); // Ej: "cuestiones_estrategicas"
            $table->integer('orden'); // Para ordenar las categorías
            $table->string('color')->default('#e5e7eb'); // Color hexadecimal para UI
            $table->timestamps();
        });

        // Tabla de elementos por categoría
        Schema::create('reporte_madurez_elementos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categoria_id')->constrained('reporte_madurez_categorias')->onDelete('cascade');
            $table->integer('numero'); // Número del elemento (1, 2, 3, etc.)
            $table->string('nombre'); // Ej: "Operación equilibrada"
            $table->integer('orden'); // Para ordenar elementos dentro de la categoría
            $table->timestamps();
        });

        // Tabla principal de reportes de madurez
        Schema::create('reporte_madurez', function (Blueprint $table) {
            $table->id();
            $table->string('empresa');
            $table->string('ciudad');
            $table->string('centro_trabajo');
            $table->string('area');
            $table->date('fecha_realizacion');
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            // Índices para rendimiento
            $table->index(['tenant_id', 'created_by']);
            $table->index('fecha_realizacion');
        });

        // Tabla de evaluaciones (checkboxes marcados)
        Schema::create('reporte_madurez_evaluaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporte_id')->constrained('reporte_madurez')->onDelete('cascade');
            $table->foreignId('elemento_id')->constrained('reporte_madurez_elementos')->onDelete('cascade');
            $table->enum('nivel', ['emergente', 'resolutivo', 'laborioso', 'cooperativo', 'progresivo']);
            $table->timestamps();

            // Constraint único: un elemento solo puede tener un nivel evaluado por reporte
            $table->unique(['reporte_id', 'elemento_id'], 'reporte_elemento_unique');
            
            // Índices para rendimiento
            $table->index(['reporte_id', 'nivel']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reporte_madurez_evaluaciones');
        Schema::dropIfExists('reporte_madurez');
        Schema::dropIfExists('reporte_madurez_elementos');
        Schema::dropIfExists('reporte_madurez_categorias');
    }
};
