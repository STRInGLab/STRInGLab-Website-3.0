<?php
// config.php
require_once __DIR__ . '/php/env.php';

$host    = env('DB_HOST', 'localhost');
$db      = env('DB_NAME', 'invoice_db');
$user    = env('DB_USER', 'db_user');
$pass    = env('DB_PASS', '');
$charset = env('DB_CHARSET', 'utf8mb4');
