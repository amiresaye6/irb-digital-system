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
    public function getconn() {
        return $this->conn;
    }

    public function selectAll($table) {
        $sql = "SELECT * FROM $table";
        return $this->conn->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    public function selectById($table, $id) {
        $id  = (int)$id;
        $sql = "SELECT * FROM $table WHERE id = $id";
        return $this->conn->query($sql)->fetch_assoc();
    }
    public function insert($table, $data = []) {
        $keys = implode(',', array_keys($data));
        $values = array_map(function($value){
            return "'" . $this->conn->real_escape_string($value) . "'";
        }, array_values($data));

        $values = implode(',', $values); 

        $query = "INSERT INTO $table ($keys) VALUES ($values)";
        return $this->conn->query($query);
    }

    public function deleteById($table, $id) {
        $id = (int)$id;
        $sql = "DELETE FROM $table WHERE id = $id";
        return $this->conn->query($sql);
    }

    public function updateById($table, $id, $data = []) {
        $id = (int)$id;

        $parts = [];
        foreach ($data as $key => $value) {
            $value = $this->conn->real_escape_string($value); 
            $parts[] = "$key = '$value'";
        }

        $queryPart = implode(', ', $parts);

        $sql = "UPDATE $table SET $queryPart WHERE id = $id";
        return $this->conn->query($sql);
    }
    public function selectWhere($table, $column, $value) {
        $value = $this->conn->real_escape_string($value);
        $sql   = "SELECT * FROM $table WHERE $column = '$value'";
        return $this->conn->query($sql)->fetch_assoc();
    }
}
?>