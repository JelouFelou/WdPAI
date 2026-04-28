<?php

class Template {
    private $id;
    private $name;
    private $description;
    private $id_user;
    private $fields; // Tablica obiektów TemplateField

    public function __construct($name, $description, $id_user, $id = null, $fields = []) {
        $this->name = $name;
        $this->description = $description;
        $this->id_user = $id_user;
        $this->id = $id;
        $this->fields = $fields;
    }

    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getDescription(): string { return $this->description; }
    public function getFields(): array { return $this->fields; }
}