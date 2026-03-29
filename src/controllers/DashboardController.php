<?php

require_once 'AppController.php';
class DashboardController extends AppController{
    public function index(){
        $title = "WDPAI - dashboard";
        // TODO read data from db
        
        return $this->render('dashboard', ["title" => $title]);
    }
}