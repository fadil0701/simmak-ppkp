<?php

namespace Database\Seeders;

use App\Support\SimmakRoles;
use Illuminate\Database\Seeder;

class AuditPermissionSeeder extends Seeder
{
    public function run(): void
    {
        SimmakRoles::sync();
    }
}
