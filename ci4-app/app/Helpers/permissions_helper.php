<?php

if (! function_exists('current_user_role')) {
    function current_user_role(): string
    {
        return strtolower(trim((string) (session('type') ?? '')));
    }
}

if (! function_exists('can_access')) {
    function can_access(string $permission): bool
    {
        $role = current_user_role();
        $matrix = config(\Config\Permissions::class)->matrix;
        $grants = $matrix[$role] ?? [];

        return in_array('*', $grants, true) || in_array($permission, $grants, true);
    }
}
