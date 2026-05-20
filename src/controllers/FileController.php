<?php

require_once 'AppController.php';
require_once __DIR__ . '/../services/ImageUploadService.php';

class FileController extends AppController
{
    public function uploadFile(): void
    {
        header('Content-Type: application/json');
        $this->requireLogin();

        try {
            $uploaded = (new ImageUploadService())->upload($_FILES['file'] ?? []);
            echo json_encode($uploaded);
        } catch (Throwable $e) {
            $code = $e->getCode();
            http_response_code(($code >= 400 && $code <= 599) ? $code : 500);
            echo json_encode(['error' => $e->getMessage()]);
        }

        exit();
    }
}
