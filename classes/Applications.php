<?php
require_once __DIR__ . '/Database.php';

class Applications {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->conn;
    }

    public function getApplicationsByStatus($status) {
        $sql = "SELECT a.id,a.student_id, a.serial_number, a.title, a.principal_investigator, a.current_stage, a.created_at, a.updated_at
                FROM applications a 
                WHERE a.current_stage = ? 
                ORDER BY a.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $status);
        $stmt->execute();
        $result = $stmt->get_result();
        $apps = [];
        while ($row = $result->fetch_assoc()) {
            $apps[] = $row;
        }
        return $apps;
    }

 
    public function getStudentApplications($student_id) {
        $sql = "SELECT a.id, a.serial_number, a.title, a.principal_investigator, a.current_stage, a.created_at, a.updated_at
                FROM applications a 
                WHERE a.student_id = ? 
                ORDER BY a.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $apps = [];
        while ($row = $result->fetch_assoc()) {
            $apps[] = $row;
        }
        return $apps;
    }

    public function getApplicationFullDetails($application_id, $student_id) {
        $sql = "SELECT a.*, u.faculty, u.department, u.full_name as student_name
                FROM applications a 
                JOIN users u ON a.student_id = u.id 
                WHERE a.id = ? AND a.student_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ii", $application_id, $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return null;
    }

    public function getApplicationDocuments($application_id) {
        $sql = "SELECT * FROM documents WHERE application_id = ? ORDER BY uploaded_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $application_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $docs = [];
        while ($row = $result->fetch_assoc()) {
            $docs[] = $row;
        }
        return $docs;
    }

    public function getCertificates($user_id) {
        $sql = "SELECT * FROM certificates WHERE user_id = ? ORDER BY uploaded_at DESC";//application_id , manager_id , certificate_number , issued_to_name , pdf_url
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $docs = [];
        while ($row = $result->fetch_assoc()) {
            $docs[] = $row;
        }
        return $docs;
    }

    public function getReviewerFeedback($application_id) {
        $sql = "SELECT r.id as review_id, r.decision, r.reviewed_at, rc.comment, rc.created_at as comment_date
                FROM reviews r 
                LEFT JOIN review_comments rc ON r.id = rc.review_id
                WHERE r.application_id = ? AND r.decision != 'pending'
                ORDER BY rc.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $application_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $feedback = [];
        $reviewerIndex = [];
        $counter = 1;
        while ($row = $result->fetch_assoc()) {
            if (!isset($reviewerIndex[$row['review_id']])) {
                $reviewerIndex[$row['review_id']] = $counter++;
            }
            $row['reviewer_label'] = 'مراجع ' . $reviewerIndex[$row['review_id']];
            $feedback[] = $row;
        }
        return $feedback;
    }

    public function hasNeedsModification($application_id) {
        $sql = "SELECT COUNT(*) as cnt FROM reviews WHERE application_id = ? AND decision = 'needs_modification'";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $application_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['cnt'] > 0;
    }

    public function getStudentNotifications($student_id) {
        $sql = "SELECT n.*, a.serial_number, a.title as app_title
                FROM notifications n 
                LEFT JOIN applications a ON n.application_id = a.id
                WHERE n.user_id = ? 
                ORDER BY n.is_read ASC, n.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $notifs = [];
        while ($row = $result->fetch_assoc()) {
            $notifs[] = $row;
        }
        return $notifs;
    }

    public function getNotificationById($id, $student_id) {
        $sql = "SELECT n.*, a.serial_number, a.title as app_title, a.current_stage, a.id as app_id
                FROM notifications n 
                LEFT JOIN applications a ON n.application_id = a.id
                WHERE n.id = ? AND n.user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ii", $id, $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return null;
    }

    public function markNotificationRead($id, $student_id) {
        $sql = "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ii", $id, $student_id);
        return $stmt->execute();
    }

    public function markAllNotificationsRead($student_id) {
        $sql = "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $student_id);
        return $stmt->execute();
    }

    public function getUnreadNotificationCount($student_id) {
        $sql = "SELECT COUNT(*) as cnt FROM notifications WHERE user_id = ? AND is_read = 0";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return (int)$row['cnt'];
    }

    public function getSampleSize($application_id) {
        $sql = "SELECT * FROM sample_sizes WHERE application_id = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $application_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return null;
    }

    public function updateApplicationDetails($application_id, $student_id, $title, $pi, $co_investigators) {
        $coJson = json_encode($co_investigators, JSON_UNESCAPED_UNICODE);
        $sql = "UPDATE applications SET title = ?, principal_investigator = ?, co_investigators = ? WHERE id = ? AND student_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("sssii", $title, $pi, $coJson, $application_id, $student_id);
        return $stmt->execute();
    }

    public static function createNotification($db, $user_id, $application_id, $message) {
        $sql = "INSERT INTO notifications (user_id, application_id, message, channel) VALUES (?, ?, ?, 'system')";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("iis", $user_id, $application_id, $message);
        $inserted = $stmt->execute();
        
        if ($inserted) {
            require_once __DIR__ . '/EmailService.php';
            EmailService::triggerCron();
        }
        
        return $inserted;
    }

    public static function createLog($db, $application_id, $user_id, $action) {
        $sql = "INSERT INTO logs (application_id, user_id, action) VALUES (?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("iis", $application_id, $user_id, $action);
        return $stmt->execute();
    }
}
?>
