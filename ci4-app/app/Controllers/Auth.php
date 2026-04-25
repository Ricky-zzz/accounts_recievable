<?php

namespace App\Controllers;

use App\Models\UserModel;

class Auth extends BaseController
{
    public function login(): string
    {
        return view('auth/login');
    }

    public function attempt()
    {
        $rules = [
            'username' => 'required',
            'password' => 'required',
        ];

        if (! $this->validate($rules)) {
            return view('auth/login', [
                'validation' => $this->validator,
            ]);
        }

        $username = trim((string) $this->request->getPost('username'));
        $password = (string) $this->request->getPost('password');

        $userModel = new UserModel();
        $user = $userModel
            ->where('username', $username)
            ->where('is_active', 1)
            ->first();

        if (! $user || ! password_verify($password, $user['password_hash'])) {
            return redirect()->back()->withInput()->with('error', 'Invalid username or password.');
        }

        session()->regenerate(true);
        session()->set([
            'user_id' => $user['id'],
            'name' => $user['name'] ?? '',
            'username' => $user['username'],
            'type' => $user['type'] ?? 'cashier',
        ]);

        return redirect()->to('/clients');
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to('/login');
    }
}
