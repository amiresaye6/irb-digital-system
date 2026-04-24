<?php

class ManagerDashboard
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    private function buildWhereClause($year, $faculty)
    {
        $whereClause = "WHERE 1=1";
        $params = [];
        $types = "";

        if ($year) {
            $whereClause .= " AND YEAR(a.created_at) = ?";
            $params[] = (int)$year;
            $types .= "i";
        }

        if ($faculty) {
            $whereClause .= " AND u.faculty = ?";
            $params[] = $faculty;
            $types .= "s";
        }

        return ['clause' => $whereClause, 'params' => $params, 'types' => $types];
    }

    private function bindDynamicParams($stmt, $types, $params)
    {
        if (!empty($types) && !empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
    }

    public function getKPIs($year = null, $faculty = null)
    {
        $whereData = $this->buildWhereClause($year, $faculty);
        $whereClause = $whereData['clause'];
        $params = $whereData['params'];
        $types = $whereData['types'];

     
        $sqlTotal = "SELECT COUNT(a.id) as total FROM applications a JOIN users u ON a.student_id = u.id $whereClause";
        $stmtTotal = $this->db->prepare($sqlTotal);
        $this->bindDynamicParams($stmtTotal, $types, $params);
        $stmtTotal->execute();
        $totalResearches = $stmtTotal->get_result()->fetch_assoc()['total'];

        $sqlAvg = "SELECT AVG(DATEDIFF(a.updated_at, a.created_at)) as avg_days FROM applications a JOIN users u ON a.student_id = u.id $whereClause AND a.current_stage IN ('approved', 'approved_by_reviewer')";
        $stmtAvg = $this->db->prepare($sqlAvg);
        $this->bindDynamicParams($stmtAvg, $types, $params);
        $stmtAvg->execute();
        $avgApproval = round((float)$stmtAvg->get_result()->fetch_assoc()['avg_days'], 1);

        $sqlPending = "SELECT COUNT(a.id) as pending FROM applications a JOIN users u ON a.student_id = u.id $whereClause AND a.current_stage NOT IN ('approved', 'rejected', 'returned_to_student')";
        $stmtPending = $this->db->prepare($sqlPending);
        $this->bindDynamicParams($stmtPending, $types, $params);
        $stmtPending->execute();
        $pendingResearches = $stmtPending->get_result()->fetch_assoc()['pending'];

        $sqlApproved = "SELECT COUNT(a.id) as approved FROM applications a JOIN users u ON a.student_id = u.id $whereClause AND a.current_stage = 'approved'";
        $stmtApproved = $this->db->prepare($sqlApproved);
        $this->bindDynamicParams($stmtApproved, $types, $params);
        $stmtApproved->execute();
        $approvedResearches = $stmtApproved->get_result()->fetch_assoc()['approved'];
        $approvalRate = $totalResearches > 0 ? round(($approvedResearches / $totalResearches) * 100, 1) : 0;

      
        $sqlRejected = "SELECT COUNT(a.id) as rejected FROM applications a JOIN users u ON a.student_id = u.id $whereClause AND a.current_stage = 'rejected'";
        $stmtRejected = $this->db->prepare($sqlRejected);
        $this->bindDynamicParams($stmtRejected, $types, $params);
        $stmtRejected->execute();
        $rejectedResearches = $stmtRejected->get_result()->fetch_assoc()['rejected'];

        $sqlReturned = "SELECT COUNT(a.id) as returned FROM applications a JOIN users u ON a.student_id = u.id $whereClause AND a.current_stage = 'returned_to_student'";
        $stmtReturned = $this->db->prepare($sqlReturned);
        $this->bindDynamicParams($stmtReturned, $types, $params);
        $stmtReturned->execute();
        $returnedResearches = $stmtReturned->get_result()->fetch_assoc()['returned'];

        $sqlReviewers = "SELECT COUNT(id) as total_revs FROM users WHERE role = 'reviewer'";
        $stmtReviewers = $this->db->query($sqlReviewers);
        $totalReviewers = $stmtReviewers->fetch_assoc()['total_revs'];

        return [
            'totalResearches' => $totalResearches,
            'avgApproval' => $avgApproval,
            'pendingResearches' => $pendingResearches,
            'approvalRate' => $approvalRate,
            'approvedResearches' => $approvedResearches,
            'rejectedResearches' => $rejectedResearches,
            'returnedResearches' => $returnedResearches,
            'totalReviewers' => $totalReviewers
        ];
    }

    public function getDepartmentDistribution($year, $faculty)
    {
        $whereData = $this->buildWhereClause($year, $faculty);
        $whereClause = $whereData['clause'];
        $params = $whereData['params'];
        $types = $whereData['types'];

        $sqlDept = "SELECT u.department, COUNT(a.id) as count 
                    FROM applications a 
                    JOIN users u ON a.student_id = u.id 
                    $whereClause AND u.department IS NOT NULL
                    GROUP BY u.department 
                    ORDER BY count DESC";
        $stmtDept = $this->db->prepare($sqlDept);
        $this->bindDynamicParams($stmtDept, $types, $params);
        $stmtDept->execute();
        $deptResult = $stmtDept->get_result();
        
        $deptLabels = [];
        $deptData = [];
        while ($row = $deptResult->fetch_assoc()) {
            $deptLabels[] = $row['department'];
            $deptData[] = $row['count'];
        }

        return ['labels' => $deptLabels, 'data' => $deptData];
    }

    public function getReviewerWorkload()
    {
        $sqlWorkload = "SELECT u.full_name, COUNT(r.id) as assigned_count 
                        FROM users u 
                        LEFT JOIN reviews r ON u.id = r.reviewer_id 
                        WHERE u.role = 'reviewer' 
                        GROUP BY u.id 
                        ORDER BY assigned_count DESC";
        $workloadResult = $this->db->query($sqlWorkload);
        
        $reviewerLabels = [];
        $reviewerData = [];
        while ($row = $workloadResult->fetch_assoc()) {
            $reviewerLabels[] = str_replace('أ.د. ', '', $row['full_name']); // Clean up prefix for chart
            $reviewerData[] = $row['assigned_count'];
        }

        return ['labels' => $reviewerLabels, 'data' => $reviewerData];
    }

    public function getFilterOptions()
    {
        $yearsResult = $this->db->query("SELECT DISTINCT YEAR(created_at) as year FROM applications ORDER BY year DESC");
        $years = [];
        while ($row = $yearsResult->fetch_assoc()) { $years[] = $row['year']; }

        $facultiesResult = $this->db->query("SELECT DISTINCT faculty FROM users WHERE faculty IS NOT NULL");
        $faculties = [];
        while ($row = $facultiesResult->fetch_assoc()) { $faculties[] = $row['faculty']; }

        return ['years' => $years, 'faculties' => $faculties];
    }
}
?>
