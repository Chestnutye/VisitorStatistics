<?php
// auth.php - Authentication Helper

session_start();

require_once __DIR__ . '/config.php';

// Check if user is logged in
function check_login()
{
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header('Location: login.php');
        exit;
    }
}

// Logout logic
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}
