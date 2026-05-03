<?php

class AdminDashboard
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getTopStats()
    {
        $stats = [];
        
        // Total researches in the system
        $res = $this->db->query("SELECT COUNT(*) as total FROM applications");
        $stats['totalResearches'] = $res->fetch_assoc()['total'];

        // Researches pending admin review
        $res = $this->db->query("SELECT COUNT(*) as total FROM applications WHERE current_stage = 'pending_admin'");
        $stats['pendingAdmin'] = $res->fetch_assoc()['total'];

        // Active researches (currently in workflow, not approved/rejected)
        $res = $this->db->query("SELECT COUNT(*) as total FROM applications WHERE current_stage NOT IN ('approved', 'rejected')");
        $stats['activeResearches'] = $res->fetch_assoc()['total'];

        // Researches pending payment (first or second)
        $res = $this->db->query("SELECT COUNT(*) as total FROM applications WHERE current_stage IN ('awaiting_initial_payment', 'awaiting_sample_payment')");
        $stats['pendingPayment'] = $res->fetch_assoc()['total'];

        // Researches under review (assigned to a reviewer, awaiting decision)
        $res = $this->db->query("SELECT COUNT(DISTINCT a.id) as total FROM applications a JOIN reviews r ON a.id = r.application_id WHERE a.current_stage = 'under_review'");
        $stats['underReview'] = $res->fetch_assoc()['total'];


        // Total registered students
        $res = $this->db->query("SELECT COUNT(*) as total FROM users WHERE role = 'student'");
        $stats['totalStudents'] = $res->fetch_assoc()['total'];

        // Total reviewers
        $res = $this->db->query("SELECT COUNT(*) as total FROM users WHERE role = 'reviewer'");
        $stats['totalReviewers'] = $res->fetch_assoc()['total'];

        // Total payments collected (sum in currency)
        $res = $this->db->query("SELECT SUM(amount) as total FROM payments WHERE status = 'completed'");
        $val = $res->fetch_assoc()['total'];
        $stats['totalRevenue'] = $val ? (float)$val : 0;

        return $stats;
    }

    public function getStatusDistribution()
    {
        $sql = "SELECT current_stage, COUNT(*) as count FROM applications GROUP BY current_stage";
        $result = $this->db->query($sql);
        
        $stageMap = [
            'pending_admin' => 'بانتظار التفعيل',
            'awaiting_initial_payment' => 'بانتظار الدفعة الأولى',
            'awaiting_sample_calc' => 'حساب العينة',
            'awaiting_sample_payment' => 'بانتظار الدفعة الثانية',
            'under_review' => 'قيد المراجعة',
            'approved_by_reviewer' => 'مقبول مبدئياً',
            'approved' => 'مقبول',
            'rejected' => 'مرفوض',
            'returned_to_student' => 'طلب تعديل'
        ];

        $labels = [];
        $data = [];

        while ($row = $result->fetch_assoc()) {
            $stage = $row['current_stage'];
            $labels[] = $stageMap[$stage] ?? $stage;
            $data[] = (int)$row['count'];
        }

        return ['labels' => $labels, 'data' => $data];
    }

    public function getMonthlySubmissions()
    {
        $sql = "SELECT MONTH(created_at) as m, COUNT(*) as count FROM applications WHERE YEAR(created_at) = YEAR(CURRENT_DATE) GROUP BY m ORDER BY m ASC";
        $result = $this->db->query($sql);

        $months = array_fill(1, 12, 0);
        while ($row = $result->fetch_assoc()) {
            $months[(int)$row['m']] = (int)$row['count'];
        }

        $arabicMonths = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
        
        return [
            'labels' => $arabicMonths,
            'data' => array_values($months)
        ];
    }

    public function getPaymentAnalytics()
    {
        $sql = "SELECT MONTH(paid_at) as m, phase, SUM(amount) as total FROM payments WHERE status='completed' AND YEAR(paid_at) = YEAR(CURRENT_DATE) GROUP BY m, phase ORDER BY m ASC";
        $result = $this->db->query($sql);

        $initialMonths = array_fill(1, 12, 0);
        $secondMonths = array_fill(1, 12, 0);

        while ($row = $result->fetch_assoc()) {
            if ($row['phase'] === 'initial') {
                $initialMonths[(int)$row['m']] = (float)$row['total'];
            } else {
                $secondMonths[(int)$row['m']] = (float)$row['total'];
            }
        }

        $resFirst = $this->db->query("SELECT SUM(amount) as total, COUNT(*) as cnt FROM payments WHERE phase='initial' AND status='completed'");
        $firstSum = $resFirst->fetch_assoc();

        $resSecond = $this->db->query("SELECT SUM(amount) as total, COUNT(*) as cnt FROM payments WHERE phase='sample' AND status='completed'");
        $secondSum = $resSecond->fetch_assoc();

        $resPending = $this->db->query("SELECT COUNT(*) as cnt FROM payments WHERE status='pending'");
        $pendingPaymentsCount = $resPending->fetch_assoc()['cnt'];

        $arabicMonths = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];

        return [
            'chart' => [
                'labels' => $arabicMonths,
                'initialData' => array_values($initialMonths),
                'secondData' => array_values($secondMonths)
            ],
            'summary' => [
                'totalFirstAmount' => $firstSum['total'] ? (float)$firstSum['total'] : 0,
                'totalFirstCount' => (int)$firstSum['cnt'],
                'totalSecondAmount' => $secondSum['total'] ? (float)$secondSum['total'] : 0,
                'totalSecondCount' => (int)$secondSum['cnt'],
                'totalRevenue' => ($firstSum['total'] ?? 0) + ($secondSum['total'] ?? 0),
                'pendingCount' => (int)$pendingPaymentsCount
            ]
        ];
    }

    public function getDepartmentDistribution()
    {
        $sql = "SELECT u.faculty, u.department, COUNT(a.id) as cnt FROM applications a JOIN users u ON a.student_id = u.id GROUP BY u.faculty, u.department ORDER BY cnt DESC";
        $result = $this->db->query($sql);

        $labels = [];
        $data = [];

        while ($row = $result->fetch_assoc()) {
            $faculty = $row['faculty'] ?: 'غير محدد';
            $dept = $row['department'] ?: 'غير محدد';
            $labels[] = "$faculty - $dept";
            $data[] = (int)$row['cnt'];
        }

        return ['labels' => $labels, 'data' => $data];
    }

    public function getReviewerPerformance()
    {
        $sql = "
            SELECT 
                u.id, u.full_name,
                (SELECT COUNT(*) FROM reviews WHERE reviewer_id = u.id) as total_assigned,
                (SELECT COUNT(*) FROM reviews WHERE reviewer_id = u.id AND decision = 'approved') as approved,
                (SELECT COUNT(*) FROM reviews WHERE reviewer_id = u.id AND decision = 'rejected') as rejected,
                (SELECT COUNT(*) FROM reviews WHERE reviewer_id = u.id AND decision = 'needs_modification') as modifications,
                (SELECT COUNT(*) FROM reviews r JOIN applications a ON r.application_id = a.id WHERE r.reviewer_id = u.id AND r.assignment_status = 'accepted' AND r.decision = 'pending' AND a.current_stage = 'under_review') as workload,
                (SELECT AVG(DATEDIFF(reviewed_at, created_at)) FROM reviews WHERE reviewer_id = u.id AND decision != 'pending') as avg_days
            FROM users u
            WHERE u.role = 'reviewer'
            ORDER BY workload DESC
        ";
        
        $result = $this->db->query($sql);
        $reviewers = [];
        while ($row = $result->fetch_assoc()) {
            $row['avg_days'] = round((float)$row['avg_days'], 1);
            $reviewers[] = $row;
        }
        return $reviewers;
    }

    public function getRecentActivity()
    {
        $sql = "SELECT l.action, u.full_name, l.created_at FROM logs l LEFT JOIN users u ON l.user_id = u.id ORDER BY l.created_at DESC LIMIT 8";
        $result = $this->db->query($sql);
        $logs = [];
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
        return $logs;
    }

    public function getRequiresAction()
    {
        $actions = [];

        // 1. Newly submitted researches not yet activated
        $res = $this->db->query("SELECT id, serial_number, title, created_at FROM applications WHERE current_stage = 'pending_admin'");
        while ($row = $res->fetch_assoc()) {
            $actions[] = [
                'type' => 'activation',
                'title' => 'طلب بانتظار التفعيل',
                'app_id' => $row['id'],
                'serial' => $row['serial_number'],
                'date' => $row['created_at'],
                'link' => '/irb-digital-system/features/admin/pending_applications.php'
            ];
        }

        // 2. Completed second payment and need reviewer
        $res = $this->db->query("SELECT id, serial_number, title, updated_at FROM applications a WHERE current_stage = 'under_review' AND id NOT IN (SELECT application_id FROM reviews)");
        while ($row = $res->fetch_assoc()) {
            $actions[] = [
                'type' => 'assign_reviewer',
                'title' => 'يحتاج تعيين مراجعين',
                'app_id' => $row['id'],
                'serial' => $row['serial_number'],
                'date' => $row['updated_at'],
                'link' => '/irb-digital-system/features/reviewer/assign_form.php?application_id=' . $row['id']
            ];
        }

        // 3. Stale: Waiting for second payment > 3 days
        $res = $this->db->query("SELECT id, serial_number, title, updated_at, DATEDIFF(CURRENT_DATE, updated_at) as days_stale FROM applications WHERE current_stage = 'awaiting_sample_payment' AND DATEDIFF(CURRENT_DATE, updated_at) > 3");
        while ($row = $res->fetch_assoc()) {
            $actions[] = [
                'type' => 'stale_payment',
                'title' => 'دفع متأخر (' . $row['days_stale'] . ' أيام)',
                'app_id' => $row['id'],
                'serial' => $row['serial_number'],
                'date' => $row['updated_at'],
                'link' => '/irb-digital-system/features/admin/application_details.php?id=' . $row['id']
            ];
        }

        // Sort by date descending
        usort($actions, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        return $actions;
    }

    public function getCertificateStats()
    {
        $res = $this->db->query("SELECT COUNT(*) as total FROM certificates");
        $totalCertificates = $res->fetch_assoc()['total'];

        $sql = "SELECT MONTH(issued_at) as m, COUNT(*) as count FROM certificates WHERE YEAR(issued_at) = YEAR(CURRENT_DATE) GROUP BY m ORDER BY m ASC";
        $result = $this->db->query($sql);
        $months = array_fill(1, 12, 0);
        while ($row = $result->fetch_assoc()) {
            $months[(int)$row['m']] = (int)$row['count'];
        }

        $resRec = $this->db->query("SELECT c.*, a.serial_number FROM certificates c JOIN applications a ON c.application_id = a.id ORDER BY c.issued_at DESC LIMIT 5");
        $recentCerts = [];
        while ($row = $resRec->fetch_assoc()) {
            $recentCerts[] = $row;
        }

        return [
            'total' => $totalCertificates,
            'monthlyData' => array_values($months),
            'recent' => $recentCerts
        ];
    }
}
