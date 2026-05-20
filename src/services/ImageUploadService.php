<?php

class ImageUploadService
{
    private const MAX_FILE_SIZE = 8 * 1024 * 1024;
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        'image/avif' => 'avif',
    ];

    public function upload(array $file): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('Brak pliku lub blad przesylania.', 400);
        }

        if (($file['size'] ?? 0) > self::MAX_FILE_SIZE) {
            throw new InvalidArgumentException('Plik jest zbyt duzy (max 8 MB).', 413);
        }

        $tmpName = $file['tmp_name'] ?? '';
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            throw new InvalidArgumentException('Nieprawidlowy plik uploadu.', 400);
        }

        $mimeType = mime_content_type($tmpName);
        if (!isset(self::ALLOWED_MIME_TYPES[$mimeType])) {
            throw new InvalidArgumentException('Niedozwolony typ pliku. Dozwolone: jpg, png, gif, webp, avif.', 415);
        }

        $uploadDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
            throw new RuntimeException('Nie udalo sie utworzyc katalogu uploads.', 500);
        }

        $extension = self::ALLOWED_MIME_TYPES[$mimeType];
        [$filename, $destination] = $this->reserveUniqueDestination($uploadDir, $extension);

        if (!move_uploaded_file($tmpName, $destination)) {
            @unlink($destination);
            throw new RuntimeException('Nie udalo sie zapisac pliku na serwerze.', 500);
        }

        return [
            'url' => '/public/uploads/' . $filename,
            'filename' => $filename,
        ];
    }

    private function reserveUniqueDestination(string $uploadDir, string $extension): array
    {
        do {
            $filename = 'img_' . date('Ymd_His') . '_' . bin2hex(random_bytes(16)) . '.' . $extension;
            $destination = $uploadDir . $filename;
            $handle = @fopen($destination, 'x');
        } while ($handle === false);

        fclose($handle);

        return [$filename, $destination];
    }
}
