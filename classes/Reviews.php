<?php
require_once __DIR__ . '/Database.php';

class Reviews {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->conn;
    }

    public function getApplicationsUnderReview() {
        $sql = "SELECT a.id, a.serial_number, a.title, a.principal_investigator, u.department, a.created_at FROM applications a JOIN users u ON a.student_id = u.id WHERE a.current_stage = 'under_review' ORDER BY a.created_at DESC";
        $result = $this->db->query($sql);
        $applications = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $applications[] = $row;
            }
        }
        return $applications;
    }

    public function getApplicationDetails($application_id) {
        $sql = "SELECT a.id, a.serial_number, a.title, a.principal_investigator, a.co_investigators, a.created_at, u.faculty, u.department FROM applications a JOIN users u ON a.student_id = u.id WHERE a.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $application_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return null;
    }

    public function getAvailableReviewers() {
        $sql = "SELECT id, full_name, faculty, department FROM users WHERE role = 'reviewer'";
        $result = $this->db->query($sql);
        $reviewers = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $reviewers[] = $row;
            }
        }
        return $reviewers;
    }

    public function getAssignedReviewers($application_id) {
        $sql = "SELECT r.reviewer_id as id, u.full_name, r.decision FROM reviews r JOIN users u ON r.reviewer_id = u.id WHERE r.application_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $application_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $reviewers = [];
        while ($row = $result->fetch_assoc()) {
            $reviewers[] = $row;
        }
        return $reviewers;
    }

    public function assignReviewer($application_id, $reviewer_id, $admin_id) {
        $check_sql = "SELECT id FROM reviews WHERE application_id = ? AND reviewer_id = ?";
        $check_stmt = $this->db->prepare($check_sql);
        $check_stmt->bind_param("ii", $application_id, $reviewer_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            return false;
        }

        $sql = "INSERT INTO reviews (application_id, reviewer_id, assigned_by, decision) VALUES (?, ?, ?, 'pending')";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("iii", $application_id, $reviewer_id, $admin_id);
        return $stmt->execute();
    }
}
?>
