<?php

require_once 'AppController.php';

class FileController extends AppController
{
    /**
     * POST /uploadFile
     * Zapisuje plik do <project_root>/public/uploads/
     * Zachowuje oryginalną nazwę pliku.
     * Jeśli plik o tej nazwie już istnieje, dopisuje licznik: zdjecie.png → zdjecie1.png → zdjecie2.png …
     * Zwraca JSON: { url: "/public/uploads/nazwa.png", filename: "nazwa.png" }
     */
    public function uploadFile(): void
    {
        header('Content-Type: application/json');
        $this->requireLogin();

        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['error' => 'Brak pliku lub błąd przesyłania.']);
            exit();
        }

        $file     = $_FILES['file'];
        $mimeType = mime_content_type($file['tmp_name']);

        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif'];
        if (!in_array($mimeType, $allowed)) {
            http_response_code(415);
            echo json_encode(['error' => 'Niedozwolony typ pliku. Dozwolone: jpg, png, gif, webp, avif.']);
            exit();
        }

        if ($file['size'] > 8 * 1024 * 1024) {
            http_response_code(413);
            echo json_encode(['error' => 'Plik jest zbyt duży (max 8 MB).']);
            exit();
        }

        // Katalog uploads – relatywnie od katalogu głównego projektu
        // __DIR__ to src/controllers, więc cofamy się dwa poziomy
        $uploadDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Sanityzacja oryginalnej nazwy – zostawiamy litery, cyfry, kropki, myślniki, podkreślenia
        $originalName = $_FILES['file']['name'];
        $safeName     = $this->sanitizeFilename($originalName);

        // Rozdzielamy na nazwę bazową i rozszerzenie
        $ext      = strtolower(pathinfo($safeName, PATHINFO_EXTENSION));
        $baseName = pathinfo($safeName, PATHINFO_FILENAME);

        // Upewniamy się że rozszerzenie zgadza się z faktycznym typem MIME
        $ext = $this->mimeToExt($mimeType) ?? $ext;

        // Szukamy wolnej nazwy: zdjecie.png → zdjecie1.png → zdjecie2.png …
        $filename    = $baseName . '.' . $ext;
        $counter     = 1;
        while (file_exists($uploadDir . $filename)) {
            $filename = $baseName . $counter . '.' . $ext;
            $counter++;
        }

        $destination = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            http_response_code(500);
            echo json_encode(['error' => 'Nie udało się zapisać pliku na serwerze.']);
            exit();
        }

        echo json_encode([
            'url'      => '/public/uploads/' . $filename,
            'filename' => $filename,
        ]);
        exit();
    }

    // -------------------------------------------------------
    // Pomocnicze
    // -------------------------------------------------------

    /**
     * Czyści nazwę pliku – usuwa znaki specjalne, spacje zamienia na podkreślenia.
     */
    private function sanitizeFilename(string $name): string
    {
        // Dekoduj encje HTML na wypadek dziwnych nazw
        $name = html_entity_decode($name, ENT_QUOTES, 'UTF-8');

        // Zamień spacje i niedozwolone znaki
        $name = preg_replace('/\s+/', '_', $name);
        $name = preg_replace('/[^A-Za-z0-9._\-]/', '', $name);

        // Zabezpieczenie przed path traversal
        $name = basename($name);

        // Nie może być pusty
        if ($name === '' || $name === '.') {
            $name = 'plik';
        }

        return $name;
    }

    /**
     * Zwraca poprawne rozszerzenie na podstawie MIME type.
     */
    private function mimeToExt(string $mime): ?string
    {
        return match($mime) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
            'image/avif' => 'avif',
            default      => null,
        };
    }
}