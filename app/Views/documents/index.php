<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<div class="action-bar">
    <form method="get" action="<?= site_url('dokumen') ?>" class="filters">
        <input type="search" name="q" value="<?= esc($search) ?>" placeholder="Nama, nomor lamaran, atau file">
        <select name="status"><option value="">Semua status</option><?php foreach (['pending' => 'Menunggu', 'downloading' => 'Mengunduh', 'completed' => 'Selesai', 'failed' => 'Gagal'] as $value => $label): ?><option value="<?= $value ?>" <?= $status === $value ? 'selected' : '' ?>><?= $label ?></option><?php endforeach ?></select>
        <button class="button ghost" type="submit">Filter</button>
    </form>
    <form method="post" action="<?= site_url('dokumen/sinkronkan') ?>"><?= csrf_field() ?><button class="button primary" type="submit" <?= ! $remoteConfigured ? 'disabled title="Konfigurasi hosting belum tersedia"' : '' ?>>Sinkronkan dari hosting</button></form>
</div>
<div class="table-card">
<table><thead><tr><th>Pelamar</th><th>Dokumen</th><th>Ukuran</th><th>Status</th><th>Waktu</th><th class="right">Aksi</th></tr></thead><tbody>
<?php if ($documents === []): ?><tr><td colspan="6" class="empty">Belum ada dokumen. Hubungkan API hosting lalu jalankan sinkronisasi.</td></tr><?php endif ?>
<?php foreach ($documents as $document): ?>
<tr>
    <td><strong><?= esc($document['applicant_name']) ?></strong><small><?= esc($document['application_number'] ?: 'Tanpa nomor lamaran') ?></small></td>
    <td><strong class="file-name"><?= esc($document['original_filename']) ?></strong><small><?= esc($document['document_type']) ?></small></td>
    <td><?= $document['file_size'] ? number_format($document['file_size'] / 1024, 1) . ' KB' : '—' ?></td>
    <td><span class="badge <?= esc($document['transfer_status']) ?>"><?= esc($document['transfer_status']) ?></span><?php if ($document['last_error']): ?><small class="error-text" title="<?= esc($document['last_error']) ?>"><?= esc(mb_strimwidth($document['last_error'], 0, 55, '…')) ?></small><?php endif ?></td>
    <td><small>Upload: <?= esc($document['remote_uploaded_at'] ?: '—') ?></small><small>Download: <?= esc($document['downloaded_at'] ?: '—') ?></small></td>
    <td class="right"><?php if ($document['transfer_status'] === 'completed'): ?><a class="button small ghost" target="_blank" rel="noopener" href="<?= site_url('dokumen/' . $document['id'] . '/buka') ?>">Buka PDF</a><?php else: ?><form method="post" action="<?= site_url('dokumen/' . $document['id'] . '/download') ?>"><?= csrf_field() ?><button class="button small primary" type="submit" <?= ! $remoteConfigured || $document['transfer_status'] === 'downloading' ? 'disabled' : '' ?>><?= $document['transfer_status'] === 'failed' ? 'Coba lagi' : 'Download' ?></button></form><?php endif ?></td>
</tr>
<?php endforeach ?>
</tbody></table>
</div>
<div class="pagination"><?= $pager->links() ?></div>
<?= $this->endSection() ?>
