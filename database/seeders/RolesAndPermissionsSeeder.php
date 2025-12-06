<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'view-ativos', 'create-ativos', 'edit-ativos', 'delete-ativos',
            'view-chamados', 'create-chamados', 'edit-chamados', 'close-chamados',
            'view-users', 'create-users', 'edit-users', 'delete-users',
            'view-departamentos', 'create-departamentos', 'edit-departamentos', 'delete-departamentos',
            'view-categorias', 'create-categorias', 'edit-categorias', 'delete-categorias',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }


        $roleComum = Role::firstOrCreate(['name' => 'Usuário Comum']);
        $roleComum->givePermissionTo(['view-chamados', 'create-chamados', 'close-chamados']);

        $roleEstagiario = Role::firstOrCreate(['name' => 'Estagiário']);
        $roleEstagiario->givePermissionTo(['view-ativos', 'create-ativos', 'edit-ativos',
            'view-chamados', 'create-chamados', 'edit-chamados', 'close-chamados',]);

        $roleTecnico = Role::firstOrCreate(['name' => 'Técnico de TI']);
        $roleTecnico->givePermissionTo([
            'view-ativos', 'create-ativos', 'edit-ativos',
            'view-chamados', 'create-chamados', 'edit-chamados', 'close-chamados',
        ]);

        $roleSupervisor = Role::firstOrCreate(['name' => 'Supervisor']);
        $roleSupervisor->givePermissionTo([
            'view-ativos', 'create-ativos', 'edit-ativos', 'delete-ativos',
            'view-chamados', 'create-chamados', 'edit-chamados', 'close-chamados',
            'view-departamentos', 'create-departamentos', 'edit-departamentos', 'delete-departamentos',
            'view-categorias', 'create-categorias', 'edit-categorias', 'delete-categorias',
        ]);

        $roleAdmin = Role::firstOrCreate(['name' => 'Admin']);
        $roleAdmin->givePermissionTo(Permission::all());
    }
}