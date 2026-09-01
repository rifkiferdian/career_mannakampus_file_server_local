<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Services\AuditService;

class UserController extends BaseController
{
    public function index(): string
    {
        return view('users/index', [
            'title' => 'Pengguna',
            'users' => (new UserModel())->orderBy('full_name')->findAll(),
        ]);
    }

    public function create()
    {
        $rules = [
            'username' => 'required|alpha_numeric|min_length[4]|max_length[50]|is_unique[users.username]',
            'full_name' => 'required|min_length[3]|max_length[120]',
            'email' => 'required|valid_email|max_length[190]|is_unique[users.email]',
            'role' => 'required|in_list[admin,hrd]',
            'password' => 'required|min_length[12]|max_length[72]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $password = (string) $this->request->getPost('password');
        if (! $this->strongPassword($password)) {
            return redirect()->back()->withInput()->with('error', 'Password harus berisi huruf besar, huruf kecil, angka, dan simbol.');
        }

        $id = (int) (new UserModel())->insert([
            'username' => mb_strtolower(trim((string) $this->request->getPost('username'))),
            'full_name' => trim((string) $this->request->getPost('full_name')),
            'email' => mb_strtolower(trim((string) $this->request->getPost('email'))),
            'role' => (string) $this->request->getPost('role'),
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'is_active' => 1,
            'must_change_password' => 1,
        ], true);
        (new AuditService())->record('user_created', 'Membuat pengguna ID ' . $id . '.');

        return redirect()->back()->with('success', 'Pengguna berhasil dibuat dan wajib mengganti password saat login pertama.');
    }

    public function toggleStatus(int $id)
    {
        $auth = (array) session('auth_user');
        if ($id === (int) $auth['id']) {
            return redirect()->back()->with('error', 'Anda tidak dapat menonaktifkan akun sendiri.');
        }
        $model = new UserModel();
        $user = $model->find($id);
        if ($user === null) {
            return redirect()->back()->with('error', 'Pengguna tidak ditemukan.');
        }
        $active = (int) $user['is_active'] === 1 ? 0 : 1;
        $model->update($id, ['is_active' => $active]);
        (new AuditService())->record('user_status_changed', sprintf('Akun %s diubah menjadi %s.', $user['username'], $active ? 'aktif' : 'nonaktif'));

        return redirect()->back()->with('success', 'Status pengguna berhasil diubah.');
    }

    public function resetPassword(int $id)
    {
        $model = new UserModel();
        $user = $model->find($id);
        if ($user === null) {
            return redirect()->back()->with('error', 'Pengguna tidak ditemukan.');
        }
        $password = 'Tmp#' . bin2hex(random_bytes(6)) . 'A1';
        $model->update($id, [
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'must_change_password' => 1,
        ]);
        (new AuditService())->record('user_password_reset', 'Mereset password pengguna ' . $user['username'] . '.');

        return redirect()->back()->with('temporary_password', ['username' => $user['username'], 'password' => $password]);
    }

    private function strongPassword(string $password): bool
    {
        return preg_match('/[A-Z]/', $password) === 1
            && preg_match('/[a-z]/', $password) === 1
            && preg_match('/\d/', $password) === 1
            && preg_match('/[^A-Za-z0-9]/', $password) === 1;
    }
}
