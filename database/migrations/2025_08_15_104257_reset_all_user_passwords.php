<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Actualizar contraseña de todos los usuarios a 159753456
        $hashedPassword = Hash::make('159753456');
        
        DB::table('users')->update([
            'password' => $hashedPassword,
            'updated_at' => now()
        ]);
        
        // Log para confirmar la operación
        \Log::info('Contraseñas de todos los usuarios actualizadas a 159753456', [
            'affected_users' => DB::table('users')->count(),
            'timestamp' => now()
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No es posible revertir contraseñas hasheadas
        // Esta migración es irreversible por seguridad
        throw new Exception('Esta migración no puede ser revertida por seguridad.');
    }
};
