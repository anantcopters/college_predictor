<?php
require_once __DIR__ . '/config.php';
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo APP_NAME; ?></title>
</head>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../public/assets/css/style.css">
<body>

<h1><?php echo APP_NAME; ?></h1>

<nav>
    <a href="index.php">Search</a> |
    <a href="upload.php">Upload Excel</a>
</nav>