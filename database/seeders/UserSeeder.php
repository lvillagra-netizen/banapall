<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('BANAPAL_ADMIN_PASSWORD') ?: 'Banapal2026!';

        $users = [
            ['name' => 'Administrador BANAPALL', 'email' => 'admin@banapal.cl', 'role' => Role::Admin, 'phone' => '+56 9 1111 1111'],
            ['name' => 'Valentina Rojas', 'email' => 'ventas@banapal.cl', 'role' => Role::Ventas, 'phone' => '+56 9 2222 2222'],
            ['name' => 'Matías Fuentes', 'email' => 'inventario@banapal.cl', 'role' => Role::Inventario, 'phone' => '+56 9 3333 3333'],
            ['name' => 'Camila Herrera', 'email' => 'contabilidad@banapal.cl', 'role' => Role::Contabilidad, 'phone' => '+56 9 4444 4444'],
        ];

        foreach ($users as $data) {
            $user = User::withTrashed()->firstOrNew(['email' => $data['email']]);
            $user->fill([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'is_active' => true,
            ]);

            if (! $user->exists || ! $user->password) {
                $user->password = $password;
            }

            $user->deleted_at = null;
            $user->save();
            $user->syncRoles([$data['role']->value]);
        }
    }
}
