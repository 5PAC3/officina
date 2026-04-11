<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

session_start();
session_destroy();

echo json_encode(['success' => true, 'message' => 'Logout effettuato']);
?>