<?php
// includes/functions.php

session_start();

// Include DB config if it exists
if (file_exists(__DIR__ . '/../config/db.php')) {
    try {
        require_once __DIR__ . '/../config/db.php';
        
        // Check if system is initialized (tables exist)
        if (basename($_SERVER['PHP_SELF']) !== 'install.php') {
            // Check if users table exists and is populated
            $stmt = $pdo->query("SELECT COUNT(*) FROM users");
            $count = $stmt->fetchColumn();
            if ($count == 0) {
                 header("Location: install.php");
                 exit;
            }
        }
    } catch (Exception $e) {
        if (basename($_SERVER['PHP_SELF']) !== 'install.php') {
            header("Location: install.php");
            exit;
        }
    }
} else {
    // If not installed and not on install page, redirect
    if (basename($_SERVER['PHP_SELF']) !== 'install.php') {
        header("Location: install.php?msg=missing_config");
        exit;
    }
}

function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
}

function formatCurrency($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

function formatDate($date) {
    return date('d M Y', strtotime($date));
}

function getSettings($pdo) {
    $stmt = $pdo->query("SELECT * FROM settings WHERE id = 1");
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getFlash($key) {
    if (isset($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return null;
}

function setFlash($key, $msg) {
    $_SESSION['flash'][$key] = $msg;
}
?>
