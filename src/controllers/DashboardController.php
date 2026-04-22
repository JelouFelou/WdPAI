<?php

// Ważne: DashboardController i AppController są w tym samym folderze, 
// więc wystarczy zwykły require_once
require_once 'AppController.php';
require_once __DIR__.'/../repositories/UserRepository.php';

class DashboardController extends AppController {
    public function index() {
        $this->requireLogin();

        $title = "OC Studio - Dashboard";
        $usersRepository = new UsersRepository();
        $users = $usersRepository->getUsers();
        
        // Renderujemy widok 'dashboard'
        return $this->render('dashboard', ["title" => $title, "users" => $users]);
    }
}