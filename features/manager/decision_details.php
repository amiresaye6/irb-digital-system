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
    $sql = "SELECT r.*, a.title, a.serial_number, u.full_name as student_name 
            FROM reviews r
            JOIN applications a ON r.application_id = a.id
            JOIN users u ON a.student_id = u.id
            WHERE r.id = $review_id";

    $result = $conn->query($sql);
    $review = $result->fetch_assoc();
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
                <h5><i class="fa-solid fa-comment-dots"></i> تعليق المراجع</h5>
                <p><?= nl2br(htmlspecialchars($review['comments'] ?? '')) ?></p>
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
                <a href="process_decision.php?id=<?= $review_id ?>&action=approve" class="btn btn-approve-final" style="text-decoration: none;">
                    <i class="fa-solid fa-check-circle"></i> اعتماد القرار النهائي
                </a>
                <a href="process_decision.php?id=<?= $review_id ?>&action=return" class="btn btn-return-reviewer" style="text-decoration: none;">
                    <i class="fa-solid fa-undo"></i> إعادة للمراجع
                </a>
            </div>
        </div>
    </div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.querySelectorAll('.decision-actions-footer .btn').forEach(button => {
    button.addEventListener('click', function(e) {
        e.preventDefault(); // منع الصفحة من التحميل أو الانتقال
        
        const actionUrl = this.getAttribute('href'); // هيجيب الرابط اللي فيه الـ id والـ action
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
                // تنفيذ الأكشن باستخدام Fetch (AJAX)
                fetch(actionUrl)
                    .then(response => {
                        // إظهار رسالة نجاح تظهر وتختفي لوحدها
                        Swal.fire({
                            title: 'تم بنجاح!',
                            text: 'تم تحديث حالة القرار فوراً.',
                            icon: 'success',
                            timer: 2000, // هتختفي بعد ثانيتين
                            showConfirmButton: false
                        });

                        // اختياري: ممكن تعطلي الزراير بعد التنفيذ عشان ميدوسش تاني
                        document.querySelector('.decision-actions-footer').style.display = 'none';
                    })
                    .catch(error => {
                        Swal.fire('خطأ!', 'حدثت مشكلة أثناء التحديث.', 'error');
                    });
            }
        });
    });
});
</script>