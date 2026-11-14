<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$role = $_SESSION['user']['role'] ?? '';

if ($role === 'admin') {
    header("Location: dashboard.php");
    exit;
}

header("Location: user.php");
exit;
