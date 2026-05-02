<?php

require_once 'Repository.php';
require_once __DIR__ . '/../models/Character.php';

class CharacterRepository extends Repository
{

    public function getCharactersByUserId(int $userId): array
    {
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

    public function addCharacter(string $name, string $description, string $image, int $userId, int $templateId): void
    {
        $stmt = $this->database->connect()->prepare('
        INSERT INTO characters (name, description, image, id_user, id_template)
        VALUES (?, ?, ?, ?, ?)
    ');

        $stmt->execute([
            $name,
            $description,
            $image,
            $userId,
            $templateId
        ]);
    }

    public function getCharacterById(int $id): ?Character
    {
        $stmt = $this->database->connect()->prepare('
            SELECT * FROM characters WHERE id = :id
        ');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $char = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$char) {
            return null;
        }

        return new Character(
            $char['name'],
            $char['description'],
            $char['image'],
            $char['id_user'],
            $char['id'],
            $char['id_template']
        );
    }

    public function updateCharacter(int $id, string $name, string $description, string $image, ?int $templateId): void
    {
        $stmt = $this->database->connect()->prepare('
            UPDATE characters 
            SET name = ?, description = ?, image = ?, id_template = ?
            WHERE id = ?
        ');
        $stmt->execute([$name, $description, $image, $templateId, $id]);
    }

    public function getCharacterFieldValues(int $characterId): array
    {
        $stmt = $this->database->connect()->prepare('
            SELECT id_template_field, value 
            FROM character_field_values 
            WHERE id_character = :characterId
        ');
        $stmt->bindParam(':characterId', $characterId, PDO::PARAM_INT);
        $stmt->execute();
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Zwracamy tablicę asocjacyjną [id_field => value] dla łatwego odczytu w widoku
        $mapped = [];
        foreach ($results as $row) {
            $mapped[$row['id_template_field']] = $row['value'];
        }
        
        return $mapped;
    }

    public function saveCharacterFieldValues(int $characterId, array $fieldValues): void
    {
        $db = $this->database->connect();
        try {
            $db->beginTransaction();

            // Dla wygody najpierw czyścimy stare wartości dla tej postaci
            $stmtDel = $db->prepare('DELETE FROM character_field_values WHERE id_character = :charId');
            $stmtDel->bindParam(':charId', $characterId, PDO::PARAM_INT);
            $stmtDel->execute();

            // Wstawiamy nowe wartości
            $stmtInsert = $db->prepare('
                INSERT INTO character_field_values (id_character, id_template_field, value)
                VALUES (?, ?, ?)
            ');

            foreach ($fieldValues as $fieldId => $value) {
                if ($value !== null && $value !== '') {
                    $stmtInsert->execute([$characterId, $fieldId, $value]);
                }
            }

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }
}