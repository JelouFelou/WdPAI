<?php

require_once 'Repository.php';
require_once __DIR__.'/../models/User.php';

class UsersRepository extends Repository {

    public function getUsers(): ?array 
    {
        $query = $this->database->connect()->prepare(
            "
            SELECT * FROM users;
            "
        );
        $query->execute();

        $users = $query->fetchAll(PDO::FETCH_ASSOC);
        return $users;
    }

  public function getUserByEmail(string $email) {
        $query = $this->database->connect()->prepare(
            "
            SELECT * FROM users WHERE email = :email
            "
        );
        $query->bindParam(':email', $email);
        $query->execute();

        $user = $query->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            return null;
        }

        return new User(
        $user['email'],
        $user['password'],
        $user['firstname'],
        $user['lastname'],
        $user['bio'] ?? '',
        $user['id']
        );
    }

    public function createUser(
        string $email,
        string $hashedPassword,
        string $firstname,
        string $lastname,
        string $bio = ''
    ) {
        $query = $this->database->connect()->prepare(
            "
            INSERT INTO users (firstname, lastname, email, password, bio)
            VALUES (?, ?, ?, ?, ?);
            "
        );
        $query->execute([
            $firstname,
            $lastname,
            $email, 
            $hashedPassword,
            $bio
        ]);
    }
}