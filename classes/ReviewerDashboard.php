<?php

class ReviewerDashboard
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getKPIs($reviewer_id)
    {
        $kpis = [
            'totalAssigned' => 0,
            'pendingAction' => 0,
            'completed' => 0,
            'needsModification' => 0
        ];

        // Total Assigned
        $sqlTotal = "SELECT COUNT(id) as total FROM reviews WHERE reviewer_id = ?";
        $stmtTotal = $this->db->prepare($sqlTotal);
        $stmtTotal->bind_param("i", $reviewer_id);
        $stmtTotal->execute();
        $kpis['totalAssigned'] = $stmtTotal->get_result()->fetch_assoc()['total'];

        // Pending Action (Decision is pending)
        $sqlPending = "SELECT COUNT(id) as pending FROM reviews WHERE reviewer_id = ? AND decision = 'pending'";
        $stmtPending = $this->db->prepare($sqlPending);
        $stmtPending->bind_param("i", $reviewer_id);
        $stmtPending->execute();
        $kpis['pendingAction'] = $stmtPending->get_result()->fetch_assoc()['pending'];

        // Needs Modification
        $sqlNeedsMod = "SELECT COUNT(id) as needs_mod FROM reviews WHERE reviewer_id = ? AND decision = 'needs_modification'";
        $stmtNeedsMod = $this->db->prepare($sqlNeedsMod);
        $stmtNeedsMod->bind_param("i", $reviewer_id);
        $stmtNeedsMod->execute();
        $kpis['needsModification'] = $stmtNeedsMod->get_result()->fetch_assoc()['needs_mod'];

        // Completed (Approved or Rejected)
        $sqlCompleted = "SELECT COUNT(id) as completed FROM reviews WHERE reviewer_id = ? AND decision IN ('approved', 'rejected')";
        $stmtCompleted = $this->db->prepare($sqlCompleted);
        $stmtCompleted->bind_param("i", $reviewer_id);
        $stmtCompleted->execute();
        $kpis['completed'] = $stmtCompleted->get_result()->fetch_assoc()['completed'];

        return $kpis;
    }

    public function getDecisionsDistribution($reviewer_id)
    {
        $sql = "SELECT decision, COUNT(id) as count FROM reviews WHERE reviewer_id = ? GROUP BY decision";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $reviewer_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $labels = [];
        $data = [];
        $colors = [];
        
        $decisionMap = [
            'pending' => ['label' => 'قيد الانتظار', 'color' => '#3498db'],
            'approved' => ['label' => 'موافقة', 'color' => '#27ae60'],
            'needs_modification' => ['label' => 'طلب تعديل', 'color' => '#f39c12'],
            'rejected' => ['label' => 'رفض', 'color' => '#e74c3c']
        ];

        while ($row = $result->fetch_assoc()) {
            $dec = $row['decision'];
            if (isset($decisionMap[$dec])) {
                $labels[] = $decisionMap[$dec]['label'];
                $colors[] = $decisionMap[$dec]['color'];
                $data[] = $row['count'];
            }
        }

        return ['labels' => $labels, 'data' => $data, 'colors' => $colors];
    }

    public function getMonthlyReviews($reviewer_id)
    {
        $sql = "
            SELECT 
                DATE_FORMAT(reviewed_at, '%Y-%m') as month, 
                COUNT(id) as count 
            FROM reviews 
            WHERE reviewer_id = ? AND reviewed_at IS NOT NULL 
            GROUP BY month 
            ORDER BY month ASC 
            LIMIT 6
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $reviewer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $labels = [];
        $data = [];
        
        // Convert 'YYYY-MM' to Arabic month name (optional, but YYYY-MM is fine)
        while ($row = $result->fetch_assoc()) {
            $labels[] = $row['month'];
            $data[] = $row['count'];
        }
        
        return ['labels' => $labels, 'data' => $data];
    }

    public function getPendingResearches($reviewer_id)
    {
        // Get applications where decision is pending or needs modification (since user requested both)
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
            WHERE r.reviewer_id = ? AND r.decision IN ('pending', 'needs_modification')
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
}
