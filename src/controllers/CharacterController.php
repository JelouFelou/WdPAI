<?php

require_once 'AppController.php';
require_once __DIR__ . '/../repositories/CharacterRepository.php';
require_once __DIR__ . '/../repositories/TemplateRepository.php';
require_once __DIR__ . '/../services/ImageUploadService.php';

class CharacterController extends AppController
{
    private $characterRepository;
    private $templateRepository;

    public function __construct()
    {
        $this->characterRepository = new CharacterRepository();
        $this->templateRepository  = new TemplateRepository();
    }

    /**
     * Zamienia wartość POST na ?int – pusty string i "0" traktuje jako null.
     */
    private function parseTemplateId(mixed $raw): ?int
    {
        if ($raw === null || $raw === '' || $raw === '0') {
            return null;
        }
        $int = (int) $raw;
        return $int > 0 ? $int : null;
    }

    public function createCharacter()
    {
        $this->requireLogin();

        if ($this->isPost()) {
            $name        = $_POST['character_name']        ?? '';
            $description = $_POST['character_description'] ?? '';
            $templateId  = $this->parseTemplateId($_POST['template_id'] ?? null);
            $userId      = $_SESSION['user_id'];

            try {
                $image = $this->uploadCharacterImage('default.jpg');
            } catch (Throwable $e) {
                http_response_code(($e->getCode() >= 400 && $e->getCode() <= 599) ? $e->getCode() : 400);
                return $this->render('create_character', [
                    'title' => 'Stworz postac - OCStudio',
                    'templates' => $this->templateRepository->getTemplatesByUserId($_SESSION['user_id']),
                    'messages' => [$e->getMessage()]
                ]);
            }

            $characterId = $this->characterRepository->addCharacter($name, $description, $image, $userId, $templateId);

            if (isset($_POST['field_values']) && is_array($_POST['field_values'])) {
                $this->characterRepository->saveCharacterFieldValues($characterId, $_POST['field_values']);
            }

            header('Location: /dashboard');
            exit();
        }

        $templates = $this->templateRepository->getTemplatesByUserId($_SESSION['user_id']);

        return $this->render('create_character', [
            'title'     => 'Stwórz postać - OCStudio',
            'templates' => $templates
        ]);
    }

    public function getTemplateData()
    {
        header('Content-Type: application/json');

        if (!isset($_GET['id'])) {
            echo json_encode(['error' => 'Brak ID szablonu']);
            exit();
        }

        $id       = (int) $_GET['id'];
        $template = $this->templateRepository->getTemplate($id);

        if (!$template) {
            echo json_encode(['error' => 'Nie znaleziono szablonu']);
            exit();
        }

        $fields = $this->templateRepository->getTemplateFields($id);

        echo json_encode([
            'id'          => $template->getId(),
            'name'        => $template->getName(),
            'description' => $template->getDescription(),
            'fields'      => $fields
        ]);
        exit();
    }

    public function editCharacter()
    {
        $this->requireLogin();

        $id = isset($_GET['id']) ? (int) $_GET['id'] : null;
        if (!$id) {
            header('Location: /dashboard');
            exit();
        }

        $character = $this->characterRepository->getCharacterById($id);

        if (!$character) {
            header('Location: /dashboard');
            exit();
        }

        if ($this->isPost()) {
            $name        = $_POST['character_name']        ?? '';
            $description = $_POST['character_description'] ?? '';
            $templateId  = $this->parseTemplateId($_POST['template_id'] ?? null);

            try {
                $image = $this->uploadCharacterImage($character->getImage() ?: 'default.jpg');
            } catch (Throwable $e) {
                http_response_code(($e->getCode() >= 400 && $e->getCode() <= 599) ? $e->getCode() : 400);
                return $this->render('create_character', [
                    'title' => 'Edytuj postac - OCStudio',
                    'character' => $character,
                    'templates' => $this->templateRepository->getTemplatesByUserId($_SESSION['user_id']),
                    'characterFieldValues' => $this->characterRepository->getCharacterFieldValues($character->getId()),
                    'messages' => [$e->getMessage()]
                ]);
            }

            $this->characterRepository->updateCharacter($id, $name, $description, $image, $templateId);

            if (isset($_POST['field_values']) && is_array($_POST['field_values'])) {
                $this->characterRepository->saveCharacterFieldValues($id, $_POST['field_values']);
            }

            header('Location: /dashboard');
            exit();
        }

        $templates       = $this->templateRepository->getTemplatesByUserId($_SESSION['user_id']);
        $characterValues = $this->characterRepository->getCharacterFieldValues($character->getId());

        return $this->render('create_character', [
            'title'               => 'Edytuj postać - OCStudio',
            'character'           => $character,
            'templates'           => $templates,
            'characterFieldValues' => $characterValues
        ]);
    }

    private function uploadCharacterImage(string $fallback): string
    {
        $file = $_FILES['character_image'] ?? null;
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return $fallback;
        }

        $uploaded = (new ImageUploadService())->upload($file);
        return $uploaded['filename'];
    }
}
