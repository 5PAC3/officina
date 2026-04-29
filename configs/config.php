<?php
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? '');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');
define('APP_URL', rtrim($_ENV['APP_URL'] ?? 'http://localhost/officina', '/'));
define('API_BASE', APP_URL . '/api/');

error_reporting(E_ALL);
ini_set('display_errors', 1);
