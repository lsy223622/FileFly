<?php
require 'db_credentials.php';

// Set maximum file size (in bytes)
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10 MB

try {
    // Database connection configuration
    $db = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if the table exists, if not, create it
    $db->exec("CREATE TABLE IF NOT EXISTS `files` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `code` varchar(6) NOT NULL,
        `original_name` varchar(255) NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `code` (`code`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Handle upload request
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_FILES['file'])) {
            // Check file size
            if ($_FILES['file']['size'] > MAX_FILE_SIZE) {
                echo json_encode(['success' => false, 'message' => 'File size exceeds the limit']);
                exit;
            }

            $originalName = $_FILES['file']['name'];
            $code = generateRandomCode();
            $newFilename = 'files/' . $code;

            if (move_uploaded_file($_FILES['file']['tmp_name'], $newFilename)) {
                $stmt = $db->prepare("INSERT INTO files (code, original_name) VALUES (?, ?)");
                $stmt->execute([$code, $originalName]);

                echo json_encode(['success' => true, 'code' => $code]);
            } else {
                echo json_encode(['success' => false, 'message' => 'File upload failed']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'No file received']);
        }
    }

    // Handle download request
    elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['code'])) {
        $code = sanitizeCode($_GET['code']);

        if (!isValidCode($code)) {
            echo json_encode(['success' => false, 'message' => 'Invalid code format']);
            exit;
        }

        $stmt = $db->prepare("SELECT original_name FROM files WHERE code = ?");
        $stmt->execute([$code]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $filePath = 'files/' . $code;
            if (file_exists($filePath)) {
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . $result['original_name'] . '"');
                readfile($filePath);
                unlink($filePath);

                $stmt = $db->prepare("DELETE FROM files WHERE code = ?");
                $stmt->execute([$code]);
            } else {
                echo json_encode(['success' => false, 'message' => 'File does not exist']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid code']);
        }
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

// Generate random 6-digit code (only numbers and uppercase letters)
function generateRandomCode(): string
{
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $code = '';
    for ($i = 0; $i < 6; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $code;
}

// Sanitize and validate the input code
function sanitizeCode($code): string
{
    return strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $code));
}

// Check if the code is valid (6 characters, only letters and numbers)
function isValidCode($code): bool
{
    return preg_match('/^[A-Z0-9]{6}$/', $code) === 1;
}
