<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<?php
$formatDate = static function ($value): string {
    $timestamp = $value ? strtotime((string) $value) : false;
    return $timestamp === false ? '—' : date('d/m/Y H:i', $timestamp);
};
$transferLabels = [
    'pending' => 'Menunggu download',
    'downloading' => 'Sedang diproses',
    'completed' => 'Tersimpan lokal',
    'failed' => 'Gagal',
];
?>

<div class="documents-page">
    <section class="documents-hero">
        <div>
            <span class="documents-kicker">Pusat dokumen recruitment</span>
            <h2>Kelola CV dengan aman dan ringkas</h2>
            <p>Pantau proses pemindahan, buka salinan lokal, dan kosongkan penyimpanan hosting dari satu halaman.</p>
        </div>
        <form method="post" action="<?= site_url('dokumen/sinkronkan') ?>">
            <?= csrf_field() ?>
            <button class="button sync-button" type="submit" <?= ! $remoteConfigured ? 'disabled title="Konfigurasi hosting belum tersedia"' : '' ?>>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 7v5h-5M4 17v-5h5M6.1 9A7 7 0 0 1 18.7 7M17.9 15A7 7 0 0 1 5.3 17"/></svg>
                Sinkronkan dokumen
            </button>
        </form>
    </section>

    <section class="document-overview" aria-label="Ringkasan dokumen">
        <a class="document-stat total <?= $storage === '' ? 'active' : '' ?>" href="<?= site_url('dokumen') ?>">
            <span class="document-stat-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h7l4 4v14H7zM14 3v5h5M10 13h5M10 17h5"/></svg></span>
            <span class="document-stat-copy"><small>Total dokumen lokal</small><strong><?= number_format($summary['total']) ?></strong><em>Semua CV yang tercatat</em></span>
            <span class="document-stat-arrow">›</span>
        </a>
        <a class="document-stat ready <?= $storage === 'ready' ? 'active' : '' ?>" href="<?= site_url('dokumen') ?>?storage=ready">
            <span class="document-stat-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg></span>
            <span class="document-stat-copy"><small>Belum dihapus</small><strong><?= number_format($summary['readyToDelete']) ?></strong><em>Siap dibersihkan dari hosting</em></span>
            <span class="document-stat-arrow">›</span>
        </a>
        <a class="document-stat deleted <?= $storage === 'deleted' ? 'active' : '' ?>" href="<?= site_url('dokumen') ?>?storage=deleted">
            <span class="document-stat-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M9 7V4h6v3M7 7l1 14h8l1-14M10 11v6M14 11v6"/></svg></span>
            <span class="document-stat-copy"><small>Sudah dihapus</small><strong><?= number_format($summary['deletedFromHosting']) ?></strong><em>Hosting berhasil dibersihkan</em></span>
            <span class="document-stat-arrow">›</span>
        </a>
        <a class="document-stat attention <?= $storage === 'attention' ? 'active' : '' ?>" href="<?= site_url('dokumen') ?>?storage=attention">
            <span class="document-stat-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 8v5M12 17h.01M10.3 4.7 3.5 17a2 2 0 0 0 1.8 3h13.4a2 2 0 0 0 1.8-3L13.7 4.7a2 2 0 0 0-3.4 0Z"/></svg></span>
            <span class="document-stat-copy"><small>Perlu perhatian</small><strong><?= number_format($summary['needsAttention']) ?></strong><em>Pending, gagal, atau belum valid</em></span>
            <span class="document-stat-arrow">›</span>
        </a>
    </section>

    <section class="documents-toolbar">
        <form method="get" action="<?= site_url('dokumen') ?>" class="document-filters">
            <label class="search-control">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                <input type="search" name="q" value="<?= esc($search) ?>" placeholder="Cari nama pelamar, nomor, atau file">
            </label>
            <select name="status" aria-label="Filter status transfer">
                <option value="">Semua proses</option>
                <?php foreach (['pending' => 'Menunggu download', 'downloading' => 'Sedang diproses', 'completed' => 'Tersimpan lokal', 'failed' => 'Gagal'] as $value => $label): ?>
                    <option value="<?= $value ?>" <?= $status === $value ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach ?>
            </select>
            <select name="storage" aria-label="Filter penyimpanan hosting">
                <option value="">Semua penyimpanan</option>
                <option value="ready" <?= $storage === 'ready' ? 'selected' : '' ?>>Belum dihapus dari hosting</option>
                <option value="deleted" <?= $storage === 'deleted' ? 'selected' : '' ?>>Sudah dihapus dari hosting</option>
                <option value="attention" <?= $storage === 'attention' ? 'selected' : '' ?>>Perlu perhatian</option>
            </select>
            <button class="button primary" type="submit">Terapkan</button>
            <?php if ($search !== '' || $status !== '' || $storage !== ''): ?><a class="button ghost" href="<?= site_url('dokumen') ?>">Reset</a><?php endif ?>
        </form>
    </section>

    <section class="storage-notice">
        <span class="storage-notice-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 5 6v5c0 4.6 2.9 8.7 7 10 4.1-1.3 7-5.4 7-10V6zM9 12l2 2 4-5"/></svg></span>
        <div><strong>Penghapusan aman berbasis checksum</strong><span>Checkbox hanya tersedia jika salinan lokal sudah terverifikasi. Menghapus dari hosting tidak menghapus PDF lokal maupun data pelamar.</span></div>
        <span class="storage-notice-label">File lokal aman</span>
    </section>

    <section class="document-list-panel">
        <header class="bulk-delete-bar">
            <div><strong><span id="selected-document-count">0</span> dokumen dipilih</strong><span>Pilih CV yang ingin dibersihkan dari hosting.</span></div>
            <button class="button danger-solid" id="bulk-hosting-delete-button" type="submit" form="bulk-hosting-delete-form" disabled>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M9 7V4h6v3M8 7l1 13h6l1-13"/></svg>
                Hapus dari hosting
            </button>
        </header>

        <div class="documents-table-wrap">
            <table class="documents-table">
                <thead><tr><th class="check-cell"><input type="checkbox" id="toggle-all-hosting-delete" aria-label="Pilih semua dokumen yang dapat dihapus"></th><th>No.</th><th>Pelamar</th><th>File CV</th><th>Ukuran</th><th>Penyimpanan</th><th>Aktivitas</th><th class="right">Aksi</th></tr></thead>
                <tbody>
                <?php if ($documents === []): ?>
                    <tr><td colspan="8"><div class="documents-empty"><span>⌕</span><strong>Dokumen tidak ditemukan</strong><p>Ubah filter pencarian atau jalankan sinkronisasi untuk mengambil dokumen baru.</p></div></td></tr>
                <?php endif ?>
                <?php foreach ($documents as $index => $document): ?>
                    <?php
                    $hostingDeletedAt = $document['hosting_deleted_at'] ?? null;
                    $hostingDeleteError = $document['hosting_delete_error'] ?? null;
                    $canDeleteHosting = $remoteConfigured
                        && $document['transfer_status'] === 'completed'
                        && $document['confirmation_status'] === 'confirmed'
                        && empty($hostingDeletedAt);
                    $error = $document['last_error'] ?: $document['confirmation_error'] ?: $hostingDeleteError;
                    ?>
                    <tr class="<?= $canDeleteHosting ? 'row-ready' : '' ?>">
                        <td class="check-cell"><?php if ($canDeleteHosting): ?><input class="hosting-delete-checkbox" type="checkbox" form="bulk-hosting-delete-form" name="document_ids[]" value="<?= (int) $document['id'] ?>" aria-label="Pilih <?= esc($document['original_filename']) ?>"><?php else: ?><span class="check-unavailable">—</span><?php endif ?></td>
                        <td class="row-number"><?= number_format($rowNumberStart + $index) ?></td>
                        <td><div class="applicant-cell"><span class="applicant-avatar"><?= esc(mb_strtoupper(mb_substr((string) $document['applicant_name'], 0, 1))) ?></span><span><strong><?= esc($document['applicant_name']) ?></strong><small><?= esc($document['application_number'] ?: 'Tanpa nomor lamaran') ?></small></span></div></td>
                        <td><strong class="file-name" title="<?= esc($document['original_filename']) ?>"><?= esc($document['original_filename']) ?></strong><small>Dokumen lamaran PDF</small></td>
                        <td><span class="file-size"><?= $document['file_size'] ? number_format($document['file_size'] / 1024, 1) . ' KB' : '—' ?></span></td>
                        <td><div class="storage-status"><span class="process-pill <?= esc($document['transfer_status']) ?>"><?= esc($transferLabels[$document['transfer_status']] ?? $document['transfer_status']) ?></span><?php if (! empty($hostingDeletedAt)): ?><span class="hosting-pill deleted">Hosting dihapus</span><?php elseif ($document['confirmation_status'] === 'confirmed'): ?><span class="hosting-pill available">Masih di hosting</span><?php else: ?><span class="hosting-pill waiting">Belum terkonfirmasi</span><?php endif ?><?php if ($error): ?><small class="error-text" title="<?= esc($error) ?>"><?= esc(mb_strimwidth($error, 0, 45, '…')) ?></small><?php endif ?></div></td>
                        <td><div class="activity-cell"><span><b>Upload</b><?= esc($formatDate($document['remote_uploaded_at'])) ?></span><span><b>Local</b><?= esc($formatDate($document['downloaded_at'])) ?></span><?php if ($hostingDeletedAt): ?><span><b>Dihapus</b><?= esc($formatDate($hostingDeletedAt)) ?></span><?php endif ?></div></td>
                        <td class="right"><div class="inline-actions document-actions"><?php if ($document['transfer_status'] === 'completed'): ?><a class="button small ghost" target="_blank" rel="noopener" href="<?= site_url('dokumen/' . $document['id'] . '/buka') ?>">Buka PDF</a><?php if ($document['confirmation_status'] !== 'confirmed'): ?><form method="post" action="<?= site_url('dokumen/' . $document['id'] . '/download') ?>"><?= csrf_field() ?><button class="button small primary" type="submit">Konfirmasi ulang</button></form><?php endif ?><?php else: ?><form method="post" action="<?= site_url('dokumen/' . $document['id'] . '/download') ?>"><?= csrf_field() ?><button class="button small primary" type="submit" <?= ! $remoteConfigured || $document['transfer_status'] === 'downloading' ? 'disabled' : '' ?>><?= $document['transfer_status'] === 'failed' ? 'Coba lagi' : 'Download' ?></button></form><?php endif ?></div></td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </section>

    <form method="post" action="<?= site_url('dokumen/hapus-hosting') ?>" id="bulk-hosting-delete-form"><?= csrf_field() ?></form>
    <div class="pagination documents-pagination"><?= $pager->only(['q', 'status', 'storage'])->links() ?></div>
</div>

<script>
(() => {
    const form = document.getElementById('bulk-hosting-delete-form');
    const selectAll = document.getElementById('toggle-all-hosting-delete');
    const checkboxes = [...document.querySelectorAll('.hosting-delete-checkbox')];
    const count = document.getElementById('selected-document-count');
    const button = document.getElementById('bulk-hosting-delete-button');
    const refresh = () => {
        const selected = checkboxes.filter((checkbox) => checkbox.checked);
        count.textContent = selected.length;
        button.disabled = selected.length === 0;
        if (selectAll) {
            selectAll.disabled = checkboxes.length === 0;
            selectAll.checked = selected.length > 0 && selected.length === checkboxes.length;
            selectAll.indeterminate = selected.length > 0 && selected.length < checkboxes.length;
        }
        checkboxes.forEach((checkbox) => checkbox.closest('tr')?.classList.toggle('selected', checkbox.checked));
    };
    checkboxes.forEach((checkbox) => checkbox.addEventListener('change', refresh));
    selectAll?.addEventListener('change', () => { checkboxes.forEach((checkbox) => { checkbox.checked = selectAll.checked; }); refresh(); });
    form?.addEventListener('submit', (event) => {
        const selected = checkboxes.filter((checkbox) => checkbox.checked).length;
        if (selected === 0 || !window.confirm(`Hapus ${selected} PDF dari hosting? Salinan pada server lokal akan tetap tersimpan.`)) event.preventDefault();
    });
    refresh();
})();
</script>
<?= $this->endSection() ?>
