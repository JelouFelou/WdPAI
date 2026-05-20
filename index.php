<?php

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
    ),
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

require_once "Routing.php";
$path = trim($_SERVER["REQUEST_URI"], '/');
$path = parse_url($path,PHP_URL_PATH);

Routing::run($path);
