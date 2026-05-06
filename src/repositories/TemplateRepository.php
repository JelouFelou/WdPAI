<?php

require_once 'Repository.php';
require_once __DIR__ . '/../models/Template.php';

class TemplateRepository extends Repository
{

    public function getTemplate(int $id): ?Template
    {
        $stmt = $this->database->connect()->prepare('
            SELECT * FROM templates WHERE id = :id
        ');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $template = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$template) {
            return null;
        }

        return new Template(
            $template['name'],
            $template['description'],
            $template['id_user'],
            $template['id'],
            $this->getTemplateFields($id)
        );
    }

    public function getTemplateFields(int $templateId): array
    {
        $stmt = $this->database->connect()->prepare('
            SELECT * FROM template_fields 
            WHERE id_template = :id 
            ORDER BY location DESC, order_number ASC
        ');
        $stmt->bindParam(':id', $templateId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTemplatesByUserId(int $userId): array
    {
        $result = [];
        $stmt   = $this->database->connect()->prepare('
            SELECT * FROM templates WHERE id_user = :userId
        ');
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($templates as $temp) {
            $result[] = new Template(
                $temp['name'],
                $temp['description'],
                $temp['id_user'],
                $temp['id']
            );
        }
        return $result;
    }

    public function addTemplate(string $name, string $description, int $userId, array $fields): void
    {
        $db = $this->database->connect();

        try {
            $db->beginTransaction();

            $stmt = $db->prepare('
                INSERT INTO templates (name, description, id_user)
                VALUES (?, ?, ?) RETURNING id
            ');
            $stmt->execute([$name, $description, $userId]);
            $templateId = $stmt->fetchColumn();

            // Teraz zapisujemy też kolumnę placeholder (JSON z wierszami tabeli lub pusty string)
            $stmtField = $db->prepare('
                INSERT INTO template_fields (id_template, label, field_type, location, order_number, placeholder)
                VALUES (?, ?, ?, ?, ?, ?)
            ');

            foreach ($fields as $index => $field) {
                $stmtField->execute([
                    $templateId,
                    $field['label'],
                    $field['type']        ?? 'text',
                    $field['location']    ?? 'left',
                    $index,
                    $field['placeholder'] ?? '',
                ]);
            }

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public function deleteTemplate(int $id, int $userId): bool
    {
        $stmt = $this->database->connect()->prepare('
            DELETE FROM templates WHERE id = :id AND id_user = :userId
        ');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function getTemplateWithFields(int $id): ?array
    {
        $stmt = $this->database->connect()->prepare('
            SELECT * FROM templates WHERE id = :id
        ');
        $stmt->execute(['id' => $id]);
        $template = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$template) return null;

        $stmtFields = $this->database->connect()->prepare('
            SELECT * FROM template_fields WHERE id_template = :id ORDER BY order_number ASC
        ');
        $stmtFields->execute(['id' => $id]);
        $template['fields'] = $stmtFields->fetchAll(PDO::FETCH_ASSOC);

        return $template;
    }

    public function updateTemplate(int $id, string $name, string $description, array $fields): void
    {
        $db = $this->database->connect();
        try {
            $db->beginTransaction();

            // 1. Aktualizacja nazwy i opisu
            $stmt = $db->prepare('UPDATE templates SET name = ?, description = ? WHERE id = ?');
            $stmt->execute([$name, $description, $id]);

            // 2. Usunięcie starych pól
            $stmtDel = $db->prepare('DELETE FROM template_fields WHERE id_template = ?');
            $stmtDel->execute([$id]);

            // 3. Wstawienie aktualnych pól (z placeholder)
            $stmtField = $db->prepare('
                INSERT INTO template_fields (id_template, label, field_type, location, order_number, placeholder)
                VALUES (?, ?, ?, ?, ?, ?)
            ');

            foreach ($fields as $index => $field) {
                $stmtField->execute([
                    $id,
                    $field['label'],
                    $field['type']        ?? 'text',
                    $field['location']    ?? 'left',
                    $index,
                    $field['placeholder'] ?? '',
                ]);
            }

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }
}