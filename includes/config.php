<?php

/*

define('DB_HOST', 'localhost');
define('DB_PORT', '5432');
define('DB_NAME', 'db_college_predictor');
define('DB_USER', 'admin');
define('DB_PASS', 'admin');

*/

declare(strict_types=1);
define('APP_NAME', 'Previous Year College Predictor ');
define('BASE_PATH', dirname(__DIR__));

define('UPLOAD_DIR', __DIR__ . '/../storage/uploads/');
define('LOG_DIR', __DIR__ . '/../storage/logs/');

// Detect base URL automatically
$scriptName = dirname($_SERVER['SCRIPT_NAME']);

define('BASE_URL', rtrim(str_replace('\\', '/', $scriptName), '/'));
/*
|--------------------------------------------------------------------------
| Default Configuration
|--------------------------------------------------------------------------
*/

$config = [

    'app' => [

        'name' => 'College Predictor',

        'debug' => true,

    ],

    'db' => [

        'host' => 'localhost',
        'port' => 5432,
        'database' => 'db_college_predictor',
        'username' => '',
        'password' => '',

    ],

    'upload' => [

        'directory' => realpath(__DIR__ . '/../uploads') . DIRECTORY_SEPARATOR,
        'max_size'  => 10 * 1024 * 1024,

    ],

];

/*
|--------------------------------------------------------------------------
| Local Configuration
|--------------------------------------------------------------------------
*/

$localConfig = __DIR__ . '/config.local.php';

if (file_exists($localConfig)) {
    require $localConfig;
}

/*
|--------------------------------------------------------------------------
| Error Reporting
|--------------------------------------------------------------------------
*/

if ($config['app']['debug']) {

    ini_set('display_errors', 1);
    error_reporting(E_ALL);

} else {

    ini_set('display_errors', 0);
    error_reporting(E_ALL);

}