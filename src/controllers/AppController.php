<?php

class AppController {
    protected function isGet(): bool
    {
        return $_SERVER["REQUEST_METHOD"] === 'GET';
    }

    protected function isPost(): bool
    {
        return $_SERVER["REQUEST_METHOD"] === 'POST';
    }

    protected function requireLogin()
    {
        if (!isset($_SESSION['user_id'])) {
            $url = "http://$_SERVER[HTTP_HOST]";
            header("Location: {$url}/login");
            exit();
        }
    }
 
    protected function render(?string $template = null, array $variables = [])
    {
        // Określamy ścieżkę bazową do widoków
        $basePath = __DIR__.'/../../public/views/';
        
        $templatePath = $basePath . $template . '.html';
        $headPath = $basePath . 'partials/head.html';
        $navPath = $basePath . 'partials/nav.html';
        
        if(file_exists($templatePath)){
            extract($variables);

            ob_start();
            
            // Dołączamy nagłówek
            if (file_exists($headPath)) {
                include $headPath;
            }

            // Jeśli to nie jest login, dołączamy nawigację
            if ($template !== 'login' && file_exists($navPath)) {
                include $navPath;
            }

            include $templatePath;

            // Domykamy tagi, jeśli to nie login
            if ($template !== 'login') {
                echo '    </main>'; 
                echo '</div>';      
            }
            
            echo '</body></html>';
            
            $output = ob_get_clean();
        } else {
            // Prosta informacja o braku pliku dla debugowania
            $output = "Błąd: Nie znaleziono pliku widoku: " . $templatePath;
        }
        
        echo $output;
    }
}