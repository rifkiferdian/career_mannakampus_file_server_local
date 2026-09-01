<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AdminFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $user = (array) session('auth_user');
        if ($user === []) {
            return redirect()->to(site_url('login'));
        }
        if (($user['must_change_password'] ?? false)) {
            return redirect()->to(site_url('akun/password'));
        }
        if (($user['role'] ?? '') !== 'admin') {
            return redirect()->to(site_url('/'))->with('error', 'Menu tersebut hanya dapat diakses admin.');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): void
    {
    }
}
