<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<div class="form-card narrow">
    <p class="eyebrow">Keamanan akun</p><h2>Ganti password</h2>
    <p>Password minimal 12 karakter dan berisi huruf besar, huruf kecil, angka, serta simbol.</p>
    <form method="post" action="<?= site_url('akun/password') ?>" class="form-stack">
        <?= csrf_field() ?>
        <label>Password saat ini<input type="password" name="current_password" autocomplete="current-password" required></label>
        <label>Password baru<input type="password" name="new_password" minlength="12" maxlength="72" autocomplete="new-password" required></label>
        <label>Ulangi password baru<input type="password" name="password_confirmation" minlength="12" maxlength="72" autocomplete="new-password" required></label>
        <button class="button primary" type="submit">Simpan password baru</button>
    </form>
</div>
<?= $this->endSection() ?>
