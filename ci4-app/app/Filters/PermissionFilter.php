<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class PermissionFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        helper('permissions');

        $requiredPermissions = is_array($arguments) ? $arguments : [];

        foreach ($requiredPermissions as $permission) {
            if (can_access((string) $permission)) {
                return null;
            }
        }

        return redirect()->to('/')->with('error', 'You do not have permission to access that page.');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
