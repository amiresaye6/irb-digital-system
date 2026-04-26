<?php
class SystemLogs
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getLogsSummary()
    {
        $summary = [
            'today' => 0,
            'this_month' => 0,
            'active_user' => ['name' => 'لا يوجد', 'count' => 0],
            'last_action' => ['action' => 'لا يوجد', 'time' => '']
        ];

        // Total logs today
        $resToday = $this->db->query("SELECT COUNT(*) as cnt FROM logs WHERE DATE(created_at) = CURRENT_DATE()");
        if ($resToday) {
            $summary['today'] = $resToday->fetch_assoc()['cnt'];
        }

        // Total logs this month
        $resMonth = $this->db->query("SELECT COUNT(*) as cnt FROM logs WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
        if ($resMonth) {
            $summary['this_month'] = $resMonth->fetch_assoc()['cnt'];
        }

        // Most active user today
        $resUser = $this->db->query("
            SELECT u.full_name, COUNT(l.id) as actions_count 
            FROM logs l 
            JOIN users u ON l.user_id = u.id 
            WHERE DATE(l.created_at) = CURRENT_DATE() 
            AND u.role != 'admin'  
            GROUP BY l.user_id 
            ORDER BY actions_count DESC 
            LIMIT 1
        ");
        if ($resUser && $resUser->num_rows > 0) {
            $row = $resUser->fetch_assoc();
            $summary['active_user'] = ['name' => $row['full_name'], 'count' => $row['actions_count']];
        }

        // Last action recorded
        $resLast = $this->db->query("SELECT action, created_at FROM logs ORDER BY created_at DESC LIMIT 1");
        if ($resLast && $resLast->num_rows > 0) {
            $row = $resLast->fetch_assoc();
            // Shorten the action string for the card
            $shortAction = mb_strlen($row['action']) > 30 ? mb_substr($row['action'], 0, 30) . '...' : $row['action'];
            $summary['last_action'] = ['action' => $shortAction, 'time' => $row['created_at']];
        }

        return $summary;
    }

    public function getAllLogs()
    {
        $sql = "
            SELECT 
                l.id, 
                l.action, 
                l.type,
                l.created_at, 
                u.full_name, 
                u.role, 
                a.serial_number, 
                a.id as application_id
            FROM logs l
            LEFT JOIN users u ON l.user_id = u.id
            LEFT JOIN applications a ON l.application_id = a.id
            ORDER BY l.created_at DESC
        ";
        
        $result = $this->db->query($sql);
        $logs = [];

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $typeInfo = $this->getTypeInfo($row['type'] ?? 'general');
                
                $row['action_type'] = $typeInfo['type'];
                $row['action_label'] = $typeInfo['label'];
                $row['badge_class'] = $typeInfo['badge_class'];
                
                $logs[] = $row;
            }
        }
        return $logs;
    }

    private function getTypeInfo($type)
    {
        $map = [
            'login'        => ['type' => 'login', 'label' => 'تسجيل دخول', 'badge_class' => 'status-badge status-login'],
            'registration' => ['type' => 'registration', 'label' => 'تسجيل جديد', 'badge_class' => 'status-badge status-registration'],
            'profile'      => ['type' => 'profile', 'label' => 'تحديث الملف', 'badge_class' => 'status-badge status-profile'],
            'payment'      => ['type' => 'payment', 'label' => 'عملية دفع', 'badge_class' => 'status-badge status-payment'],
            'submission'   => ['type' => 'submission', 'label' => 'تقديم بحث', 'badge_class' => 'status-badge status-submission'],
            'document'     => ['type' => 'document', 'label' => 'إدارة المستندات', 'badge_class' => 'status-badge status-document'],
            'assignment'   => ['type' => 'assignment', 'label' => 'إسناد مراجعين', 'badge_class' => 'status-badge status-assignment'],
            'status_change'=> ['type' => 'status_change', 'label' => 'تغيير حالة', 'badge_class' => 'status-badge status-change'],
            'decision'     => ['type' => 'decision', 'label' => 'قرار تحكيم', 'badge_class' => 'status-badge status-decision'],
            'certificate'  => ['type' => 'certificate', 'label' => 'إصدار شهادة', 'badge_class' => 'status-badge status-certificate'],
            'general'      => ['type' => 'general', 'label' => 'إجراء عام', 'badge_class' => 'status-badge status-general']
        ];

        return $map[$type] ?? $map['general'];
    }
}
