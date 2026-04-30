<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../configs/config.php';

echo json_encode([
    'API_BASE' => rtrim($_ENV['APP_URL'] ?? 'http://localhost/officina/api/', '/') . '/',
    'DEBUG' => filter_var($_ENV['APP_DEBUG'] ?? true, FILTER_VALIDATE_BOOLEAN)
]);
