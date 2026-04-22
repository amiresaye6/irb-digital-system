<?php
class Database
{
    private $host;
    private $user;
    private $pass;
    private $db;
    private $port;
    public $conn;

    public function __construct()
    {
        // Load the secret variables
        $env = require __DIR__ . '/../includes/env.php';

        $this->host = $env['DB_HOST'];
        $this->user = $env['DB_USER'];
        $this->pass = $env['DB_PASS'];
        $this->db = $env['DB_NAME'];
        $this->port = $env['DB_PORT'];

        // Connect using the secure variables
        $this->conn = new mysqli($this->host, $this->user, $this->pass, $this->db, $this->port);

        if ($this->conn->connect_error) {
            die("Database Connection Failed: " . $this->conn->connect_error);
        }

        $this->conn->set_charset("utf8mb4");
    }
}
?>