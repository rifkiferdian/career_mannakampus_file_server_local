<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<?php if ($temp = session()->getFlashdata('temporary_password')): ?><div class="alert warning"><strong>Password sementara untuk <?= esc($temp['username']) ?>:</strong> <code><?= esc($temp['password']) ?></code><br>Simpan sekarang; password ini tidak ditampilkan kembali.</div><?php endif ?>
<div class="split-grid">
<div class="form-card">
    <p class="eyebrow">Akun baru</p><h2>Tambah pengguna</h2>
    <form method="post" action="<?= site_url('pengguna/buat') ?>" class="form-stack">
        <?= csrf_field() ?>
        <label>Nama lengkap<input name="full_name" value="<?= esc(old('full_name')) ?>" maxlength="120" required></label>
        <label>Username<input name="username" value="<?= esc(old('username')) ?>" minlength="4" maxlength="50" required></label>
        <label>Email<input type="email" name="email" value="<?= esc(old('email')) ?>" maxlength="190" required></label>
        <label>Peran<select name="role"><option value="hrd" <?= old('role') === 'hrd' ? 'selected' : '' ?>>HRD</option><option value="admin" <?= old('role') === 'admin' ? 'selected' : '' ?>>Admin</option></select></label>
        <label>Password sementara<input type="password" name="password" minlength="6" maxlength="72" required><small>Minimal 6 karakter.</small></label>
        <button class="button primary" type="submit">Buat pengguna</button>
    </form>
</div>
<div class="table-card">
<table><thead><tr><th>Pengguna</th><th>Peran</th><th>Status</th><th>Login terakhir</th><th class="right">Aksi</th></tr></thead><tbody>
<?php foreach ($users as $user): ?><tr><td><strong><?= esc($user['full_name']) ?></strong><small><?= esc($user['username']) ?> · <?= esc($user['email']) ?></small></td><td><span class="badge neutral"><?= esc(mb_strtoupper($user['role'])) ?></span></td><td><span class="badge <?= $user['is_active'] ? 'success' : 'failed' ?>"><?= $user['is_active'] ? 'Aktif' : 'Nonaktif' ?></span><?php if ($user['must_change_password']): ?><small>Wajib ganti password</small><?php endif ?></td><td><?= esc($user['last_login_at'] ?: 'Belum pernah') ?></td><td class="right"><div class="inline-actions"><form method="post" action="<?= site_url('pengguna/' . $user['id'] . '/reset-password') ?>"><?= csrf_field() ?><button class="button tiny ghost" type="submit">Reset password</button></form><form method="post" action="<?= site_url('pengguna/' . $user['id'] . '/status') ?>"><?= csrf_field() ?><button class="button tiny danger-outline" type="submit"><?= $user['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?></button></form></div></td></tr><?php endforeach ?>
</tbody></table>
</div>
</div>
<?= $this->endSection() ?>
