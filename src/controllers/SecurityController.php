<?php
require_once 'AppController.php';
require_once __DIR__.'/../repositories/UserRepository.php';
class SecurityController extends AppController{
    private const MAX_EMAIL_LENGTH = 254;
    private const MAX_PASSWORD_LENGTH = 72;
    private const MIN_PASSWORD_LENGTH = 8;
    private const MAX_NAME_LENGTH = 50;
    private const MAX_FAILED_ATTEMPTS = 5;
    private const LOCKOUT_SECONDS = 300;
    private const CAPTCHA_AFTER_ATTEMPTS = 3;
    private const DUMMY_PASSWORD_HASH = '$2y$10$wH7QDEvH3sB/N/r5mgq36eSdAgkYGIT83wG/xYtpOvt3c1B.zvQQG';

    public function login()
    {
        if (!$this->ensureHttps()) {
            return;
        }

        if (!$this->isPost()) {
            return $this->render('login', [
                'showCaptcha' => $this->shouldRequireCaptcha(),
                'turnstileSiteKey' => $this->getTurnstileSiteKey()
            ]);
        }

        $email = trim($_POST["email"] ?? '');
        $password = $_POST["password"] ?? '';

        if ($this->isLockedOut()) {
            http_response_code(403);
            return $this->renderLoginError('Too many failed attempts. Try again later.');
        }

        if (!$this->isLoginInputValid($email, $password)) {
            http_response_code(400);
            $this->recordFailedLogin();
            return $this->renderLoginError('Invalid email or password.');
        }

        if ($this->shouldRequireCaptcha() && !$this->verifyTurnstile()) {
            http_response_code(403);
            return $this->renderLoginError('Captcha verification failed.');
        }

        $userRepository = new UsersRepository();
        $user = $userRepository->getUserByEmail($email);
        $passwordHash = $user ? $user->getPassword() : self::DUMMY_PASSWORD_HASH;

        if (!password_verify($password, $passwordHash) || !$user) {
            http_response_code(401);
            $this->recordFailedLogin();
            return $this->renderLoginError('Invalid email or password.');
        }

        $this->clearFailedLogins();
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user->getId();
        $_SESSION['email'] = $user->getEmail();
        $_SESSION['first_name'] = $user->getFirstName(); 
        $_SESSION['last_name'] = $user->getLastName();

        http_response_code(303);
        $url = $this->baseUrl();
        header("Location: {$url}/dashboard");
    }

    public function register()
    {
        if (!$this->ensureHttps()) {
            return;
        }

        if (!$this->isPost()) {
            return $this->render('register');
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $password2 = $_POST['password2'] ?? '';
        $firstName = trim($_POST['firstName'] ?? '');
        $lastName = trim($_POST['lastName'] ?? '');

        if ($password !== $password2) {
            http_response_code(400);
            return $this->render('register', ['messages' => ['Passwords do not match.']]);
        }

        $validationErrors = $this->validateRegistrationInput($email, $password, $firstName, $lastName);
        if ($validationErrors) {
            http_response_code(400);
            return $this->render('register', ['messages' => $validationErrors]);
        }

        $userRepository = new UsersRepository();
        if ($userRepository->getUserByEmail($email)) {
            http_response_code(409);
            return $this->render('register', ['messages' => ['User already exists.']]);
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        $userRepository->createUser($email, $hashedPassword, $firstName, $lastName);

        http_response_code(201);
        return $this->render('login', ['messages' => ['Registration successful! Please log in.']]);
    }

    public function logout(){
        return $this->render('logout');
    }

    private function ensureHttps(): bool
    {
        if ($this->isSecureRequest() || $this->isLocalRequest()) {
            return true;
        }

        if ($this->isPost()) {
            http_response_code(403);
            echo 'Login and registration are available only over HTTPS.';
            return false;
        }

        $url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        http_response_code(301);
        header("Location: {$url}");
        return false;
    }

    private function isSecureRequest(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
            || (($_SERVER['SERVER_PORT'] ?? '') === '443');
    }

    private function isLocalRequest(): bool
    {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        return strpos($host, 'localhost') === 0
            || strpos($host, '127.0.0.1') === 0
            || strpos($host, '[::1]') === 0;
    }

    private function baseUrl(): string
    {
        $scheme = $this->isSecureRequest() ? 'https' : 'http';
        return "{$scheme}://{$_SERVER['HTTP_HOST']}";
    }

    private function isLoginInputValid(string $email, string $password): bool
    {
        return $email !== ''
            && $password !== ''
            && strlen($email) <= self::MAX_EMAIL_LENGTH
            && strlen($password) <= self::MAX_PASSWORD_LENGTH
            && filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    private function validateRegistrationInput(
        string $email,
        string $password,
        string $firstName,
        string $lastName
    ): array {
        $errors = [];

        if ($email === '' || $password === '' || $firstName === '') {
            $errors[] = 'Fill all required fields.';
        }

        if (strlen($email) > self::MAX_EMAIL_LENGTH || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email is invalid or too long.';
        }

        if (strlen($firstName) > self::MAX_NAME_LENGTH || strlen($lastName) > self::MAX_NAME_LENGTH) {
            $errors[] = 'Name fields are too long.';
        }

        if (!$this->isPasswordComplex($password)) {
            $errors[] = 'Password must have at least 8 characters, uppercase and lowercase letters, and a digit.';
        }

        return $errors;
    }

    private function isPasswordComplex(string $password): bool
    {
        return strlen($password) >= self::MIN_PASSWORD_LENGTH
            && strlen($password) <= self::MAX_PASSWORD_LENGTH
            && preg_match('/[a-z]/', $password)
            && preg_match('/[A-Z]/', $password)
            && preg_match('/\d/', $password);
    }

    private function renderLoginError(string $message)
    {
        return $this->render('login', [
            'messages' => [$message],
            'showCaptcha' => $this->shouldRequireCaptcha(),
            'turnstileSiteKey' => $this->getTurnstileSiteKey()
        ]);
    }

    private function recordFailedLogin(): void
    {
        $now = time();
        $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
        $_SESSION['last_login_attempt'] = $now;

        if ($_SESSION['login_attempts'] >= self::MAX_FAILED_ATTEMPTS) {
            $_SESSION['login_locked_until'] = $now + self::LOCKOUT_SECONDS;
        }
    }

    private function clearFailedLogins(): void
    {
        unset($_SESSION['login_attempts'], $_SESSION['last_login_attempt'], $_SESSION['login_locked_until']);
    }

    private function isLockedOut(): bool
    {
        $lockedUntil = $_SESSION['login_locked_until'] ?? 0;
        if ($lockedUntil > time()) {
            return true;
        }

        if ($lockedUntil) {
            $this->clearFailedLogins();
        }

        return false;
    }

    private function shouldShowCaptcha(): bool
    {
        return ($_SESSION['login_attempts'] ?? 0) >= self::CAPTCHA_AFTER_ATTEMPTS;
    }

    private function shouldRequireCaptcha(): bool
    {
        return $this->shouldShowCaptcha() && $this->isTurnstileConfigured();
    }

    private function isTurnstileConfigured(): bool
    {
        return (bool)$this->getTurnstileSiteKey() && (bool)getenv('CLOUDFLARE_TURNSTILE_SECRET');
    }

    private function getTurnstileSiteKey(): ?string
    {
        return getenv('CLOUDFLARE_TURNSTILE_SITE_KEY') ?: null;
    }

    private function verifyTurnstile(): bool
    {
        $secret = getenv('CLOUDFLARE_TURNSTILE_SECRET');
        if (!$secret) {
            return false;
        }

        $token = $_POST['cf-turnstile-response'] ?? '';
        if ($token === '') {
            return false;
        }

        $response = @file_get_contents('https://challenges.cloudflare.com/turnstile/v0/siteverify', false, stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query([
                    'secret' => $secret,
                    'response' => $token,
                    'remoteip' => $_SERVER['REMOTE_ADDR'] ?? null
                ]),
                'timeout' => 5
            ]
        ]));

        if ($response === false) {
            return false;
        }

        $result = json_decode($response, true);
        return (bool)($result['success'] ?? false);
    }
}
