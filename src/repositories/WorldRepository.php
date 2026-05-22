<?php

require_once 'Repository.php';
require_once __DIR__ . '/../models/World.php';

class WorldRepository extends Repository
{
    public function getWorldsByUserId(int $userId): array
    {
        $stmt = $this->database->connect()->prepare(
            'SELECT * FROM worlds WHERE id_user = :userId ORDER BY parent_id NULLS FIRST, id ASC'
        );
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $worlds = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result = [];
        foreach ($worlds as $world) {
            $result[] = $this->hydrate($world);
        }

        return $result;
    }

    /**
     * Zwraca bezpośrednie podfoldery danego folderu.
     * Dla folderu głównego ($parentId = null) zwraca foldery bez rodzica.
     */
    public function getChildWorlds(int $userId, ?int $parentId): array
    {
        if ($parentId === null) {
            $stmt = $this->database->connect()->prepare(
                'SELECT * FROM worlds WHERE id_user = :userId AND parent_id IS NULL ORDER BY id ASC'
            );
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        } else {
            $stmt = $this->database->connect()->prepare(
                'SELECT * FROM worlds WHERE id_user = :userId AND parent_id = :parentId ORDER BY id ASC'
            );
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':parentId', $parentId, PDO::PARAM_INT);
        }

        $stmt->execute();
        $result = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $world) {
            $result[] = $this->hydrate($world);
        }

        return $result;
    }

    public function getWorldByIdAndUserId(int $id, int $userId): ?World
    {
        $stmt = $this->database->connect()->prepare(
            'SELECT * FROM worlds WHERE id = :id AND id_user = :userId'
        );
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $world = $stmt->fetch(PDO::FETCH_ASSOC);
        return $world ? $this->hydrate($world) : null;
    }

    public function addWorld(string $name, string $description, int $userId, ?int $parentId = null, ?string $image = 'default.jpg'): int
    {
        $stmt = $this->database->connect()->prepare(
            'INSERT INTO worlds (name, description, image, id_user, parent_id) VALUES (?, ?, ?, ?, ?) RETURNING id'
        );
        $stmt->execute([$name, $description, $image, $userId, $parentId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Buduje ścieżkę breadcrumb od folderu głównego do podanego $worldId.
     * Zwraca tablicę World obiektów od najwyższego do bieżącego.
     */
    public function getBreadcrumb(int $worldId, int $userId): array
    {
        $path  = [];
        $current = $this->getWorldByIdAndUserId($worldId, $userId);

        while ($current !== null) {
            array_unshift($path, $current);
            $parentId = $current->getParentId();
            $current  = $parentId ? $this->getWorldByIdAndUserId($parentId, $userId) : null;
        }

        return $path;
    }

    private function hydrate(array $row): World
    {
        return new World(
            $row['name'],
            $row['description'],
            $row['image'],
            $row['id_user'],
            $row['id'],
            $row['parent_id'] !== null ? (int)$row['parent_id'] : null
        );
    }
}