<?php
// test.php
echo "<h2>Debugging SystemLab</h2>";
echo "<h3>Current Directory: " . __DIR__ . "</h3>";

$files_to_check = [
    'index.php',
    'router.php',
    'config/database.php',
    'models/Pengguna.php',
    'views/login.html'
];

echo "<h3>File Check:</h3>";
foreach ($files_to_check as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        echo "✅ $file - FOUND<br>";
    } else {
        echo "❌ $file - NOT FOUND<br>";
    }
}

// Test database connection
echo "<h3>Database Test:</h3>";
try {
    require_once __DIR__ . '/config/database.php';
    $conn = Database::getInstance();
    echo "✅ Database connection SUCCESS<br>";
} catch(Exception $e) {
    echo "❌ Database connection FAILED: " . $e->getMessage();
}
?>