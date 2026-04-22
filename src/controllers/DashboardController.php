<?php

// Ważne: DashboardController i AppController są w tym samym folderze, 
// więc wystarczy zwykły require_once
require_once 'AppController.php';
require_once __DIR__.'/../repositories/UserRepository.php';
require_once __DIR__.'/../repositories/CharacterRepository.php';

class DashboardController extends AppController {
    public function index() {
        $this->requireLogin();

        $title = "OC Studio - Dashboard";
        $usersRepository = new UsersRepository();
        $users = $usersRepository->getUsers();

        $characterRepository = new CharacterRepository();
        $characters = $characterRepository->getCharactersByUserId($_SESSION['user_id']);
        $numberOfCharacters = count($characters);

        return $this->render('dashboard', [
            "title" => "OCStudio - Dashboard", 
            "users" => $users,
            "characters" => $characters,
            "characterCount" => $numberOfCharacters
        ]);
    }
}