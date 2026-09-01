<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<div class="table-card">
    <table>
        <thead>
            <tr><th>No.</th><th>Waktu</th><th>Pelamar / File</th><th>Pengguna</th><th>Aksi</th><th>Status</th><th>Data</th><th>Pesan</th></tr>
        </thead>
        <tbody>
        <?php if ($logs === []): ?>
            <tr><td colspan="8" class="empty">Belum ada riwayat transfer.</td></tr>
        <?php endif ?>
        <?php foreach ($logs as $index => $log): ?>
            <tr>
                <td><?= number_format($rowNumberStart + $index) ?></td>
                <td><small><?= esc($log['started_at']) ?></small><small><?= esc($log['finished_at'] ?: 'Belum selesai') ?></small></td>
                <td><strong><?= esc($log['applicant_name'] ?? '—') ?></strong><small><?= esc($log['original_filename'] ?? '—') ?></small></td>
                <td><?= esc($log['user_name'] ?? 'Sistem') ?></td>
                <td><?= esc($log['action']) ?></td>
                <td><span class="badge <?= esc($log['status']) ?>"><?= esc($log['status']) ?></span></td>
                <td><?= $log['bytes_received'] ? number_format($log['bytes_received'] / 1024, 1) . ' KB' : '—' ?><small>HTTP <?= esc($log['http_status'] ?: '—') ?></small></td>
                <td class="message-cell"><?= esc($log['error_message'] ?: '—') ?></td>
            </tr>
        <?php endforeach ?>
        </tbody>
    </table>
</div>
<div class="pagination"><?= $pager->links() ?></div>
<?= $this->endSection() ?>
