<?php

class Character {
    private $id;
    private $name;
    private $description;
    private $image;
    private $id_user;
    private $id_template; // Dodajemy pole, jeśli przechowujesz je w bazie

    public function __construct($name, $description, $image, $id_user, $id = null, $id_template = null) {
        $this->name = $name;
        $this->description = $description;
        $this->image = $image;
        $this->id_user = $id_user;
        $this->id = $id;
        $this->id_template = $id_template;
    }

    public function getName(): string { return $this->name; }
    public function getDescription(): string { return $this->description; }
    public function getImage(): string { return $this->image; }
    public function getId(): ?int { return $this->id; }
    public function getIdTemplate(): ?int { return $this->id_template; } // Ważne: dodaj ten getter
}