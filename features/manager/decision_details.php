<?php
require_once "../../includes/irb_helpers.php";
include "../../includes/header.php";
require_once '../../classes/Database.php';

$statusMap = [
    'approved' => [
        'text' => 'موصى بالموافقة',
        'class' => 'decision-approved',
        'icon' => 'fa-check-circle'
    ],
    'needs_modification' => [
        'text' => 'تعديلات طفيفة',
        'class' => 'decision-needs-modification',
        'icon' => 'fa-exclamation-circle'
    ],
    'rejected' => [
        'text' => 'رفض مقترح',
        'class' => 'decision-rejected',
        'icon' => 'fa-times-circle'
    ],
    'pending' => [
        'text' => 'قيد الانتظار',
        'class' => 'decision-pending',
        'icon' => 'fa-hourglass-half'
    ]
];

if (isset($_GET['id'])) {
    $review_id = $_GET['id'];
    $db = new Database();
    $conn = $db->getconn();
    $sql = "SELECT r.*, a.title, a.serial_number,a.current_stage, u.full_name as student_name 
            FROM reviews r
            JOIN applications a ON r.application_id = a.id
            JOIN users u ON a.student_id = u.id
            WHERE r.id = $review_id";

    $result = $conn->query($sql);
    $review = $result->fetch_assoc();

    $comments_sql = "SELECT comment, created_at FROM review_comments WHERE review_id = $review_id ORDER BY created_at DESC";
    $comments_result = $conn->query($comments_sql);
}
?>

<link rel="stylesheet" href="../../assets/css/decision-details.css">

<div class="decision-details-page">
    <div class="container-fluid">
        <div class="decision-details-card">
            <div class="decision-header">
                <h3><i class="fa-solid fa-file-lines"></i> تفاصيل قرار المراجعة</h3>
            </div>

            <div class="decision-info-grid">
                <div class="info-item">
                    <label>عنوان الدراسة البحثية</label>
                    <p><?= htmlspecialchars($review['title'] ?? 'N/A') ?></p>
                </div>
                <div class="info-item">
                    <label>رقم السيريال</label>
                    <div class="serial-badge">
                        <i class="fa-solid fa-barcode"></i>
                        <?= htmlspecialchars($review['serial_number'] ?? 'N/A') ?>
                    </div>
                </div>
            </div>

            <div class="review-comment-section">
                <h5><i class="fa-solid fa-comment-dots"></i> تعليقات المراجع</h5>

                <?php if ($comments_result && $comments_result->num_rows > 0): ?>
                    <ul style="list-style: none; padding: 0;">
                        <?php while ($comment_row = $comments_result->fetch_assoc()): ?>
                            <li style="background: #f9f9f9; padding: 12px; border-radius: 8px; margin-bottom: 10px; border-right: 4px solid #16a085;">
                                <p style="margin: 0; font-size: 14px; color: #333;">
                                    <?= nl2br(htmlspecialchars($comment_row['comment'])) ?>
                                </p>
                                <small style="color: #888; font-size: 11px;">
                                    <i class="fa-regular fa-clock"></i> <?= $comment_row['created_at'] ?>
                                </small>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <p style="color: #888; font-style: italic;">لا توجد تعليقات مسجلة لهذا القرار.</p>
                <?php endif; ?>
            </div>

            <div class="decision-status-section">
                <h5>القرار الموصى به</h5>
                <?php
                $decision = $review['decision'] ?? 'approved';
                $status = $statusMap[$decision] ?? $statusMap['approved'];
                ?>
                <button class="decision-btn <?= $status['class'] ?>" disabled>
                    <i class="fa-solid <?= $status['icon'] ?>"></i>
                    <?= $status['text'] ?>
                </button>
            </div>

            <div class="decision-actions-footer">
                <?php
                if ($review['current_stage'] === 'approved_by_reviewer'): ?>

                    <a href="process_decision.php?id=<?= $review_id ?>&action=approve" class="btn btn-approve-final" style="text-decoration: none;">
                        <i class="fa-solid fa-check-circle"></i> اعتماد القرار النهائي
                    </a>
                    <a href="process_decision.php?id=<?= $review_id ?>&action=return" class="btn btn-return-reviewer" style="text-decoration: none;">
                        <i class="fa-solid fa-undo"></i> إعادة للمراجع
                    </a>

                <?php   
                elseif ($review['current_stage'] === 'approved'): ?>
                    <div class="alert alert-success" style="width: 100%; text-align: center;">
                        <i class="fa-solid fa-circle-check"></i> تم اعتماد البحث نهائياً وإصدار الشهادة.
                        <br><br>
                        <a href="view_certificate.php?app_id=<?= $review['application_id'] ?>" target="_blank" class="btn btn-view" style="background:#16a085; color:white; padding:8px 15px; border-radius:5px; text-decoration:none;">
                            <i class="fa-solid fa-file-pdf"></i> عرض الشهادة
                        </a>
                    </div>

                <?php  
                elseif ($review['current_stage'] === 'under_review'): ?>
                    <div class="alert alert-warning" style="width: 100%; text-align: center;">
                        <i class="fa-solid fa-reply"></i> تم إعادة الطلب للمراجع لإجراء تعديلات.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    document.querySelectorAll('.btn-approve-final, .btn-return-reviewer').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();

            const actionUrl = this.getAttribute('href');
            const actionText = this.innerText.trim();

            Swal.fire({
                title: 'تأكيد الإجراء',
                text: `هل أنت متأكد من ${actionText}؟`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#1abc9c',
                cancelButtonColor: '#95a5a6',
                confirmButtonText: 'نعم، تنفيذ',
                cancelButtonText: 'إلغاء'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(actionUrl)
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                if (actionUrl.includes('action=approve')) {
                                    Swal.fire({
                                        title: 'تم الاعتماد بنجاح!',
                                        text: 'تم تحديث حالة البحث وتوليد شهادة الاعتماد.',
                                        icon: 'success',
                                        showCancelButton: true,
                                        confirmButtonColor: '#16a085',
                                        confirmButtonText: '<i class="fa fa-eye"></i> عرض الشهادة',
                                        cancelButtonText: 'إغلاق'
                                    }).then((res) => {
                                        if (res.isConfirmed) {
                                            window.open(data.cert_url, '_blank');
                                        }
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        title: 'تم بنجاح!',
                                        text: 'تمت إعادة الطلب للمراجع.',
                                        icon: 'info',
                                        timer: 2000,
                                        showConfirmButton: false
                                    }).then(() => {
                                        location.reload();
                                    });
                                }
                            } else {
                                Swal.fire('تنبيه!', data.message, 'warning');
                            }
                        })
                        .catch(error => {
                            Swal.fire('خطأ!', 'حدثت مشكلة في الاتصال بالسيرفر.', 'error');
                        });
                }
            });
        });
    });
</script>