<?php

require_once 'Repository.php';
require_once __DIR__.'/../models/Character.php';

class CharacterRepository extends Repository {

    public function getCharactersByUserId(int $userId): array {
        $result = [];

        $stmt = $this->database->connect()->prepare('
            SELECT * FROM characters WHERE id_user = :userId
        ');
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $characters = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($characters as $char) {
            $result[] = new Character(
                $char['name'],
                $char['description'],
                $char['image'],
                $char['id_user'],
                $char['id']
            );
        }

        return $result;
    }
}