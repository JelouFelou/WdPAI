<?php
// .env 
require_once "config.php";

// singleton 
class Database {
    private $username;
    private $password;
    private $host;
    private $database;
    // private $conn;

    public function __construct()
    {
        $this->username = USERNAME;
        $this->password = PASSWORD;
        $this->host = HOST;
        $this->database = DATABASE;
    }

    public function connect()
    {
        try {
            $conn = new PDO(
                "pgsql:host=$this->host;port=5432;dbname=$this->database",
                $this->username,
                $this->password,
                ["sslmode"  => "prefer"]
            );

            // set the PDO error mode to exception
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $conn;
        }
        catch(PDOException $e) {
            // change to error page e.g. 404 not found etc.
            die("Connection failed: " . $e->getMessage());
        }
    }

    public function disconnect() {
        // $this->conn = null;
    }
}