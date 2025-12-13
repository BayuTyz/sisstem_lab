<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Auto load classes
spl_autoload_register(function ($class_name) {
    $file = __DIR__ . '/models/' . $class_name . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/router.php';

$router = new Router();
$router->dispatch();
?>