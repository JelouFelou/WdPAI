<?php

require_once 'AppController.php';
require_once __DIR__.'/../repositories/UserRepository.php';
require_once __DIR__.'/../repositories/CharacterRepository.php';
require_once __DIR__.'/../repositories/TemplateRepository.php';
require_once __DIR__.'/../repositories/WorldRepository.php';
require_once __DIR__.'/../repositories/CharacterStatusRepository.php';

class DashboardController extends AppController {
    public function index() {
        $this->requireLogin();

        $usersRepository = new UsersRepository();
        $users = $usersRepository->getUsers();

        $characterRepository = new CharacterRepository();
        $allCharacters = $characterRepository->getCharactersByUserId($_SESSION['user_id']);
        $numberOfCharacters = count($allCharacters);

        $templateRepository = new TemplateRepository();
        $templates = $templateRepository->getTemplatesByUserId($_SESSION['user_id']);
        $numberOfTemplates = count($templates);

        $worldRepository = new WorldRepository();
        $worlds = $worldRepository->getWorldsByUserId($_SESSION['user_id']);
        $numberOfWorlds = count($worlds);

        $statusRepository = new CharacterStatusRepository();
        $statuses = $statusRepository->getAllStatuses();

        // --- Losowanie 6 postaci z priorytetem "niedokończonych" ---
        // Niedokończona = brak własnego zdjęcia LUB brak przypisanego szablonu
        $incomplete = [];
        $complete   = [];

        foreach ($allCharacters as $char) {
            $noImage    = !$char->getImage() || in_array($char->getImage(), ['default.png', 'default.jpg', '']);
            $noTemplate = $char->getIdTemplate() === null;

            if ($noImage || $noTemplate) {
                $incomplete[] = $char;
            } else {
                $complete[] = $char;
            }
        }

        // Losujemy kolejność w obu grupach
        shuffle($incomplete);
        shuffle($complete);

        // Bierzemy najpierw z niedokończonych, dopełniamy ukończonymi, max 6
        $dashboardChars = array_slice(
            array_merge($incomplete, $complete),
            0,
            6
        );

        return $this->render('dashboard', [
            "title"          => "OCStudio - Dashboard",
            "users"          => $users,
            "characters"     => $dashboardChars,
            "characterCount" => $numberOfCharacters,
            "templateCount"  => $numberOfTemplates,
            "worldCount"     => $numberOfWorlds,
            "statuses"       => $statuses,
        ]);
    }
}