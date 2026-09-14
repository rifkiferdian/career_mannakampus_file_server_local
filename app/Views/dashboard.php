<?= $this->extend('layouts/app') ?>
<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/dashboard-storage.css') ?>?v=1">
<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php if (! $remoteConfigured): ?>
    <div class="connection-banner"><div><strong>Koneksi hosting belum dikonfigurasi</strong><span>Isi <code>remoteStorage.baseUrl</code>, <code>remoteStorage.clientId</code>, dan <code>remoteStorage.secret</code> pada file .env.</span></div><span class="status-dot offline">Offline</span></div>
<?php else: ?>
    <div class="connection-banner connected"><div><strong>Konfigurasi hosting tersedia</strong><span>Aplikasi siap meminta daftar dokumen dari API recruitment.</span></div><span class="status-dot online">Siap</span></div>
<?php endif ?>
<div class="stats-grid">
    <article class="stat"><span>Total dokumen</span><strong><?= number_format($stats['total']) ?></strong><small>Metadata tersimpan</small></article>
    <article class="stat amber"><span>Menunggu download</span><strong><?= number_format($stats['pending']) ?></strong><small>Belum disimpan lokal</small></article>
    <article class="stat green"><span>Tersimpan lokal</span><strong><?= number_format($stats['completed']) ?></strong><small>Checksum terverifikasi</small></article>
    <article class="stat red"><span>Gagal</span><strong><?= number_format($stats['failed']) ?></strong><small>Perlu dicoba kembali</small></article>
</div>
<?= view('partials/dashboard_storage', ['diskUsage' => $diskUsage]) ?>
<div class="panel-heading"><div><p class="eyebrow">Aktivitas terbaru</p><h2>Transfer dokumen</h2></div><a class="button ghost" href="<?= site_url('riwayat/transfer') ?>">Lihat semua</a></div>
<div class="table-card">
    <table><thead><tr><th>Waktu</th><th>Pelamar / File</th><th>Aksi</th><th>Status</th><th>Ukuran</th></tr></thead><tbody>
    <?php if ($recentTransfers === []): ?><tr><td colspan="5" class="empty">Belum ada aktivitas transfer.</td></tr><?php endif ?>
    <?php foreach ($recentTransfers as $log): ?><tr><td><?= esc($log['started_at']) ?></td><td><strong><?= esc($log['applicant_name'] ?? 'Dokumen') ?></strong><small><?= esc($log['original_filename'] ?? '-') ?></small></td><td><?= esc($log['action']) ?></td><td><span class="badge <?= esc($log['status']) ?>"><?= esc($log['status']) ?></span></td><td><?= $log['bytes_received'] ? number_format($log['bytes_received'] / 1024, 1) . ' KB' : '—' ?></td></tr><?php endforeach ?>
    </tbody></table>
</div>
<?= $this->endSection() ?>
