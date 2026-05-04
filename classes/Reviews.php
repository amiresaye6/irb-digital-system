<?php
require_once __DIR__ . '/Database.php';

class Reviews {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->conn;
    }

    public function getApplicationsUnderReview() {
        $sql = "SELECT a.id, a.serial_number, a.title, a.principal_investigator, u.department, a.created_at 
                FROM applications a 
                JOIN users u ON a.student_id = u.id 
                WHERE a.current_stage = 'under_review' 
                ORDER BY a.created_at ASC"; 
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
        $sql = "SELECT a.id, a.serial_number, a.title, a.principal_investigator, a.co_investigators, a.is_blinded, a.created_at, u.faculty, u.department 
                FROM applications a 
                JOIN users u ON a.student_id = u.id 
                WHERE a.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $application_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return ($result && $result->num_rows > 0) ? $result->fetch_assoc() : null;
    }

    public function getAvailableReviewers() {
        $sql = "SELECT id, full_name, faculty, department FROM users WHERE role = 'reviewer' AND is_active = 1";
        $result = $this->db->query($sql);
        $reviewers = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $reviewers[] = $row;
            }
        }
        return $reviewers;
    }

    public function getActiveAssignment($application_id) {
        $sql = "SELECT r.*, u.full_name 
                FROM reviews r 
                JOIN users u ON r.reviewer_id = u.id 
                WHERE r.application_id = ? 
                AND r.assignment_status IN ('awaiting_acceptance', 'accepted')
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $application_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return ($result && $result->num_rows > 0) ? $result->fetch_assoc() : null;
    }

    public function getAssignmentHistory($application_id) {
        $sql = "SELECT r.*, u.full_name 
                FROM reviews r 
                JOIN users u ON r.reviewer_id = u.id 
                WHERE r.application_id = ? 
                ORDER BY r.assigned_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $application_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $history = [];
        while ($row = $result->fetch_assoc()) {
            $history[] = $row;
        }
        return $history;
    }

    public function getAssignedReviewers($application_id) {
        $sql = "SELECT r.reviewer_id as id, u.full_name, r.decision, r.assignment_status 
                FROM reviews r 
                JOIN users u ON r.reviewer_id = u.id 
                WHERE r.application_id = ?";
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
        if ($this->getActiveAssignment($application_id)) {
            return false;
        }
        $sql = "INSERT INTO reviews (application_id, reviewer_id, assigned_by, assignment_status, decision) 
                VALUES (?, ?, ?, 'awaiting_acceptance', 'pending')";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("iii", $application_id, $reviewer_id, $admin_id);
        return $stmt->execute();
    }

    /**
     * Reviewer's ACCEPTED assignments — their active work queue.
     */
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
                r.assignment_status,
                r.reviewed_at,
                a.current_stage
            FROM reviews r 
            JOIN applications a ON r.application_id = a.id 
            JOIN users u ON a.student_id = u.id 
            WHERE r.reviewer_id = ? AND r.assignment_status = 'accepted'
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

    /**
     * Assignments awaiting reviewer acceptance.
     */
    public function getPendingAssignments($reviewer_id) {
        $sql = "
            SELECT 
                r.id as review_id,
                a.id as application_id, 
                a.serial_number, 
                a.title, 
                a.principal_investigator, 
                a.is_blinded, 
                a.created_at as application_date,
                r.assigned_at,
                u.department,
                u2.full_name as assigned_by_name
            FROM reviews r 
            JOIN applications a ON r.application_id = a.id 
            JOIN users u ON a.student_id = u.id
            LEFT JOIN users u2 ON r.assigned_by = u2.id
            WHERE r.reviewer_id = ? AND r.assignment_status = 'awaiting_acceptance'
            ORDER BY r.assigned_at DESC
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

    public function acceptAssignment($review_id, $reviewer_id) {
        $sql = "UPDATE reviews SET assignment_status = 'accepted' WHERE id = ? AND reviewer_id = ? AND assignment_status = 'awaiting_acceptance'";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ii", $review_id, $reviewer_id);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }

    public function refuseAssignment($review_id, $reviewer_id, $reason) {
        $sql = "UPDATE reviews SET assignment_status = 'refused', refusal_reason = ? WHERE id = ? AND reviewer_id = ? AND assignment_status = 'awaiting_acceptance'";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("sii", $reason, $review_id, $reviewer_id);
        $stmt->execute();
        return $stmt->affected_rows > 0;
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
        $sql = "SELECT r.id as review_id, r.decision, r.assignment_status, r.reviewed_at, u.full_name 
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
        // Guard: cannot modify after final approval
        $check_stmt = $this->db->prepare("SELECT current_stage FROM applications WHERE id = ?");
        $check_stmt->bind_param("i", $application_id);
        $check_stmt->execute();
        $app = $check_stmt->get_result()->fetch_assoc();

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

        // Must be an accepted assignment
        $rev_stmt = $this->db->prepare(
            "SELECT id FROM reviews WHERE application_id = ? AND reviewer_id = ? AND assignment_status = 'accepted'"
        );
        $rev_stmt->bind_param("ii", $application_id, $reviewer_id);
        $rev_stmt->execute();
        $review = $rev_stmt->get_result()->fetch_assoc();

        if (!$review) {
            return ['success' => false, 'message' => 'لم يتم العثور على المراجعة أو لم يتم قبول الإسناد بعد'];
        }
        $review_id = $review['id'];

        // Save the decision
        $upd_stmt = $this->db->prepare("UPDATE reviews SET decision = ?, reviewed_at = CURRENT_TIMESTAMP WHERE id = ?");
        $upd_stmt->bind_param("si", $decision, $review_id);

        if ($upd_stmt->execute()) {
            // Save comment
            if (!empty(trim($comment))) {
                $c_stmt = $this->db->prepare("INSERT INTO review_comments (review_id, comment) VALUES (?, ?)");
                $c_stmt->bind_param("is", $review_id, $comment);
                $c_stmt->execute();
            }

            require_once __DIR__ . '/Applications.php';

            // ── REJECTED: immediately lock the application ──────────────────
            if ($decision === 'rejected') {
                $r_stmt = $this->db->prepare(
                    "UPDATE applications SET current_stage = 'rejected'
                     WHERE id = ? AND current_stage NOT IN ('approved','rejected')"
                );
                $r_stmt->bind_param("i", $application_id);
                $r_stmt->execute();

                // Notify student
                $appDetails = $this->getApplicationDetails($application_id);
                $s_stmt = $this->db->prepare("SELECT student_id FROM applications WHERE id = ?");
                $s_stmt->bind_param("i", $application_id);
                $s_stmt->execute();
                $s_row = $s_stmt->get_result()->fetch_assoc();
                if ($s_row && $appDetails) {
                    Applications::createNotification(
                        $this->db,
                        $s_row['student_id'],
                        $application_id,
                        "تم رفض بحثك رقم ({$appDetails['serial_number']}) من قبل المراجع. يرجى مراجعة ملاحظات المراجعة."
                    );
                }
                return ['success' => true, 'message' => 'تم حفظ قرار الرفض بنجاح'];
            }

            // ── ALL APPROVED: promote to manager ──────────────────────────
            $ca_stmt = $this->db->prepare(
                "SELECT COUNT(*) as total,
                 SUM(CASE WHEN decision = 'approved' THEN 1 ELSE 0 END) as approved_count
                 FROM reviews WHERE application_id = ? AND assignment_status = 'accepted'"
            );
            $ca_stmt->bind_param("i", $application_id);
            $ca_stmt->execute();
            $ca_res = $ca_stmt->get_result()->fetch_assoc();

            if ($ca_res && $ca_res['total'] > 0 && $ca_res['total'] == $ca_res['approved_count']) {
                $stage_stmt = $this->db->prepare(
                    "UPDATE applications SET current_stage = 'approved_by_reviewer' WHERE id = ?"
                );
                $stage_stmt->bind_param("i", $application_id);
                if ($stage_stmt->execute()) {
                    $mgr_res = $this->db->query("SELECT id FROM users WHERE role = 'manager'");
                    if ($mgr_res && $mgr_res->num_rows > 0) {
                        $appDetails = $this->getApplicationDetails($application_id);
                        $serial  = $appDetails ? $appDetails['serial_number'] : '';
                        $message = "تمت الموافقة على البحث رقم ({$serial}) من قبل جميع المراجعين ويحتاج لاعتمادك النهائي.";
                        while ($mgr = $mgr_res->fetch_assoc()) {
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