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
        // Lista de roles que deben tener el permiso de duplicación
        $rolesConPermiso = ['admin', 'manager', 'rol_ejemplo'];
        
        foreach ($rolesConPermiso as $roleName) {
            $role = DB::table('roles')->where('name', $roleName)->first();
            
            if ($role) {
                // Manejar tanto arrays como JSON strings
                $permissions = $role->permissions;
                if (is_string($permissions)) {
                    $permissions = json_decode($permissions, true) ?? [];
                } else {
                    // Si ya es un array, usarlo directamente
                    $permissions = (array) $permissions;
                }
                
                // Añadir permiso de duplicación si no existe
                if (!in_array('formularios.duplicate', $permissions)) {
                    $permissions[] = 'formularios.duplicate';
                    
                    // Guardar como JSON string para consistencia
                    DB::table('roles')
                        ->where('id', $role->id)
                        ->update([
                            'permissions' => json_encode($permissions),
                            'updated_at' => now(),
                        ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remover permiso de duplicación de todos los roles
        $roles = DB::table('roles')->get();
        
        foreach ($roles as $role) {
            $permissions = json_decode($role->permissions, true) ?? [];
            
            // Remover permiso de duplicación
            $permissions = array_filter($permissions, function($permission) {
                return $permission !== 'formularios.duplicate';
            });
            
            DB::table('roles')
                ->where('id', $role->id)
                ->update([
                    'permissions' => json_encode(array_values($permissions)),
                    'updated_at' => now(),
                ]);
        }
    }
};