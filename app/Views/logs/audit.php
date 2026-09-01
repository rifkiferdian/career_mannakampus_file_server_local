<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<div class="table-card"><table><thead><tr><th>Waktu</th><th>Pengguna</th><th>Aktivitas</th><th>Keterangan</th><th>IP</th></tr></thead><tbody>
<?php if ($logs === []): ?><tr><td colspan="5" class="empty">Belum ada log aktivitas.</td></tr><?php endif ?>
<?php foreach ($logs as $log): ?><tr><td><?= esc($log['created_at']) ?></td><td><?= esc($log['user_name'] ?? 'Sistem') ?></td><td><span class="badge neutral"><?= esc($log['event']) ?></span><?php if ($log['original_filename']): ?><small><?= esc($log['original_filename']) ?></small><?php endif ?></td><td><?= esc($log['description'] ?: '—') ?></td><td><?= esc($log['ip_address'] ?: '—') ?></td></tr><?php endforeach ?>
</tbody></table></div><div class="pagination"><?= $pager->links() ?></div>
<?= $this->endSection() ?>
