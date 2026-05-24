<?php

class User {
    private $id;
    private $email;
    private $password;
    private $firstName;
    private $lastName;
    private $username;
    private $bio;
    private $accountType;
    private $bannedUntil;
    private $banReason;
    private $deletionScheduledAt;

    public function __construct(
        string $email, 
        string $password, 
        string $firstName, 
        string $lastName, 
        string $bio = '', 
        int $id = null,
        string $username = '',
        int $accountType = 0,
        ?string $bannedUntil = null,
        ?string $banReason = null,
        ?string $deletionScheduledAt = null
    ) {
        $this->email = $email;
        $this->password = $password;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->bio = $bio;
        $this->id = $id;
        $this->username = $username;
        $this->accountType = $accountType;
        $this->bannedUntil = $bannedUntil;
        $this->banReason = $banReason;
        $this->deletionScheduledAt = $deletionScheduledAt;
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
    
    public function getUsername(): string {
        return $this->username;
    }

    public function getAccountType(): int {
        return $this->accountType;
    }

    public function isAdmin(): bool {
        return $this->accountType === 1;
    }

    public function getBannedUntil(): ?string {
        return $this->bannedUntil;
    }

    public function getBanReason(): ?string {
        return $this->banReason;
    }

    public function getDeletionScheduledAt(): ?string {
        return $this->deletionScheduledAt;
    }
    
    // Możesz dodać przydatne metody logiczne
    public function getFullName(): string {
        return $this->firstName . ' ' . $this->lastName;
    }
}
