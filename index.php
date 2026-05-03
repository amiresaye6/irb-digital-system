<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/init.php';

$isLoggedIn = isset($_SESSION['user_id']);
$dashboard_url = '/irb-digital-system/features/auth/login.php';

if ($isLoggedIn && isset($_SESSION['role'])) {
    switch ($_SESSION['role']) {
        case 'student':
            $dashboard_url = '/irb-digital-system/features/student/dashboard.php';
            break;
        case 'admin':
            $dashboard_url = '/irb-digital-system/features/admin/dashboard.php';
            break;
        case 'sample_officer':
            $dashboard_url = '/irb-digital-system/features/sample_officer/dashboard.php';
            break;
        case 'reviewer':
            $dashboard_url = '/irb-digital-system/features/reviewer/dashboard.php';
            break;
        case 'manager':
            $dashboard_url = '/irb-digital-system/features/manager/dashboard.php';
            break;
    }
}

// Fetch Dynamic Statistics
$db = new Database();
$conn = $db->getconn();

$statApproved = 0;
$statStudents = 0;
$statReviewers = 0;

$res1 = $conn->query("SELECT COUNT(*) as cnt FROM applications WHERE current_stage = 'approved'");
if ($res1)
    $statApproved = $res1->fetch_assoc()['cnt'];

$res2 = $conn->query("SELECT COUNT(*) as cnt FROM users WHERE role = 'student'");
if ($res2)
    $statStudents = $res2->fetch_assoc()['cnt'];

$res3 = $conn->query("SELECT COUNT(*) as cnt FROM users WHERE role = 'reviewer'");
if ($res3)
    $statReviewers = $res3->fetch_assoc()['cnt'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" style="scroll-behavior: smooth;">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IRB Digital System - نظام الموافقات البحثية</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/irb-digital-system/assets/css/global.css">
    <link rel="stylesheet" href="/irb-digital-system/assets/css/landing.css">
</head>

<body>

    <nav class="navbar">
        <div class="nav-brand-area">
            <div class="nav-logos">
                <img src="/irb-digital-system/assets/images/university-logo.png" alt="شعار الجامعة" title="شعار الجامعة"
                    onerror="this.style.display='none'">
                <img src="/irb-digital-system/assets/images/faculty-logo2.png" alt="شعار كلية الطب"
                    title="شعار كلية الطب" onerror="this.style.display='none'">
            </div>

            <a href="#" class="nav-brand">
                <i class="fa-solid fa-microscope"></i>
                IRB Digital System
            </a>

            <div class="menu-toggle" id="mobileMenuBtn">
                <i class="fa-solid fa-bars-staggered"></i>
            </div>
        </div>

        <div class="nav-links" id="navLinks">
            <a href="#about" onclick="closeMenu()">عن اللجنة</a>
            <a href="#features" onclick="closeMenu()">المميزات</a>
            <a href="#workflow" onclick="closeMenu()">آلية العمل</a>
            <a href="verify.php" style="color: var(--accent-base);"><i class="fa-solid fa-shield-check"></i> التحقق من
                الشهادات</a>
            <?php if ($isLoggedIn): ?>
                <a href="<?= htmlspecialchars($dashboard_url) ?>" class="btn-nav"><i class="fa-solid fa-gauge"></i> لوحة
                    التحكم</a>
            <?php else: ?>
                <a href="/irb-digital-system/features/auth/login.php" class="btn-nav"><i
                        class="fa-solid fa-right-to-bracket"></i> تسجيل الدخول</a>
            <?php endif; ?>
        </div>
    </nav>

    <section class="hero fullscreen-section">
        <div class="hero-glow"></div>
        <div class="hero-content">
            <h1>النظام الرقمي الذكي لإدارة <span>الموافقات البحثية</span></h1>
            <p>منصة متكاملة ومؤتمتة لإدارة دورة حياة الأبحاث الطبية بكلية الطب. من تقديم البروتوكول والمراجعة العمياء،
                إلى الدفع الإلكتروني وإصدار شهادات الاعتماد النهائية بكل سهولة وشفافية.</p>
            <div class="hero-btns">
                <?php if ($isLoggedIn): ?>
                    <a href="<?= htmlspecialchars($dashboard_url) ?>" class="btn-primary"><i class="fa-solid fa-bolt"></i>
                        متابعة الأبحاث</a>
                <?php else: ?>
                    <a href="/irb-digital-system/features/auth/register.php" class="btn-primary"><i
                            class="fa-solid fa-user-plus"></i> إنشاء حساب كطالب</a>
                    <a href="#about" class="btn-outline"><i class="fa-solid fa-circle-info"></i> تعرف علينا</a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section id="about" class="about-irb fullscreen-section">
        <div class="about-container">
            <div class="about-text">
                <h2>عن لجنة أخلاقيات البحث العلمي (IRB)</h2>
                <p>تلتزم لجنة مراجعة الأبحاث (Institutional Review Board) في كليتنا بضمان حماية حقوق وسلامة ورفاهية
                    الأفراد المشاركين في الأبحاث الطبية والعلمية.</p>
                <p>نحن نعمل على تطبيق أعلى معايير الجودة والشفافية من خلال المراجعة العلمية المستقلة للبروتوكولات
                    البحثية، والتأكد من مطابقتها للمواثيق الأخلاقية المحلية والدولية، مع توفير بيئة داعمة للباحثين
                    لتيسير إجراءات الموافقة بسلاسة وفعالية.</p>
            </div>
            <div class="about-image">
                <i class="fa-solid fa-scale-balanced"></i>
            </div>
        </div>
    </section>

    <section class="stats">
        <div class="stats-grid">
            <div class="stat-item">
                <i class="fa-solid fa-file-circle-check"></i>
                <h3>+<?= number_format($statApproved) ?></h3>
                <p>بحث معتمد</p>
            </div>
            <div class="stat-item">
                <i class="fa-solid fa-users-viewfinder"></i>
                <h3>+<?= number_format($statStudents) ?></h3>
                <p>باحث مسجل</p>
            </div>
            <div class="stat-item">
                <i class="fa-solid fa-user-check"></i>
                <h3>+<?= number_format($statReviewers) ?></h3>
                <p>مراجع علمي</p>
            </div>
            <div class="stat-item">
                <i class="fa-solid fa-clock-rotate-left"></i>
                <h3>%100</h3>
                <p>إدارة رقمية متكاملة</p>
            </div>
        </div>
    </section>

    <section id="features" class="features fullscreen-section">
        <div>
            <h2 class="section-title">مميزات النظام</h2>
            <p class="section-subtitle">بيئة عمل ذكية تم تصميمها خصيصاً لتسهيل وتسريع إجراءات لجان أخلاقيات البحث العلمي
                وحوكمة البيانات.</p>

            <div class="features-grid">
                <div class="feature-card">
                    <i class="fa-solid fa-credit-card feature-icon"></i>
                    <h3>دفع إلكتروني متكامل</h3>
                    <p>تكامل تام مع منصات الدفع لتسديد رسوم التقديم الأولية ورسوم حساب حجم العينة واستخراج الإيصالات
                        فورياً.</p>
                </div>
                <div class="feature-card">
                    <i class="fa-solid fa-user-ninja feature-icon"></i>
                    <h3>المراجعة العمياء (Blind Review)</h3>
                    <p>نظام تقييم يضمن الشفافية والحيادية التامة من خلال إخفاء هوية الباحثين عن المراجعين العلميين
                        للجنة.</p>
                </div>
                <div class="feature-card">
                    <i class="fa-solid fa-calculator feature-icon"></i>
                    <h3>معالجة دقيقة للعينات</h3>
                    <p>مسار مخصص وذكي لضباط العينات لحساب التكلفة والمتطلبات الإحصائية للبحث وربطها آلياً بنظام الدفع.
                    </p>
                </div>
                <div class="feature-card">
                    <i class="fa-solid fa-certificate feature-icon"></i>
                    <h3>شهادات رقمية فورية</h3>
                    <p>بمجرد الاعتماد النهائي من مدير اللجنة، يمكن للطالب تحميل شهادة موافقة الـ IRB بصيغة PDF وموثقة.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <?php include __DIR__ . '/includes/landing/journey.php'; ?>

    <section id="verify-section" style="padding: 120px 50px; background: #fff; position: relative; overflow: hidden;">
        <div
            style="position: absolute; top: -50px; left: -50px; width: 200px; height: 200px; background: var(--accent-light); border-radius: 50%; opacity: 0.3;">
        </div>

        <div
            style="max-width: 1100px; margin: 0 auto; display: flex; align-items: center; gap: 60px; background: var(--primary-base); padding: 60px; border-radius: 40px; color: white; position: relative; z-index: 2;">
            <div style="flex: 1; text-align: right;">
                <h2 style="font-size: 2.5rem; font-weight: 900; margin-bottom: 20px; line-height: 1.3;">نظام التحقق من
                    <br><span style="color: var(--accent-base);">صحة الشهادات</span></h2>
                <p style="font-size: 1.1rem; opacity: 0.8; margin-bottom: 35px; line-height: 1.8;">نقدم ميزة التحقق
                    الفوري لضمان موثوقية الشهادات الصادرة. يمكن لأي جهة خارجية التأكد من بيانات الاعتماد عبر إدخال الكود
                    المرجعي للبحث.</p>
                <a href="verify.php" class="btn-primary"
                    style="display: inline-flex; align-items: center; gap: 12px; background: var(--accent-base); border: none;">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    ابدأ التحقق الآن
                </a>
            </div>
            <div style="flex: 0.8; display: flex; justify-content: center;">
                <div style="font-size: 15rem; color: rgba(255,255,255,0.1); transform: rotate(-15deg);">
                    <i class="fa-solid fa-certificate"></i>
                </div>
            </div>
        </div>
    </section>

    <footer class="main-footer">
        <div class="footer-container">
            <div class="footer-col">
                <h3><i class="fa-solid fa-microscope"></i> IRB Digital System</h3>
                <p>نظام رقمي متكامل صُمم خصيصاً لحوكمة وإدارة دورة حياة الأبحاث الطبية ولجان أخلاقيات البحث العلمي بكلية
                    الطب. نهدف إلى تسريع الإجراءات وضمان أعلى معايير الشفافية والحيادية.</p>
            </div>

            <div class="footer-col">
                <h3><i class="fa-solid fa-link"></i> روابط سريعة</h3>
                <ul class="footer-links">
                    <li><a href="#about"><i class="fa-solid fa-angle-left"></i> عن اللجنة (IRB)</a></li>
                    <li><a href="#features"><i class="fa-solid fa-angle-left"></i> مميزات المنصة</a></li>
                    <li><a href="#workflow"><i class="fa-solid fa-angle-left"></i> رحلة الموافقة</a></li>
                    <li><a href="/irb-digital-system/features/auth/login.php"><i class="fa-solid fa-angle-left"></i>
                            الدخول للوحة التحكم</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h3><i class="fa-solid fa-headset"></i> الدعم الفني والتواصل</h3>
                <ul class="footer-contact">
                    <li>
                        <i class="fa-solid fa-location-dot"></i>
                        <span>كلية الطب، مبنى إدارة البحوث، الدور الثالث.</span>
                    </li>
                    <li>
                        <i class="fa-solid fa-envelope"></i>
                        <span>support@irb-system.edu</span>
                    </li>
                    <li>
                        <i class="fa-solid fa-phone"></i>
                        <span>+20 123 456 7890</span>
                    </li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <p>جميع الحقوق محفوظة &copy; <?= date('Y') ?> | النظام الرقمي للموافقات البحثية (IRB)</p>
            <div class="social-icons">
                <a href="#" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                <a href="#" aria-label="Twitter"><i class="fa-brands fa-twitter"></i></a>
                <a href="#" aria-label="LinkedIn"><i class="fa-brands fa-linkedin-in"></i></a>
                <a href="#" aria-label="Website"><i class="fa-solid fa-globe"></i></a>
            </div>
        </div>
    </footer>

    <script>
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const navLinks = document.getElementById('navLinks');

        mobileMenuBtn.addEventListener('click', () => {
            navLinks.classList.toggle('active');
            const icon = mobileMenuBtn.querySelector('i');
            if (navLinks.classList.contains('active')) {
                icon.classList.remove('fa-bars-staggered');
                icon.classList.add('fa-xmark');
            } else {
                icon.classList.remove('fa-xmark');
                icon.classList.add('fa-bars-staggered');
            }
        });

        function closeMenu() {
            if (window.innerWidth <= 768) {
                navLinks.classList.remove('active');
                mobileMenuBtn.querySelector('i').classList.remove('fa-xmark');
                mobileMenuBtn.querySelector('i').classList.add('fa-bars-staggered');
            }
        }
    </script>
</body>

</html>