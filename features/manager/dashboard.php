<?php
require_once '../../classes/Database.php';
require_once '../../includes/irb_helpers.php';
$db = new Database();
$conn = $db->getconn();

        $sql = "SELECT 
            r.id as review_id,
            a.serial_number, 
            a.title, 
            u.full_name as student_name, 
            r.decision, 
            a.created_at,
            a.id as app_id
        FROM reviews r
        JOIN applications a ON r.application_id = a.id
        JOIN users u ON a.student_id = u.id
        WHERE a.current_stage = 'approved_by_reviewer' 
        AND r.decision = 'approved'
        ORDER BY a.created_at ASC";
$result = $conn->query($sql);

include '../../includes/header.php';
?>

<style>
    .table-card {
        background: #fff;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        margin: 20px;
    }

    .irb-table {
        width: 100%;
        border-collapse: collapse;
    }

    .irb-table th {
        text-align: right;
        padding: 12px;
        border-bottom: 2px solid #eee;
        color: #666;
    }

    .irb-table td {
        padding: 12px;
        border-bottom: 1px solid #f5f5f5;
    }

    .badge-success {
        background-color: #d1f7ed;
        color: #27ae60;
        padding: 4px 12px;
        border-radius: 15px;
        font-size: 13px;
    }

    .btn-view {
        color: #16a085;
        font-weight: bold;
        text-decoration: none;
    }

    .badge-warning {
        background-color: #fff4e5;
        color: #ff9800;
        padding: 4px 12px;
        border-radius: 15px;
        font-size: 13px;
    }

    .badge-danger {
        background-color: #fdeaea;
        color: #e74c3c;
        padding: 4px 12px;
        border-radius: 15px;
        font-size: 13px;
    }

    .main-content {
        margin-right: 260px;
        padding: 20px;
    }

    .table-card {
        background: #fff;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    }

    .badge {
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: bold;
    }

    .badge-success {
        background: #e6f7f0;
        color: #27ae60;
    }

    .badge-warning {
        background: #fff4e5;
        color: #ff9800;
    }

    .badge-danger {
        background: #fdeaea;
        color: #e74c3c;
    }

    .badge-info {
        background: #eef2ff;
        color: #4f46e5;
    }

    .top-navbar {
        position: fixed;
        top: 0;
        right: 260px;
        width: calc(100% - 260px);
        height: 70px;
        z-index: 1000;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0 30px;
    }
</style>

<div class="main-content" style="margin-top: 70px;">
    <div class="table-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                <h2 style="margin: 0; font-size: 20px;">قائمة القرارات النهائية</h2>
                <p style="color: #888; font-size: 14px; margin: 5px 0 0;">طلبات تنتظر الاعتماد النهائي للمدير</p>
            </div>
        </div>

        <table class="irb-table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="color: #888; border-bottom: 1px solid #eee;">
                    <th style="padding: 15px; text-align: right;">معرف البحث</th>
                    <th style="padding: 15px; text-align: right;">عنوان الدراسة</th>
                    <th style="padding: 15px; text-align: right;">الباحث الرئيسي</th>
                    <th style="padding: 15px; text-align: right;">تاريخ التقديم</th>
                    <th style="padding: 15px; text-align: right;">الحالة</th>
                    <th style="padding: 15px; text-align: right;">الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()):
                        $statusClass = '';
                        $statusText = '';

                        switch ($row['decision']) {
                            case 'approved':
                                $statusClass = 'badge-success';
                                $statusText = 'موصى بالموافقة';
                                break;
                            case 'needs_modification':
                                $statusClass = 'badge-warning';
                                $statusText = 'تعديلات طفيفة';
                                break;
                            case 'rejected':
                                $statusClass = 'badge-danger';
                                $statusText = 'رفض مقترح';
                                break;
                            case 'pending':
                                $statusClass = 'badge-info';
                                $statusText = 'قيد الانتظار';
                                break;
                            default:
                                $statusClass = 'badge-secondary';
                                $statusText = $row['decision'];
                        }
                ?>
                        <tr>
                            <td><strong><?= $row['serial_number'] ?></strong></td>
                            <td><?= $row['title'] ?></td>
                            <td><?= $row['student_name'] ?></td>
                            <td><?= irb_format_arabic_date($row['created_at']); ?></td>
                            <td><span class="badge <?= $statusClass ?>"><?= $statusText ?></span></td>
                            <td>
                                <a href="decision_details.php?id=<?= $row['review_id'] ?>" class="btn-view">
                                    <i class="fa-solid fa-eye"></i> عرض القرار
                                </a>
                            </td>
                        </tr>
                <?php
                    endwhile;
                }
                else {
                    
                    echo '<tr><td colspan="7" style="text-align: center; padding: 20px; color: #888;">سيظهر هنا أي بحث تمت مراجعته من قبل اللجنة ويحتاج لتوقيعك النهائي</td></tr>';
                    echo '<tr><td colspan="7" style="text-align: center; padding: 20px; color: #888;">لا توجد طلبات بانتظار الاعتماد حالياً</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
</div>
<script>
    document.getElementById('dashboardSearch').addEventListener('keyup', function() {
        let filter = this.value.toLowerCase();
        let rows = document.querySelectorAll('.irb-table tbody tr');

        rows.forEach(row => {
            let text = row.innerText.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });
</script>