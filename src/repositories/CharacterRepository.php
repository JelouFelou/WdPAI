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
            $result[] = $this->hydrate($char);
        }

        return $result;
    }

    /**
     * Zwraca postacie w konkretnym folderze.
     * $worldId = null  → postacie bez przypisanego folderu (folder główny)
     */
    public function getCharactersByWorld(int $userId, ?int $worldId): array
    {
        if ($worldId === null) {
            $stmt = $this->database->connect()->prepare('
                SELECT * FROM characters
                WHERE id_user = :userId AND id_world IS NULL
                ORDER BY name ASC
            ');
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        } else {
            $stmt = $this->database->connect()->prepare('
                SELECT * FROM characters
                WHERE id_user = :userId AND id_world = :worldId
                ORDER BY name ASC
            ');
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':worldId', $worldId, PDO::PARAM_INT);
        }

        $stmt->execute();

        $result = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $char) {
            $result[] = $this->hydrate($char);
        }

        return $result;
    }

    public function addCharacter(string $name, string $description, string $image, int $userId, ?int $templateId, ?int $worldId = null): int
    {
        $stmt = $this->database->connect()->prepare('
            INSERT INTO characters (name, description, image, id_user, id_template, id_world)
            VALUES (?, ?, ?, ?, ?, ?)
            RETURNING id
        ');

        $stmt->execute([
            $name,
            $description,
            $image,
            $userId,
            $templateId,
            $worldId
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
        return $char ? $this->hydrate($char) : null;
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
        return $char ? $this->hydrate($char) : null;
    }

    public function updateCharacter(int $id, string $name, string $description, string $image, ?int $templateId, ?int $worldId = null): void
    {
        $stmt = $this->database->connect()->prepare('
            UPDATE characters 
            SET name = ?, description = ?, image = ?, id_template = ?, id_world = ?
            WHERE id = ?
        ');
        $stmt->execute([$name, $description, $image, $templateId, $worldId, $id]);
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

        $mapped = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
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

            $stmtDel = $db->prepare('DELETE FROM character_field_values WHERE id_character = :charId');
            $stmtDel->bindParam(':charId', $characterId, PDO::PARAM_INT);
            $stmtDel->execute();

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

    private function hydrate(array $char): Character
    {
        return new Character(
            $char['name'],
            $char['description'],
            $char['image'],
            $char['id_user'],
            $char['id'],
            $char['id_template'],
            $char['id_world'] ?? null
        );
    }
}