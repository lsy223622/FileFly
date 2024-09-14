<?php
class FileHandler
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->createTableIfNotExists();
    }

    private function createTableIfNotExists()
    {
        $this->db->exec("CREATE TABLE IF NOT EXISTS `files` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `code` varchar(6) NOT NULL,
            `original_name` varchar(255) NOT NULL,
            `file_extension` varchar(10) NOT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `code` (`code`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }

    public function handleUpload()
    {
        if (empty($_FILES) && empty($_POST) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
            throw new Exception('File size exceeds PHP limits');
        }

        if (!isset($_FILES['file'])) {
            throw new Exception('No file received');
        }

        $file = $_FILES['file'];
        if ($file['size'] > MAX_FILE_SIZE) {
            throw new Exception('File size exceeds the application limit');
        }

        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExtension, ALLOWED_FILE_EXTENSIONS)) {
            throw new Exception('File type not allowed');
        }

        $code = $this->generateUniqueCode();
        $newFilename = 'files/' . $code;

        if (!move_uploaded_file($file['tmp_name'], $newFilename)) {
            throw new Exception('File upload failed');
        }

        $stmt = $this->db->prepare("INSERT INTO files (code, original_name, file_extension) VALUES (?, ?, ?)");
        $stmt->execute([$code, $file['name'], $fileExtension]);

        echo json_encode(['success' => true, 'code' => $code]);
    }

    public function handleDownload($code)
    {
        $code = $this->sanitizeCode($code);

        if (!$this->isValidCode($code)) {
            throw new Exception('Invalid code format');
        }

        $stmt = $this->db->prepare("SELECT original_name, file_extension FROM files WHERE code = ?");
        $stmt->execute([$code]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            throw new Exception('Invalid code');
        }

        $filePath = 'files/' . $code;
        if (!file_exists($filePath)) {
            throw new Exception('File does not exist');
        }

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . htmlspecialchars($result['original_name']) . '"');
        readfile($filePath);
        unlink($filePath);

        $stmt = $this->db->prepare("DELETE FROM files WHERE code = ?");
        $stmt->execute([$code]);
    }

    private function generateUniqueCode()
    {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        do {
            $code = '';
            for ($i = 0; $i < 6; $i++) {
                $code .= $characters[random_int(0, strlen($characters) - 1)];
            }
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM files WHERE code = ?");
            $stmt->execute([$code]);
        } while ($stmt->fetchColumn() > 0);
        return $code;
    }

    private function sanitizeCode($code)
    {
        return preg_replace('/[^A-Z0-9]/', '', strtoupper($code));
    }

    private function isValidCode($code)
    {
        return preg_match('/^[A-Z0-9]{6}$/', $code) === 1;
    }
}
