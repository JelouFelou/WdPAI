<?php

require_once 'AppController.php';
require_once __DIR__ . '/../repositories/TemplateRepository.php';

class TemplateController extends AppController
{
    private $templateRepository;

    public function __construct()
    {
        $this->templateRepository = new TemplateRepository();
    }

    public function templates()
    {
        $this->requireLogin();

        // Pobieramy szablony zalogowanego użytkownika
        $templates = $this->templateRepository->getTemplatesByUserId($_SESSION['user_id']);

        $this->render('templates', [
            'title' => 'OCStudio - Szablony',
            'templates' => $templates
        ]);
    }

    public function createTemplate()
{
    $this->requireLogin();

    if ($this->isPost()) {
        $name = $_POST['template_name'];
        $description = $_POST['template_description'];
        $userId = $_SESSION['user_id'];
        $templateId = $_POST['template_id'] ?? null; // Pobieramy ID jeśli istnieje

        // Przygotowanie pól
        $fields = [];
        $labels = $_POST['field_labels'] ?? [];
        $locations = $_POST['field_locations'] ?? [];
        $types = $_POST['field_types'] ?? [];

        foreach ($labels as $index => $label) {
            $fields[] = [
                'label' => $label,
                'location' => $locations[$index],
                'type' => $types[$index]
            ];
        }

        try {
            if ($templateId) {
                // Jeśli mamy ID, robimy UPDATE
                $this->templateRepository->updateTemplate((int)$templateId, $name, $description, $fields);
            } else {
                // Jeśli nie ma ID, robimy ADD
                $this->templateRepository->addTemplate($name, $description, $userId, $fields);
            }
            header("Location: /templates");
        } catch (Exception $e) {
            header("Location: /dashboard");
        }
        return;
    }

    $this->render('create_template', ['title' => 'Nowy Szablon']);
}

    public function deleteTemplate()
    {
        $id = $_GET['id'];
        $this->templateRepository->deleteTemplate($id, $_SESSION['user_id']);
        header("Location: /templates");
    }

    public function duplicateTemplate()
    {
        if ($this->isPost()) {
            $id = $_POST['id'];
            $newName = $_POST['new_name'];

            $original = $this->templateRepository->getTemplateWithFields($id);
            if ($original) {
                $this->templateRepository->addTemplate(
                    $newName,
                    $original['description'],
                    $_SESSION['user_id'],
                    $original['fields']
                );
            }
        }
        header("Location: /templates");
    }

    public function editTemplate()
    {
        $id = $_GET['id'];
        $template = $this->templateRepository->getTemplateWithFields($id);

        // Tutaj renderujemy ten sam widok co przy tworzeniu, 
        // ale przekazujemy dane istniejącego szablonu
        $this->render('create_template', [
            'template' => $template,
            'isEdit' => true
        ]);
    }

    
}