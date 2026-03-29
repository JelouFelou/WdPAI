<?php
require_once 'AppController.php';
class SecurityController extends AppController{
    public function login() {
        return $this->render('login');
    }

    public function register(){
        return $this->render('register');
    }

    public function logout(){
        return $this->render('logout');
    }
}