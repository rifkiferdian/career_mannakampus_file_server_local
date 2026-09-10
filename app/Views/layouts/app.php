<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title><?= esc($title ?? 'File Server Lokal') ?> — Manna Kampus</title>
    <link rel="stylesheet" href="<?= base_url('assets/app.css') ?>?v=<?= (int) @filemtime(FCPATH . 'assets/app.css') ?>">
</head>
<body>
<?php $auth = (array) session('auth_user'); $path = trim(uri_string(), '/'); ?>
<div class="app-shell">
    <aside class="sidebar">
        <a class="brand" href="<?= site_url('/') ?>">
            <span class="brand-mark">MK</span>
            <span><strong>File Server</strong><small>Recruitment lokal</small></span>
        </a>
        <nav>
            <a class="<?= $path === '' ? 'active' : '' ?>" href="<?= site_url('/') ?>">Dashboard</a>
            <a class="<?= str_starts_with($path, 'dokumen') ? 'active' : '' ?>" href="<?= site_url('dokumen') ?>">Dokumen pelamar</a>
            <a class="<?= $path === 'riwayat/transfer' ? 'active' : '' ?>" href="<?= site_url('riwayat/transfer') ?>">Riwayat transfer</a>
            <a class="<?= $path === 'riwayat/aktivitas' ? 'active' : '' ?>" href="<?= site_url('riwayat/aktivitas') ?>">Log aktivitas</a>
            <?php if (($auth['role'] ?? '') === 'admin'): ?>
                <a class="<?= str_starts_with($path, 'pengguna') ? 'active' : '' ?>" href="<?= site_url('pengguna') ?>">Pengguna</a>
            <?php endif ?>
        </nav>
        <div class="sidebar-user">
            <span class="avatar"><?= esc(mb_strtoupper(mb_substr((string) ($auth['full_name'] ?? 'U'), 0, 1))) ?></span>
            <div><strong><?= esc($auth['full_name'] ?? '') ?></strong><small><?= esc(mb_strtoupper((string) ($auth['role'] ?? ''))) ?></small></div>
        </div>
    </aside>
    <main class="main">
        <header class="topbar">
            <div><p class="eyebrow">Manna Kampus Recruitment</p><h1><?= esc($title ?? '') ?></h1></div>
            <div class="top-actions">
                <a class="button ghost" href="<?= site_url('akun/password') ?>">Ganti password</a>
                <form action="<?= site_url('logout') ?>" method="post"><?= csrf_field() ?><button class="button danger-outline" type="submit">Logout</button></form>
            </div>
        </header>
        <section class="content">
            <?= $this->include('partials/alerts') ?>
            <?= $this->renderSection('content') ?>
        </section>
    </main>
</div>
</body>
</html>
