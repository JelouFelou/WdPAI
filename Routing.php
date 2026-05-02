<?php

require_once 'src/controllers/SecurityController.php';
require_once 'src/controllers/DashboardController.php';
require_once 'src/controllers/TemplateController.php';
require_once 'src/controllers/CharacterController.php';
class Routing
{

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
        'createTemplate' => [
            'controller' => 'TemplateController',
            'action' => 'createTemplate'
        ],
        'templates' => [
            'controller' => 'TemplateController',
            'action' => 'templates'
        ],
        'deleteTemplate' => [
            'controller' => 'TemplateController',
            'action' => 'deleteTemplate'
        ],
        'duplicateTemplate' => [
            'controller' => 'TemplateController',
            'action' => 'duplicateTemplate'
        ],
        'editTemplate' => [
            'controller' => 'TemplateController',
            'action' => 'editTemplate'
        ],
        'createCharacter' => [
            'controller' => 'CharacterController',
            'action' => 'createCharacter'
        ],
        'getTemplateData' => [
            'controller' => 'CharacterController',
            'action' => 'getTemplateData'
        ],
        'editCharacter' => [
            'controller' => 'CharacterController',
            'action' => 'editCharacter'
        ],
    ];

    public static function run(string $path)
    {
        // TODO sprawdzać za pomoca array_key_exists
        switch ($path) {
            case 'dashboard':
            case '':
            case 'index':
            case 'register':
            case 'login':
            case 'templates':
            case 'createTemplate':
            case 'deleteTemplate':
            case 'duplicateTemplate':
            case 'editTemplate':
            case 'createCharacter':
            case 'getTemplateData':
            case 'editCharacter':
                $controller = Routing::$routes[$path]["controller"];
                $action = Routing::$routes[$path]["action"];

                $controllerObj = new $controller;
                $id = null;

                $controllerObj->$action($id);
                $urlParts = explode("?", $path);
                $actionKey = $urlParts[0]; // To przekazujemy do switcha
                break;
            default:
                include 'public/views/404.html';
                break;
        }
    }
}