<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<?php
$formatDate = static function ($value): string {
    $timestamp = $value ? strtotime((string) $value) : false;
    return $timestamp === false ? '—' : date('d/m/Y H:i:s', $timestamp);
};
$actionLabels = ['download' => 'Download PDF', 'retry' => 'Coba ulang', 'confirm' => 'Konfirmasi hosting', 'delete_hosting' => 'Hapus dari hosting'];
$statusLabels = ['started' => 'Diproses', 'success' => 'Berhasil', 'failed' => 'Gagal'];
?>
<div class="logs-page transfer-page">
    <section class="logs-hero">
        <span class="logs-hero-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12M7 10l5 5 5-5M5 21h14"/></svg></span>
        <div><span class="logs-kicker">Jejak pemindahan file</span><h2>Riwayat Transfer Dokumen</h2><p>Setiap download, validasi, konfirmasi, dan penghapusan hosting tercatat di sini.</p></div>
    </section>

    <section class="logs-summary" aria-label="Ringkasan riwayat transfer">
        <a class="log-stat navy <?= $status === '' && $action === '' ? 'active' : '' ?>" href="<?= site_url('riwayat/transfer') ?>"><span>Total proses</span><strong><?= number_format($summary['total']) ?></strong><small>Seluruh aktivitas transfer</small></a>
        <a class="log-stat green <?= $status === 'success' ? 'active' : '' ?>" href="<?= site_url('riwayat/transfer') ?>?status=success"><span>Berhasil</span><strong><?= number_format($summary['success']) ?></strong><small>Proses selesai tanpa kendala</small></a>
        <a class="log-stat red <?= $status === 'failed' ? 'active' : '' ?>" href="<?= site_url('riwayat/transfer') ?>?status=failed"><span>Gagal</span><strong><?= number_format($summary['failed']) ?></strong><small>Perlu pemeriksaan ulang</small></a>
        <a class="log-stat blue <?= $action === 'delete_hosting' ? 'active' : '' ?>" href="<?= site_url('riwayat/transfer') ?>?action=delete_hosting"><span>Hosting dibersihkan</span><strong><?= number_format($summary['deleted']) ?></strong><small>PDF berhasil dihapus</small></a>
    </section>

    <section class="logs-toolbar">
        <form method="get" action="<?= site_url('riwayat/transfer') ?>" class="logs-filter-form">
            <label class="logs-search"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg><input type="search" name="q" value="<?= esc($search) ?>" placeholder="Cari pelamar, file, pengguna, atau pesan"></label>
            <select name="action"><option value="">Semua aksi</option><?php foreach ($actionLabels as $value => $label): ?><option value="<?= $value ?>" <?= $action === $value ? 'selected' : '' ?>><?= $label ?></option><?php endforeach ?></select>
            <select name="status"><option value="">Semua status</option><?php foreach ($statusLabels as $value => $label): ?><option value="<?= $value ?>" <?= $status === $value ? 'selected' : '' ?>><?= $label ?></option><?php endforeach ?></select>
            <button class="button primary" type="submit">Terapkan</button>
            <?php if ($search !== '' || $status !== '' || $action !== ''): ?><a class="button ghost" href="<?= site_url('riwayat/transfer') ?>">Reset</a><?php endif ?>
        </form>
    </section>

    <section class="logs-table-panel">
        <header><div><strong>Detail transfer</strong><span>Urutan terbaru ditampilkan paling atas</span></div><span class="record-count"><?= number_format(count($logs)) ?> baris di halaman ini</span></header>
        <div class="logs-table-wrap"><table class="logs-table"><thead><tr><th>No.</th><th>Waktu</th><th>Pelamar / File</th><th>Petugas</th><th>Aksi</th><th>Status</th><th>Data transfer</th><th>Pesan</th></tr></thead><tbody>
        <?php if ($logs === []): ?><tr><td colspan="8"><div class="logs-empty"><span>⌕</span><strong>Riwayat tidak ditemukan</strong><p>Belum ada aktivitas atau data tidak cocok dengan filter.</p></div></td></tr><?php endif ?>
        <?php foreach ($logs as $index => $log): ?>
            <tr>
                <td class="row-number"><?= number_format($rowNumberStart + $index) ?></td>
                <td><div class="log-time"><strong><?= esc($formatDate($log['started_at'])) ?></strong><small><?= $log['finished_at'] ? 'Selesai ' . esc($formatDate($log['finished_at'])) : 'Masih diproses' ?></small></div></td>
                <td><div class="log-document"><span class="file-mark">PDF</span><span><strong><?= esc($log['applicant_name'] ?? 'Dokumen') ?></strong><small title="<?= esc($log['original_filename'] ?? '') ?>"><?= esc($log['original_filename'] ?? 'Tanpa nama file') ?></small></span></div></td>
                <td><span class="user-chip"><?= esc($log['user_name'] ?? 'Sistem') ?></span></td>
                <td><span class="action-label <?= esc($log['action']) ?>"><?= esc($actionLabels[$log['action']] ?? $log['action']) ?></span></td>
                <td><span class="log-status <?= esc($log['status']) ?>"><i></i><?= esc($statusLabels[$log['status']] ?? $log['status']) ?></span></td>
                <td><div class="transfer-data"><strong><?= $log['bytes_received'] ? number_format($log['bytes_received'] / 1024, 1) . ' KB' : '—' ?></strong><small>HTTP <?= esc($log['http_status'] ?: '—') ?><?= (int) $log['checksum_valid'] === 1 ? ' · checksum valid' : '' ?></small></div></td>
                <td><span class="log-message <?= $log['error_message'] ? 'has-error' : '' ?>" title="<?= esc($log['error_message'] ?: '') ?>"><?= esc($log['error_message'] ?: 'Tidak ada kendala') ?></span></td>
            </tr>
        <?php endforeach ?>
        </tbody></table></div>
    </section>
    <div class="pagination logs-pagination"><?= $pager->only(['q', 'action', 'status'])->links() ?></div>
</div>
<?= $this->endSection() ?>
