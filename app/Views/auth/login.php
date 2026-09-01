<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Login HRD — Manna Kampus</title>
    <link rel="stylesheet" href="<?= base_url('assets/app.css') ?>">
</head>
<body class="login-page">
<main class="login-shell">
    <section class="login-intro">
        <span class="brand-mark large">MK</span>
        <p class="eyebrow">Manna Kampus Recruitment</p>
        <h1>Penyimpanan dokumen pelamar yang aman di jaringan lokal.</h1>
        <p>Masuk untuk menyinkronkan, memverifikasi, dan membuka dokumen yang tersimpan pada komputer ini.</p>
        <div class="security-note"><strong>Penyimpanan lokal</strong><span>Dokumen tidak tersedia tanpa autentikasi HRD.</span></div>
    </section>
    <section class="login-card">
        <div><p class="eyebrow">Akses terbatas</p><h2>Login HRD</h2><p>Gunakan akun yang diberikan administrator.</p></div>
        <?= view('partials/alerts') ?>
        <form method="post" action="<?= site_url('login') ?>" class="form-stack">
            <?= csrf_field() ?>
            <label>Username<input type="text" name="username" value="<?= esc(old('username')) ?>" maxlength="50" autocomplete="username" autofocus required></label>
            <label>Password<input type="password" name="password" maxlength="72" autocomplete="current-password" required></label>
            <button class="button primary wide" type="submit">Masuk ke aplikasi</button>
        </form>
        <small class="login-help">Lima percobaan gagal akan memblokir login selama 15 menit.</small>
    </section>
</main>
</body>
</html>
