<?php
// index.php - File utama aplikasi

// Mulai session
session_start();

// Include router
require_once __DIR__ . '/router.php';

// Jalankan router
$router = new Router();
$router->dispatch();