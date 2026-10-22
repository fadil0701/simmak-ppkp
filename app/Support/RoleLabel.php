<?php

namespace App\Support;

class RoleLabel
{
    public static function for(?string $role): string
    {
        return SimmakRoles::label($role);
    }
}
