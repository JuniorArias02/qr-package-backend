<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=qrpackage', 'root', 'root');
    echo "Connected successfully\n";
} catch (Exception $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
