<?php

require_once 'AppController.php';

class SettingsController extends AppController
{
    public function index(): void
    {
        $this->requireLogin();

        if ($this->isPost()) {
            $theme = $_POST['theme'] ?? 'light';
            $accent = $_POST['accent'] ?? 'orange';
            $columns = (int)($_POST['columns'] ?? 4);

            if (!in_array($theme, ['light', 'dark'], true)) {
                $theme = 'light';
            }
            if (!in_array($accent, ['orange', 'green', 'blue', 'purple', 'rose'], true)) {
                $accent = 'orange';
            }
            $columns = max(4, min(10, $columns));

            $expires = time() + 60 * 60 * 24 * 365;
            setcookie('oc_theme', $theme, ['expires' => $expires, 'path' => '/', 'samesite' => 'Lax']);
            setcookie('oc_accent', $accent, ['expires' => $expires, 'path' => '/', 'samesite' => 'Lax']);
            setcookie('oc_columns', (string)$columns, ['expires' => $expires, 'path' => '/', 'samesite' => 'Lax']);

            header('Location: /settings?saved=1');
            exit();
        }

        $this->render('settings', [
            'title' => 'Settings - OCStudio',
            'settingsSaved' => isset($_GET['saved']),
        ]);
    }
}
