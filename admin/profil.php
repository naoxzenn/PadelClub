<?php
session_start();

// Validasi Session Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$baseUrl = '../';
require_once __DIR__ . '/../profil.php';
