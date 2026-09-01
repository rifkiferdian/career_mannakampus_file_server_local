<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->setAutoRoute(false);

$routes->get('/', 'Home::index', ['filter' => 'auth']);
$routes->match(['get', 'post'], 'login', 'AuthController::login', ['filter' => 'guest']);
$routes->post('logout', 'AuthController::logout', ['filter' => 'auth']);
$routes->match(['get', 'post'], 'akun/password', 'AccountController::password', ['filter' => 'auth']);

$routes->group('dokumen', ['filter' => 'auth'], static function (RouteCollection $routes): void {
    $routes->get('/', 'DocumentController::index');
    $routes->post('sinkronkan', 'DocumentController::sync');
    $routes->post('(:num)/download', 'DocumentController::download/$1');
    $routes->get('(:num)/buka', 'DocumentController::open/$1');
});

$routes->group('riwayat', ['filter' => 'auth'], static function (RouteCollection $routes): void {
    $routes->get('transfer', 'TransferLogController::index');
    $routes->get('aktivitas', 'AuditLogController::index');
});

$routes->group('pengguna', ['filter' => 'admin'], static function (RouteCollection $routes): void {
    $routes->get('/', 'UserController::index');
    $routes->post('buat', 'UserController::create');
    $routes->post('(:num)/status', 'UserController::toggleStatus/$1');
    $routes->post('(:num)/reset-password', 'UserController::resetPassword/$1');
});
