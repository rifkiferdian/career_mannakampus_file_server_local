<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<?php
$formatDate = static function ($value): string {
    $timestamp = $value ? strtotime((string) $value) : false;
    return $timestamp === false ? '—' : date('d/m/Y H:i:s', $timestamp);
};
$eventLabels = [
    'document_opened' => 'Dokumen dibuka', 'document_downloaded' => 'Dokumen diunduh', 'document_download_failed' => 'Download gagal',
    'documents_synced' => 'Sinkronisasi selesai', 'documents_sync_failed' => 'Sinkronisasi gagal',
    'hosting_file_deleted' => 'File hosting dihapus', 'hosting_file_delete_failed' => 'Hapus hosting gagal',
    'login_success' => 'Login berhasil', 'login_failed' => 'Login gagal', 'logout' => 'Logout',
    'password_changed' => 'Password diubah', 'user_created' => 'Pengguna dibuat', 'user_password_reset' => 'Password direset', 'user_status_changed' => 'Status pengguna diubah',
];
?>
<div class="logs-page audit-page">
    <section class="logs-hero audit-hero">
        <span class="logs-hero-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 5 6v5c0 4.6 2.9 8.7 7 10 4.1-1.3 7-5.4 7-10V6zM9 12l2 2 4-5"/></svg></span>
        <div><span class="logs-kicker">Audit dan keamanan</span><h2>Log Aktivitas Sistem</h2><p>Lihat siapa melakukan apa, kapan aktivitas terjadi, serta perangkat yang digunakan.</p></div>
    </section>

    <section class="logs-summary" aria-label="Ringkasan log aktivitas">
        <a class="log-stat navy <?= $category === '' ? 'active' : '' ?>" href="<?= site_url('riwayat/aktivitas') ?>"><span>Total aktivitas</span><strong><?= number_format($summary['total']) ?></strong><small>Seluruh jejak audit tersimpan</small></a>
        <div class="log-stat green"><span>Aktivitas hari ini</span><strong><?= number_format($summary['today']) ?></strong><small>Sejak pukul 00.00</small></div>
        <a class="log-stat blue <?= $category === 'document' ? 'active' : '' ?>" href="<?= site_url('riwayat/aktivitas') ?>?category=document"><span>Aktivitas dokumen</span><strong><?= number_format($summary['documents']) ?></strong><small>Buka, sinkronisasi, dan hapus</small></a>
        <a class="log-stat red <?= $category === 'problem' ? 'active' : '' ?>" href="<?= site_url('riwayat/aktivitas') ?>?category=problem"><span>Aktivitas gagal</span><strong><?= number_format($summary['problems']) ?></strong><small>Perlu diperiksa administrator</small></a>
    </section>

    <section class="logs-toolbar">
        <form method="get" action="<?= site_url('riwayat/aktivitas') ?>" class="logs-filter-form">
            <label class="logs-search"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg><input type="search" name="q" value="<?= esc($search) ?>" placeholder="Cari pengguna, aktivitas, file, IP, atau keterangan"></label>
            <select name="category"><option value="">Semua kategori</option><option value="document" <?= $category === 'document' ? 'selected' : '' ?>>Dokumen</option><option value="account" <?= $category === 'account' ? 'selected' : '' ?>>Akun dan login</option><option value="problem" <?= $category === 'problem' ? 'selected' : '' ?>>Aktivitas gagal</option></select>
            <button class="button primary" type="submit">Terapkan</button>
            <?php if ($search !== '' || $category !== ''): ?><a class="button ghost" href="<?= site_url('riwayat/aktivitas') ?>">Reset</a><?php endif ?>
        </form>
    </section>

    <section class="logs-table-panel">
        <header><div><strong>Jejak audit</strong><span>Catatan tidak dapat diubah melalui halaman ini</span></div><span class="record-count"><?= number_format(count($logs)) ?> baris di halaman ini</span></header>
        <div class="logs-table-wrap"><table class="logs-table audit-table"><thead><tr><th>No.</th><th>Waktu</th><th>Pengguna</th><th>Aktivitas</th><th>Dokumen</th><th>Keterangan</th><th>Alamat IP</th></tr></thead><tbody>
        <?php if ($logs === []): ?><tr><td colspan="7"><div class="logs-empty"><span>⌕</span><strong>Log tidak ditemukan</strong><p>Belum ada aktivitas atau data tidak cocok dengan filter.</p></div></td></tr><?php endif ?>
        <?php foreach ($logs as $index => $log): ?>
            <?php $isProblem = str_ends_with((string) $log['event'], 'failed'); ?>
            <tr>
                <td class="row-number"><?= number_format($rowNumberStart + $index) ?></td>
                <td><div class="log-time"><strong><?= esc($formatDate($log['created_at'])) ?></strong><small>ID log #<?= (int) $log['id'] ?></small></div></td>
                <td><span class="user-chip"><?= esc($log['user_name'] ?? 'Sistem') ?></span></td>
                <td><span class="audit-event <?= $isProblem ? 'problem' : '' ?>"><?= esc($eventLabels[$log['event']] ?? str_replace('_', ' ', $log['event'])) ?></span></td>
                <td><span class="audit-file" title="<?= esc($log['original_filename'] ?? '') ?>"><?= esc($log['original_filename'] ?: 'Tidak terkait dokumen') ?></span></td>
                <td><span class="audit-description"><?= esc($log['description'] ?: 'Tidak ada keterangan') ?></span></td>
                <td><code class="ip-address"><?= esc($log['ip_address'] ?: '—') ?></code></td>
            </tr>
        <?php endforeach ?>
        </tbody></table></div>
    </section>
    <div class="pagination logs-pagination"><?= $pager->only(['q', 'category'])->links() ?></div>
</div>
<?= $this->endSection() ?>
