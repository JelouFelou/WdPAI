<?php

require_once 'src/controllers/SecurityController.php';
require_once 'src/controllers/DashboardController.php';

Class Routing {

    public static $routes = [
        "login" => [
            "controller" => "SecurityController",
            "action" => "login"
        ],
        "dashboard" => [
            "controller" => "DashboardController",
            "action" => "index"
        ],
        "" => [
            "controller" => "SecurityController",
            "action" => "login"
        ],
        "register" => [
            "controller" => "SecurityController",
            "action" => "register"
        ],
    ];

    public static function run(string $path) {
        // TODO sprawdzać za pomoca array_key_exists
        switch($path) {
            case 'dashboard':
            case '':
            case 'index':
            case 'register':
            case 'login':
                $controller = Routing::$routes[$path]["controller"];
                $action = Routing::$routes[$path]["action"];

                $controllerObj = new $controller;
                $id = null;

                $controllerObj->$action($id);
                break; 
            default:
                include 'public/views/404.html';
                break;
        }
    }
}