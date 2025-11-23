<?php
// config.php - Database Configuration

$db_host = 'localhost';
$db_name = 'your_db_name';
$db_user = 'your_db_user';
$db_pass = 'your_db_pass';

try {
    $dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";
    $pdo = new PDO($dsn, $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // If database doesn't exist, try to create it (optional, but helpful)
    if (strpos($e->getMessage(), 'Unknown database') !== false) {
        try {
            $dsn_no_db = "mysql:host=$db_host;charset=utf8mb4";
            $pdo_temp = new PDO($dsn_no_db, $db_user, $db_pass);
            $pdo_temp->exec("CREATE DATABASE IF NOT EXISTS `$db_name`");
            $pdo = new PDO($dsn, $db_user, $db_pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $ex) {
            die("Database connection failed: " . $ex->getMessage());
        }
    } else {
        die("Database connection failed: " . $e->getMessage());
    }
}

// Admin Credentials
$admin_user = 'your_admin_user';
$admin_pass = 'your_admin_pass'; // Change this!

// IP Info Token
$ipinfo_token = 'your_ipinfo_token';
