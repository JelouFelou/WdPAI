<?php

class Character {
    private $id;
    private $name;
    private $description;
    private $image;
    private $id_user;

    public function __construct($name, $description, $image, $id_user, $id = null) {
        $this->name = $name;
        $this->description = $description;
        $this->image = $image;
        $this->id_user = $id_user;
        $this->id = $id;
    }

    public function getName(): string { return $this->name; }
    public function getDescription(): string { return $this->description; }
    public function getImage(): string { return $this->image; }
    public function getId(): ?int { return $this->id; }
}