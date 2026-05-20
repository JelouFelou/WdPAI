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
                $char['id'],
                $char['id_template']
            );
        }

        return $result;
    }

    public function addCharacter(string $name, string $description, string $image, int $userId, ?int $templateId): int
    {
        $stmt = $this->database->connect()->prepare('
        INSERT INTO characters (name, description, image, id_user, id_template)
        VALUES (?, ?, ?, ?, ?)
        RETURNING id
    ');

        $stmt->execute([
            $name,
            $description,
            $image,
            $userId,
            $templateId
        ]);

        return (int)$stmt->fetchColumn();
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

    public function getCharacterByIdAndUserId(int $id, int $userId): ?Character
    {
        $stmt = $this->database->connect()->prepare('
            SELECT * FROM characters WHERE id = :id AND id_user = :userId
        ');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
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

    public function getCharacterVariants(int $characterId): array
    {
        $stmt = $this->database->connect()->prepare('
            SELECT * FROM character_variants
            WHERE id_character = :characterId
            ORDER BY order_number ASC, id ASC
        ');
        $stmt->bindParam(':characterId', $characterId, PDO::PARAM_INT);
        $stmt->execute();

        $variants = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($variants as &$variant) {
            $variant['values'] = $this->getVariantFieldValues((int)$variant['id']);
        }

        return $variants;
    }

    public function getCharacterVariant(int $variantId, int $characterId): ?array
    {
        $stmt = $this->database->connect()->prepare('
            SELECT * FROM character_variants
            WHERE id = :variantId AND id_character = :characterId
        ');
        $stmt->bindParam(':variantId', $variantId, PDO::PARAM_INT);
        $stmt->bindParam(':characterId', $characterId, PDO::PARAM_INT);
        $stmt->execute();

        $variant = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$variant) {
            return null;
        }

        $variant['values'] = $this->getVariantFieldValues((int)$variant['id']);
        return $variant;
    }

    public function replaceCharacterVariants(int $characterId, array $variants): void
    {
        $db = $this->database->connect();
        try {
            $db->beginTransaction();

            $stmtDel = $db->prepare('DELETE FROM character_variants WHERE id_character = ?');
            $stmtDel->execute([$characterId]);

            $stmtVariant = $db->prepare('
                INSERT INTO character_variants (id_character, name, image, order_number)
                VALUES (?, ?, ?, ?)
                RETURNING id
            ');
            $stmtValue = $db->prepare('
                INSERT INTO character_variant_field_values (id_variant, id_template_field, value)
                VALUES (?, ?, ?)
            ');

            foreach ($variants as $index => $variant) {
                $name = trim($variant['name'] ?? '');
                if ($name === '') {
                    continue;
                }

                $stmtVariant->execute([
                    $characterId,
                    $name,
                    $variant['image'] ?? null,
                    $index
                ]);
                $variantId = (int)$stmtVariant->fetchColumn();

                foreach (($variant['values'] ?? []) as $fieldId => $value) {
                    if ($value !== null && $value !== '') {
                        $stmtValue->execute([$variantId, $fieldId, $value]);
                    }
                }
            }

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    private function getVariantFieldValues(int $variantId): array
    {
        $stmt = $this->database->connect()->prepare('
            SELECT id_template_field, value
            FROM character_variant_field_values
            WHERE id_variant = :variantId
        ');
        $stmt->bindParam(':variantId', $variantId, PDO::PARAM_INT);
        $stmt->execute();

        $mapped = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
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
