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
        $sql = "SELECT a.id, a.serial_number, a.title, a.principal_investigator, a.co_investigators, a.is_blinded, a.created_at, u.faculty, u.department FROM applications a JOIN users u ON a.student_id = u.id WHERE a.id = ?";
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

    public function getReviewerAssignments($reviewer_id) {
        $sql = "
            SELECT 
                a.id as application_id, 
                a.serial_number, 
                a.title, 
                a.principal_investigator, 
                a.is_blinded, 
                a.created_at, 
                u.department, 
                r.decision, 
                r.reviewed_at,
                a.current_stage
            FROM reviews r 
            JOIN applications a ON r.application_id = a.id 
            JOIN users u ON a.student_id = u.id 
            WHERE r.reviewer_id = ?
            ORDER BY a.created_at DESC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $reviewer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $assignments = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                if ($row['is_blinded'] == 1) {
                    $row['principal_investigator'] = "معلومات محجوبة";
                }
                $assignments[] = $row;
            }
        }
        return $assignments;
    }

    public function getApplicationDocuments($application_id) {
        $sql = "SELECT * FROM documents WHERE application_id = ? ORDER BY id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $application_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $documents = [];
        while ($row = $result->fetch_assoc()) {
            $documents[] = $row;
        }
        return $documents;
    }

    public function getReview($application_id, $reviewer_id) {
        $sql = "SELECT r.*, a.current_stage FROM reviews r JOIN applications a ON r.application_id = a.id WHERE r.application_id = ? AND r.reviewer_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ii", $application_id, $reviewer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return null;
    }

    public function getAllReviewsForApplication($application_id) {
        $sql = "SELECT r.id as review_id, r.decision, r.reviewed_at, u.full_name 
                FROM reviews r 
                JOIN users u ON r.reviewer_id = u.id 
                WHERE r.application_id = ? 
                ORDER BY r.reviewed_at ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $application_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $reviews = [];
        while ($row = $result->fetch_assoc()) {
            $row['comments'] = $this->getReviewComments($row['review_id']);
            $reviews[] = $row;
        }
        return $reviews;
    }

    public function getReviewComments($review_id) {
        $sql = "SELECT id, comment, created_at FROM review_comments WHERE review_id = ? ORDER BY created_at ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $review_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $comments = [];
        while ($row = $result->fetch_assoc()) {
            $comments[] = $row;
        }
        return $comments;
    }

    public function submitReviewDecision($application_id, $reviewer_id, $decision, $comment) {
        $check_sql = "SELECT current_stage FROM applications WHERE id = ?";
        $check_stmt = $this->db->prepare($check_sql);
        $check_stmt->bind_param("i", $application_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $app = $check_result->fetch_assoc();
        
        if (!$app || $app['current_stage'] === 'approved') {
            return ['success' => false, 'message' => 'لا يمكن تعديل القرار بعد الاعتماد النهائي'];
        }

        $valid_decisions = ['approved', 'needs_modification', 'rejected'];
        if (!in_array($decision, $valid_decisions)) {
            return ['success' => false, 'message' => 'قرار غير صالح'];
        }

        if ($decision !== 'approved' && empty(trim($comment))) {
            return ['success' => false, 'message' => 'يجب إضافة تعليقات عند الرفض أو طلب التعديل'];
        }

        // Get review id
        $rev_sql = "SELECT id FROM reviews WHERE application_id = ? AND reviewer_id = ?";
        $rev_stmt = $this->db->prepare($rev_sql);
        $rev_stmt->bind_param("ii", $application_id, $reviewer_id);
        $rev_stmt->execute();
        $rev_result = $rev_stmt->get_result();
        $review = $rev_result->fetch_assoc();
        
        if (!$review) {
            return ['success' => false, 'message' => 'لم يتم العثور على المراجعة'];
        }
        $review_id = $review['id'];

        // Update decision
        $sql = "UPDATE reviews SET decision = ?, reviewed_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("si", $decision, $review_id);
        
        if ($stmt->execute()) {
            // Insert the comment into review_comments
            if (!empty(trim($comment))) {
                $comment_sql = "INSERT INTO review_comments (review_id, comment) VALUES (?, ?)";
                $comment_stmt = $this->db->prepare($comment_sql);
                $comment_stmt->bind_param("is", $review_id, $comment);
                $comment_stmt->execute();
            }
            
           // if all reviewers approved  then push notification to manager
            $check_all = "SELECT COUNT(*) as total, SUM(CASE WHEN decision = 'approved' THEN 1 ELSE 0 END) as approved_count FROM reviews WHERE application_id = ?";
            $ca_stmt = $this->db->prepare($check_all);
            $ca_stmt->bind_param("i", $application_id);
            $ca_stmt->execute();
            $ca_res = $ca_stmt->get_result()->fetch_assoc();
            
            if ($ca_res && $ca_res['total'] > 0 && $ca_res['total'] == $ca_res['approved_count']) {
                $upd_stage = "UPDATE applications SET current_stage = 'approved_by_reviewer' WHERE id = ?";
                $upd_stmt = $this->db->prepare($upd_stage);
                $upd_stmt->bind_param("i", $application_id);
                if ($upd_stmt->execute()) {
                    
                    require_once __DIR__ . '/Applications.php';
                    $mgr_sql = "SELECT id FROM users WHERE role = 'manager'";
                    $mgr_res = $this->db->query($mgr_sql);
                    if ($mgr_res && $mgr_res->num_rows > 0) {
                        $appDetails = $this->getApplicationDetails($application_id);
                        $serial = $appDetails ? $appDetails['serial_number'] : '';
                        $message = "تمت الموافقة على البحث رقم ({$serial}) من قبل جميع المراجعين ويحتاج لاعتمادك النهائي.";
                        
                        while($mgr = $mgr_res->fetch_assoc()) {
                            Applications::createNotification($this->db, $mgr['id'], $application_id, $message);
                        }
                    }
                }
            }

            return ['success' => true, 'message' => 'تم حفظ القرار بنجاح'];
        }
        return ['success' => false, 'message' => 'حدث خطأ أثناء حفظ القرار'];
    }
}
?>
