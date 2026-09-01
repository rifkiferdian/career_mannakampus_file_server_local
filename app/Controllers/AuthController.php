<?php

namespace App\Controllers;

use App\Models\LoginAttemptModel;
use App\Models\UserModel;
use App\Services\AuditService;

class AuthController extends BaseController
{
    private const MAX_ATTEMPTS = 5;
    private const LOCK_MINUTES = 15;

    public function login()
    {
        if ($this->request->getMethod() !== 'POST') {
            return view('auth/login', ['title' => 'Login HRD']);
        }

        $username = mb_strtolower(trim((string) $this->request->getPost('username')));
        $ip = $this->request->getIPAddress();
        if ($this->isLocked($username, $ip)) {
            return redirect()->back()->withInput()->with('error', 'Terlalu banyak percobaan gagal. Coba lagi setelah 15 menit.');
        }

        $user = (new UserModel())->where('username', $username)->first();
        $valid = $user !== null
            && (int) $user['is_active'] === 1
            && password_verify((string) $this->request->getPost('password'), (string) $user['password_hash']);
        $this->recordAttempt($username, $ip, $valid);

        if (! $valid) {
            return redirect()->back()->withInput()->with('error', 'Username atau password tidak benar.');
        }
        (new LoginAttemptModel())
            ->where('username', $username)
            ->where('ip_address', $ip)
            ->where('is_success', 0)
            ->delete();

        session()->regenerate(true);
        session()->set('auth_user', [
            'id' => (int) $user['id'],
            'username' => $user['username'],
            'full_name' => $user['full_name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'must_change_password' => (bool) $user['must_change_password'],
        ]);
        (new UserModel())->update((int) $user['id'], ['last_login_at' => date('Y-m-d H:i:s')]);
        (new AuditService())->record('login_success', 'Pengguna berhasil login.');

        return redirect()->to(site_url('/'));
    }

    public function logout()
    {
        (new AuditService())->record('logout', 'Pengguna keluar dari aplikasi.');
        session()->destroy();

        return redirect()->to(site_url('login'))->with('success', 'Anda telah logout.');
    }

    private function isLocked(string $username, string $ip): bool
    {
        if ($username === '') {
            return false;
        }

        return (new LoginAttemptModel())
            ->where('username', $username)
            ->where('ip_address', $ip)
            ->where('is_success', 0)
            ->where('attempted_at >=', date('Y-m-d H:i:s', time() - self::LOCK_MINUTES * 60))
            ->countAllResults() >= self::MAX_ATTEMPTS;
    }

    private function recordAttempt(string $username, string $ip, bool $success): void
    {
        (new LoginAttemptModel())->insert([
            'username' => $username !== '' ? $username : '(kosong)',
            'ip_address' => $ip,
            'user_agent' => mb_substr((string) $this->request->getUserAgent(), 0, 500),
            'is_success' => $success ? 1 : 0,
            'attempted_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
