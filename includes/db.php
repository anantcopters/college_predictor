<?php

require_once __DIR__ . '/config.local.php';
require_once __DIR__ . '/../classes/Database.php';

$db = new Database(
    DB_HOST,
    DB_PORT,
    DB_NAME,
    DB_USER,
    DB_PASS
);

$pdo = $db->connect();