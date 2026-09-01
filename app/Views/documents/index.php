<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<div class="action-bar">
    <form method="get" action="<?= site_url('dokumen') ?>" class="filters">
        <input type="search" name="q" value="<?= esc($search) ?>" placeholder="Nama, nomor lamaran, atau file">
        <select name="status">
            <option value="">Semua status</option>
            <?php foreach (['pending' => 'Menunggu', 'downloading' => 'Mengunduh', 'completed' => 'Selesai', 'failed' => 'Gagal'] as $value => $label): ?>
                <option value="<?= $value ?>" <?= $status === $value ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach ?>
        </select>
        <button class="button ghost" type="submit">Filter</button>
    </form>
    <form method="post" action="<?= site_url('dokumen/sinkronkan') ?>">
        <?= csrf_field() ?>
        <button class="button primary" type="submit" <?= ! $remoteConfigured ? 'disabled title="Konfigurasi hosting belum tersedia"' : '' ?>>Sinkronkan &amp; pindahkan PDF</button>
    </form>
</div>
<div class="table-card">
<table>
    <thead><tr><th>No.</th><th>Pelamar</th><th>Dokumen</th><th>Ukuran</th><th>Status</th><th>Waktu</th><th class="right">Aksi</th></tr></thead>
    <tbody>
    <?php if ($documents === []): ?><tr><td colspan="7" class="empty">Belum ada dokumen. Jalankan sinkronisasi untuk mengambil PDF dari hosting.</td></tr><?php endif ?>
    <?php foreach ($documents as $index => $document): ?>
        <tr>
            <td><?= number_format($rowNumberStart + $index) ?></td>
            <td><strong><?= esc($document['applicant_name']) ?></strong><small><?= esc($document['application_number'] ?: 'Tanpa nomor lamaran') ?></small></td>
            <td><strong class="file-name"><?= esc($document['original_filename']) ?></strong><small><?= esc($document['document_type']) ?></small></td>
            <td><?= $document['file_size'] ? number_format($document['file_size'] / 1024, 1) . ' KB' : '—' ?></td>
            <td>
                <span class="badge <?= esc($document['transfer_status']) ?>"><?= esc($document['transfer_status']) ?></span>
                <?php if ($document['transfer_status'] === 'completed'): ?>
                    <small><span class="badge <?= $document['confirmation_status'] === 'confirmed' ? 'success' : ($document['confirmation_status'] === 'failed' ? 'failed' : 'pending') ?>">Hosting: <?= esc($document['confirmation_status']) ?></span></small>
                <?php endif ?>
                <?php $error = $document['last_error'] ?: $document['confirmation_error']; ?>
                <?php if ($error): ?><small class="error-text" title="<?= esc($error) ?>"><?= esc(mb_strimwidth($error, 0, 55, '…')) ?></small><?php endif ?>
            </td>
            <td><small>Upload: <?= esc($document['remote_uploaded_at'] ?: '—') ?></small><small>Download: <?= esc($document['downloaded_at'] ?: '—') ?></small></td>
            <td class="right">
                <?php if ($document['transfer_status'] === 'completed'): ?>
                    <div class="inline-actions">
                        <a class="button small ghost" target="_blank" rel="noopener" href="<?= site_url('dokumen/' . $document['id'] . '/buka') ?>">Buka PDF</a>
                        <?php if ($document['confirmation_status'] !== 'confirmed'): ?>
                            <form method="post" action="<?= site_url('dokumen/' . $document['id'] . '/download') ?>"><?= csrf_field() ?><button class="button small primary" type="submit">Konfirmasi ulang</button></form>
                        <?php endif ?>
                    </div>
                <?php else: ?>
                    <form method="post" action="<?= site_url('dokumen/' . $document['id'] . '/download') ?>">
                        <?= csrf_field() ?>
                        <button class="button small primary" type="submit" <?= ! $remoteConfigured || $document['transfer_status'] === 'downloading' ? 'disabled' : '' ?>><?= $document['transfer_status'] === 'failed' ? 'Coba lagi' : 'Download' ?></button>
                    </form>
                <?php endif ?>
            </td>
        </tr>
    <?php endforeach ?>
    </tbody>
</table>
</div>
<div class="pagination"><?= $pager->only(['q', 'status'])->links() ?></div>
<?= $this->endSection() ?>
