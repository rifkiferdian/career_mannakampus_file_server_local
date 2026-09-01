<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! session()->has('auth_user')) {
            session()->set('intended_url', current_url());

            return redirect()->to(site_url('login'))->with('error', 'Silakan login untuk melanjutkan.');
        }

        $user = (array) session('auth_user');
        $path = trim($request->getUri()->getPath(), '/');
        $isPasswordRoute = str_ends_with($path, 'akun/password');
        $isLogoutRoute = str_ends_with($path, 'logout');
        if (($user['must_change_password'] ?? false) && ! $isPasswordRoute && ! $isLogoutRoute) {
            return redirect()->to(site_url('akun/password'))->with('warning', 'Ganti password sementara sebelum menggunakan aplikasi.');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): void
    {
    }
}
