<?php

// Ważne: DashboardController i AppController są w tym samym folderze, 
// więc wystarczy zwykły require_once
require_once 'AppController.php';

class DashboardController extends AppController {
    public function index() {
        $title = "OC Studio - Dashboard";
        
        // Renderujemy widok 'dashboard'
        return $this->render('dashboard', ["title" => $title]);
    }
}