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
        // Actualizar permisos del rol admin
        $adminRole = DB::table('roles')->where('name', 'admin')->first();
        
        if ($adminRole) {
            $permissions = json_decode($adminRole->permissions, true) ?? [];
            $allowedModules = json_decode($adminRole->allowed_modules, true) ?? [];
            
            // Añadir permisos de reportes de madurez
            $newPermissions = [
                'reportes-madurez.view',
                'reportes-madurez.create', 
                'reportes-madurez.edit',
                'reportes-madurez.delete',
                'reportes-madurez.export',
            ];
            
            foreach ($newPermissions as $permission) {
                if (!in_array($permission, $permissions)) {
                    $permissions[] = $permission;
                }
            }
            
            // Añadir módulo reportes-madurez
            if (!in_array('reportes-madurez', $allowedModules)) {
                $allowedModules[] = 'reportes-madurez';
            }
            
            DB::table('roles')
                ->where('id', $adminRole->id)
                ->update([
                    'permissions' => json_encode($permissions),
                    'allowed_modules' => json_encode($allowedModules),
                    'updated_at' => now(),
                ]);
        }
        
        // Actualizar permisos del rol manager
        $managerRole = DB::table('roles')->where('name', 'manager')->first();
        
        if ($managerRole) {
            $permissions = json_decode($managerRole->permissions, true) ?? [];
            $allowedModules = json_decode($managerRole->allowed_modules, true) ?? [];
            
            // Añadir permisos limitados de reportes de madurez para manager
            $newPermissions = [
                'reportes-madurez.view',
                'reportes-madurez.create',
                'reportes-madurez.edit',
                'reportes-madurez.export',
            ];
            
            foreach ($newPermissions as $permission) {
                if (!in_array($permission, $permissions)) {
                    $permissions[] = $permission;
                }
            }
            
            // Añadir módulo reportes-madurez
            if (!in_array('reportes-madurez', $allowedModules)) {
                $allowedModules[] = 'reportes-madurez';
            }
            
            DB::table('roles')
                ->where('id', $managerRole->id)
                ->update([
                    'permissions' => json_encode($permissions),
                    'allowed_modules' => json_encode($allowedModules),
                    'updated_at' => now(),
                ]);
        }
        
        // Nota: Los roles 'user' y 'end_customer' no necesitan acceso a reportes de madurez
        // ya que es un módulo exclusivamente administrativo para gestión interna
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remover permisos de reportes de madurez de todos los roles
        $roles = DB::table('roles')->get();
        
        foreach ($roles as $role) {
            $permissions = json_decode($role->permissions, true) ?? [];
            $allowedModules = json_decode($role->allowed_modules, true) ?? [];
            
            // Remover permisos de reportes de madurez
            $permissions = array_filter($permissions, function($permission) {
                return !str_starts_with($permission, 'reportes-madurez.');
            });
            
            // Remover módulo reportes-madurez
            $allowedModules = array_filter($allowedModules, function($module) {
                return $module !== 'reportes-madurez';
            });
            
            DB::table('roles')
                ->where('id', $role->id)
                ->update([
                    'permissions' => json_encode(array_values($permissions)),
                    'allowed_modules' => json_encode(array_values($allowedModules)),
                    'updated_at' => now(),
                ]);
        }
    }
};