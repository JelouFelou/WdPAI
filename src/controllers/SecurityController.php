<?php
require_once 'AppController.php';
class SecurityController extends AppController{
    public function login()
    {
        if (!$this->isPost()) {
            return $this->render('login');
        }

        $email = $_POST["email"] ?? '';
        $password = $_POST["password"] ?? '';

        $userRepository = new UsersRepository();
        $user = $userRepository->getUserByEmail($email);
  
        if (!$user) {
            return $this->render('login', ['messages' => ['User not found']]);
        }

        if (!password_verify($password, $user->getPassword())) {
            return $this->render('login', ['messages' => ['Wrong password']]);
        }       

        session_regenerate_id(true);
        $_SESSION['user_id'] = $user->getId();
        $_SESSION['email'] = $user->getEmail();
        $_SESSION['first_name'] = $user->getFirstName(); 
        $_SESSION['last_name'] = $user->getLastName();

        $url = "http://$_SERVER[HTTP_HOST]";
        header("Location: {$url}/dashboard");
    }

    public function register()
    {
        if (!$this->isPost()) {
            return $this->render('register');
        }

        $email = $_POST['email'];
        $password = $_POST['password'];
        $password2 = $_POST['password2'];
        $firstName = $_POST['firstName'];
        $lastName = $_POST['lastName'];

        if ($password !== $password2) {
            return $this->render('register', ['messages' => ['Passwords do not match!']]);
        }

        if (empty($email) || empty($password) || empty($firstName)) {
            return $this->render('register', ['messages' => ['Fill all fields']]);
        }

        $userRepository = new UsersRepository();
        if ($userRepository->getUserByEmail($email)) {
            return $this->render('register', ['messages' => ['User already exists!']]);
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        $userRepository->createUser($email, $hashedPassword, $firstName, $lastName);

        return $this->render('login', ['messages' => ['Registration successful! Please log in.']]);
    }

    public function logout(){
        return $this->render('logout');
    }
}