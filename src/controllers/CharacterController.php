<?php

require_once 'AppController.php';
require_once __DIR__ . '/../repositories/CharacterRepository.php';
require_once __DIR__ . '/../repositories/TemplateRepository.php';

class CharacterController extends AppController
{
    private $characterRepository;
    private $templateRepository;

    public function __construct()
    {
        $this->characterRepository = new CharacterRepository();
        $this->templateRepository = new TemplateRepository();
    }

    public function createCharacter()
    {
        $this->requireLogin();

        // Jeśli wysłano formularz, zapisujemy postać
        if ($this->isPost()) {
            $name = $_POST['character_name'];
            $description = $_POST['character_description'];
            $templateId = $_POST['template_id'];
            $userId = $_SESSION['user_id'];

            // Przykładowy obrazek domyślny lub pobrany z formularza
            $image = 'default.jpg';

            // Zapis do bazy (zakładamy, że dodasz metodę addCharacter w CharacterRepository)
            $this->characterRepository->addCharacter($name, $description, $image, $userId, $templateId);

            header('Location: /dashboard');
            exit();
        }

        // Pobieramy szablony, aby użytkownik mógł wybrać, na podstawie którego schematu tworzy postać
        $templates = $this->templateRepository->getTemplatesByUserId($_SESSION['user_id']);

        return $this->render('create_character', [
            'title' => 'Stwórz postać - OCStudio',
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

        $id = (int) $_GET['id'];
        $template = $this->templateRepository->getTemplate($id); // Zwraca obiekt Template (zawierający pola)

        // Ponieważ TemplateRepository posiada metodę getTemplateFields(id):
        $fields = $this->templateRepository->getTemplateFields($id);

        echo json_encode([
            'id' => $template->getId(),
            'name' => $template->getName(),
            'description' => $template->getDescription(),
            'fields' => $fields
        ]);
        exit();
    }

    public function editCharacter()
    {
        $this->requireLogin();

        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: /dashboard');
            exit();
        }

        $character = $this->characterRepository->getCharacterById($id);

        if ($this->isPost()) {
            $name = $_POST['character_name'];
            $description = $_POST['character_description'];
            $templateId = $_POST['template_id'];

            $image = $character ? $character->getImage() : 'default.jpg';
            $this->characterRepository->updateCharacter($id, $name, $description, $image, $templateId);

            // Zapisz/zaktualizuj wartości pól
            if (isset($_POST['field_values'])) {
                $this->characterRepository->saveCharacterFieldValues($id, $_POST['field_values']);
            }

            header('Location: /dashboard');
            exit();
        }

        $templates = $this->templateRepository->getTemplatesByUserId($_SESSION['user_id']);

        $characterValues = [];
        if ($character) {
            $characterValues = $this->characterRepository->getCharacterFieldValues($character->getId());
        }

        return $this->render('create_character', [
            'title' => 'Edytuj postać - OCStudio',
            'character' => $character,
            'templates' => $templates,
            'characterFieldValues' => $characterValues
        ]);
    }
}