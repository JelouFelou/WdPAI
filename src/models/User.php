<?php

class User {
    private $id;
    private $email;
    private $password;
    private $firstName;
    private $lastName;
    private $bio;

    public function __construct(
        string $email, 
        string $password, 
        string $firstName, 
        string $lastName, 
        string $bio = '', 
        int $id = null
    ) {
        $this->email = $email;
        $this->password = $password;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->bio = $bio;
        $this->id = $id;
    }

    public function getEmail(): string {
        return $this->email;
    }

    public function getPassword(): string {
        return $this->password;
    }

    public function getFirstName(): string {
        return $this->firstName;
    }

    public function getLastName(): string {
        return $this->lastName;
    }

    public function getId(): ?int {
        return $this->id;
    }
    
    // Możesz dodać przydatne metody logiczne
    public function getFullName(): string {
        return $this->firstName . ' ' . $this->lastName;
    }
}