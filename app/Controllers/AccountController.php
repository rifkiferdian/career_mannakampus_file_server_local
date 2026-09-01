<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Services\AuditService;

class AccountController extends BaseController
{
    public function password()
    {
        if ($this->request->getMethod() !== 'POST') {
            return view('account/password', ['title' => 'Ganti Password']);
        }

        $rules = [
            'current_password' => 'required',
            'new_password' => 'required|min_length[6]|max_length[72]',
            'password_confirmation' => 'required|matches[new_password]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $auth = (array) session('auth_user');
        $model = new UserModel();
        $user = $model->find((int) $auth['id']);
        if ($user === null || ! password_verify((string) $this->request->getPost('current_password'), $user['password_hash'])) {
            return redirect()->back()->with('error', 'Password saat ini tidak benar.');
        }

        $newPassword = (string) $this->request->getPost('new_password');

        $model->update((int) $auth['id'], [
            'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
            'must_change_password' => 0,
        ]);
        $auth['must_change_password'] = false;
        session()->set('auth_user', $auth);
        (new AuditService())->record('password_changed', 'Pengguna mengganti password.');

        return redirect()->to(site_url('/'))->with('success', 'Password berhasil diganti.');
    }
}
