<?php
require 'config.php';
require 'FileHandler.php';

try {
    $db = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $fileHandler = new FileHandler($db);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $fileHandler->handleUpload();
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['code'])) {
        $fileHandler->handleDownload($_GET['code']);
    } else {
        throw new Exception('Invalid request');
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
