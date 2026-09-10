<?php

declare(strict_types=1);

/*
 * Root front controller.
 *
 * This lets the application be opened without adding `/public` to the URL.
 * CodeIgniter's real front controller remains inside the public directory so
 * FCPATH and all framework paths continue to be resolved correctly.
 */
require __DIR__ . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'index.php';
