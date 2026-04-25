<?php
// Ensure session is started if not already
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IRB System | نظام إدارة الموافقات البحثية</title>

    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="/irb-digital-system/includes/style.css">
    <link rel="stylesheet" href="/irb-digital-system/assets/css/global.css">
</head>

<body>
    <div class="app-wrapper">

        <header class="top-navbar">
            <div class="nav-brand">
                <i class="fa-solid fa-microscope"></i>
                <h1>لجنة مراجعة الأبحاث (IRB)</h1>
            </div>
            <div class="nav-search">
                <div class="search-wrapper">
                    <input type="text" placeholder="البحث عن دراسة، باحث أو رقم ملف..." id="dashboardSearch">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>
            </div>
            <div class="nav-user">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <span class="user-greeting">مرحباً، <?= htmlspecialchars($_SESSION['full_name']); ?></span>
                    <a href="/irb-digital-system/features/auth/logout.php" class="btn-logout"><i class="fa-solid fa-right-from-bracket"></i> خروج</a>
                <?php else: ?>
                    <a href="/irb-digital-system/features/auth/login.php" class="btn-login"><i class="fa-solid fa-right-to-bracket"></i> تسجيل الدخول</a>
                <?php endif; ?>
            </div>
        </header>

        <div class="main-layout">
            <?php
            if (isset($_SESSION['user_id'])) {
                include __DIR__ . '/sidebar.php';
            }
            ?>

            <main class="content-area"></main>