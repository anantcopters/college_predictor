<?php
require_once __DIR__ . '/config.php';
?>

<!DOCTYPE html>
<html>

<head>
    <title><?php echo APP_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>

<body>

    <h1><?php echo APP_NAME; ?></h1>

    <nav>
        <a href="<?= BASE_URL ?>/index.php">JoSAA Counselling</a> |
        <a href="<?= BASE_URL ?>/iat.php">IAT Counselling</a> |
        <a href="<?= BASE_URL ?>/upload.php">Upload JoSAA Excel</a> |
        <a href="<?= BASE_URL ?>/iat_admin.php">IAT Admin</a>
    </nav>