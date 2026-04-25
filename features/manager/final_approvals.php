<?php
require_once '../../classes/Database.php';
require_once '../../includes/irb_helpers.php';

$db = new Database();
$conn = $db->getconn();

$sql = "SELECT a.id as app_id, 
                MAX(r.id) as review_id,
                a.serial_number, 
                a.title, 
                a.current_stage, 
                a.updated_at, 
                u.full_name as student_name 
        FROM applications a
        LEFT JOIN reviews r ON a.id = r.application_id 
        JOIN users u ON a.student_id = u.id
        WHERE a.current_stage = 'approved'
        GROUP BY a.id 
        ORDER BY a.updated_at DESC";

$result = $conn->query($sql);
include '../../includes/header.php';
?>

<link rel="stylesheet" href="/irb-digital-system/assets/css/final_approvals.css">

<div class="container-fluid py-4">
    <div class="card shadow-sm border-0" style="border-radius: 15px; margin-right: 260px;">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0"><i class="fa-solid fa-clock-rotate-left text-primary me-2" style="margin-right: 30px; font-size: 1.50rem; font-weight: bold;"></i> سجل الاعتمادات والقرارات النهائية</h5>
        </div>
        <div class="card-body" style="margin-right: 30px; margin-bottom: 30px;">
            <div class="table-responsive">
                <table class="table table-hover align-middle text-center">
                    <thead class="table-light">
                        <tr>
                            <th>رقم البحث</th>
                            <th>عنوان البحث</th>
                            <th>اسم الباحث</th>
                            <th>تاريخ القرار</th>
                            <th>الحالة</th>
                            <th>الإجراء</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?= $row['serial_number'] ?></strong></td>
                                <td><?= htmlspecialchars($row['title']) ?></td>
                                <td><?= htmlspecialchars($row['student_name']) ?></td>
                                <td><?= irb_format_arabic_date($row['updated_at']); ?></td>
                                <td>
                                    <?php if ($row['current_stage'] == 'approved'): ?>
                                        <span class="badge bg-light-success text-success px-3 py-2">
                                            <i class="fa-solid fa-check-circle"></i> موافقة نهائية
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="decision_details.php?id=<?= $row['review_id'] ?>" class="btn btn-sm btn-outline-primary" style="text-decoration: none;">
                                        <i class="fa-solid fa-eye"></i> تفاصيل القرار
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?> </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
    document.getElementById('dashboardSearch').addEventListener('keyup', function() {
        let filter = this.value.toLowerCase();
        let rows = document.querySelectorAll('.table tbody tr');

        rows.forEach(row => {
            let text = row.innerText.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });
</script>