<?php
require_once __DIR__ . '/Database.php';

class Certificates{
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->conn;
    }

    public function getCertificatesByStudentId($student_id) {
        $sql = "SELECT * FROM certificates WHERE student_id = ? ORDER BY issued_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $docs = [];
        while ($row = $result->fetch_assoc()) {
            $docs[] = $row;
        }
        return $docs;
    }
}

?>