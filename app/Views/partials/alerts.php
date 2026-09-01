<?php foreach (['success', 'warning', 'error'] as $type): ?>
    <?php if ($message = session()->getFlashdata($type)): ?>
        <div class="alert <?= esc($type) ?>"><?= esc($message) ?></div>
    <?php endif ?>
<?php endforeach ?>
<?php if ($errors = session()->getFlashdata('errors')): ?>
    <div class="alert error"><strong>Periksa kembali data berikut:</strong><ul><?php foreach ((array) $errors as $error): ?><li><?= esc($error) ?></li><?php endforeach ?></ul></div>
<?php endif ?>
