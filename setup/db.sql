-- Disable foreign key checks to ensure smooth creation
SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS irb_system;

USE irb_system;

-- 1. Drop existing tables if they exist
DROP TABLE IF EXISTS logs;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS certificates;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS documents;
DROP TABLE IF EXISTS applications;
DROP TABLE IF EXISTS users;

-- 2. Create Tables
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role ENUM('student','admin','sample_officer','reviewer','manager') NOT NULL,
    full_name VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    email VARCHAR(255) UNIQUE,
    password_hash VARCHAR(255),
    national_id VARCHAR(20),
    phone_number VARCHAR(20),
    faculty VARCHAR(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    department VARCHAR(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    id_front_url VARCHAR(255),
    id_back_url VARCHAR(255),
    is_active BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT,
    serial_number VARCHAR(50) UNIQUE,
    title TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    principal_investigator VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    co_investigators JSON,
    sample_size INT NULL,
    current_stage ENUM(
        'pending_admin', 'awaiting_initial_payment', 'awaiting_sample_calc',
        'awaiting_sample_payment', 'under_review', 'approved', 'rejected'
    ) DEFAULT 'pending_admin',
    is_blinded BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX(student_id),
    INDEX(current_stage)
);

CREATE TABLE documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT,
    document_type ENUM(
        'research','protocol', 'conflict_of_interest', 'irb_checklist',
        'pi_consent', 'patient_consent', 'photos_biopsies_consent','protocol_review_app'
    ),
    file_path VARCHAR(255),
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
);

CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT,
    phase ENUM('initial','sample'),
    amount DECIMAL(10,2),
    provider VARCHAR(50),
    transaction_reference VARCHAR(100),
    status ENUM('pending','completed','failed') DEFAULT 'pending',
    paid_at TIMESTAMP NULL,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
);

CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT,
    reviewer_id INT,
    assigned_by INT,
    decision ENUM('pending','approved','needs_modification','rejected') DEFAULT 'pending',
    comments TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    reviewed_at TIMESTAMP NULL,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewer_id) REFERENCES users(id),
    FOREIGN KEY (assigned_by) REFERENCES users(id)
);

CREATE TABLE certificates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT UNIQUE,
    manager_id INT,
    certificate_number VARCHAR(100),
    issued_to_name VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    pdf_url VARCHAR(255),
    issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (manager_id) REFERENCES users(id)
);

CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    message TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    channel ENUM('system','email','sms'),
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT,
    user_id INT,
    action VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- 3. Seed Users (Password for all is: password)
-- The hash below is standard PHP bcrypt for 'password'
INSERT INTO users (role, full_name, email, password_hash, national_id, phone_number, faculty, department, id_front_url, id_back_url, is_active) VALUES 
('student', 'د. عمر الفاروق', 'omar@med.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '29001011234001', '01011111111', 'كلية الطب', 'الجراحة العامة', 'uploads/seed/dummy_id_front.jpg', 'uploads/seed/dummy_id_back.jpg', 1),
('student', 'د. ليلى عثمان', 'laila@med.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '29001011234002', '01022222222', 'كلية الطب', 'الأطفال', 'uploads/seed/dummy_id_front.jpg', 'uploads/seed/dummy_id_back.jpg', 1),
('student', 'د. كريم محسن', 'karim@med.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '29001011234003', '01033333333', 'كلية الطب', 'النساء والتوليد', 'uploads/seed/dummy_id_front.jpg', 'uploads/seed/dummy_id_back.jpg', 1),
('student', 'د. نهى عبد الرحمن', 'noha@med.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '29001011234004', '01044444444', 'كلية الصيدلة', 'الصيدلانيات', 'uploads/seed/dummy_id_front.jpg', 'uploads/seed/dummy_id_back.jpg', 1),
('admin', 'أستاذ محمود (أدمن اللجان)', 'admin@irb.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '28001011234005', '01112345678', NULL, NULL, NULL, NULL, 1),
('sample_officer', 'م. حسام (الإحصاء الطبي)', 'sample1@irb.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '27001011234006', '01212345678', NULL, NULL, NULL, NULL, 1),
('sample_officer', 'م. رشا (مسؤول عينات)', 'sample2@irb.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '27001011234007', '01212345679', NULL, NULL, NULL, NULL, 1),
('reviewer', 'أ.د. خالد عبد السلام', 'khaled.rev@irb.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '26001011234008', '01512345678', 'كلية الطب', 'الباطنة', NULL, NULL, 1),
('reviewer', 'أ.د. هدى الشربيني', 'hoda.rev@irb.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '26001011234009', '01512345679', 'كلية الطب', 'الأورام', NULL, NULL, 1),
('reviewer', 'أ.د. عصام النجار', 'essam.rev@irb.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '26001011234010', '01512345680', 'كلية الطب', 'الصحة العامة', NULL, NULL, 1),
('manager', 'أ.د. طارق الحديدي (مدير IRB)', 'manager@irb.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '25001011234011', '01099999999', 'كلية الطب', 'إدارة الجودة والبحث', NULL, NULL, 1);

-- 4. Seed Applications
INSERT INTO applications (student_id, serial_number, title, principal_investigator, co_investigators, sample_size, current_stage, is_blinded, created_at) VALUES 
(1, 'IRB-2026-001', 'تأثير الأدوية الحديثة على مرضى السكري من النوع الثاني المتقدم', 'د. عمر الفاروق', '["د. أحمد مصطفى", "د. سارة كمال"]', 150, 'approved', 1, '2026-03-01 10:00:00'),
(2, 'IRB-2026-002', 'معدلات انتشار السمنة المفرطة بين طلاب المدارس الابتدائية في الدلتا', 'د. ليلى عثمان', '["د. يوسف الشناوي"]', 500, 'under_review', 1, '2026-04-10 11:30:00'),
(3, 'IRB-2026-003', 'مقارنة بين تقنيات التخدير الموضعي والكلي في جراحات الفتق بالمنظار', 'د. كريم محسن', '[]', NULL, 'awaiting_sample_calc', 1, '2026-04-15 09:15:00'),
(4, 'IRB-2026-004', 'تقييم فعالية المضادات الحيوية واسعة المجال في التهابات الجهاز التنفسي', 'د. نهى عبد الرحمن', '["د. منى زكي", "د. رامي إمام", "د. حسن يوسف"]', 300, 'rejected', 1, '2026-02-20 14:00:00'),
(1, 'IRB-2026-005', 'استخدام الذكاء الاصطناعي في التشخيص المبكر لاعتلال الشبكية السكري', 'د. عمر الفاروق', '["د. أحمد مصطفى", "د. سارة كمال"]', NULL, 'awaiting_initial_payment', 1, '2026-04-20 16:45:00'),
(2, 'IRB-2026-006', 'مدى استجابة الأطفال الخدج لبروتوكولات التغذية الوريدية الحديثة', 'د. ليلى عثمان', '[]', 80, 'pending_admin', 1, '2026-04-21 08:00:00');

-- 5. Seed Documents
INSERT INTO documents (application_id, document_type, file_path) VALUES 
(1, 'protocol', 'uploads/seed/dummy_protocol.pdf'), 
(1, 'conflict_of_interest', 'uploads/seed/dummy_conflict.pdf'),
(1, 'irb_checklist', 'uploads/seed/dummy_checklist.pdf'), 
(1, 'pi_consent', 'uploads/seed/dummy_pi_consent.pdf'),
(1, 'patient_consent', 'uploads/seed/dummy_patient_consent.pdf'),
(2, 'protocol', 'uploads/seed/dummy_protocol.pdf'), 
(2, 'irb_checklist', 'uploads/seed/dummy_checklist.pdf'),
(2, 'pi_consent', 'uploads/seed/dummy_pi_consent.pdf'),
(3, 'protocol', 'uploads/seed/dummy_protocol.pdf'), 
(4, 'protocol', 'uploads/seed/dummy_protocol.pdf'),
(5, 'protocol', 'uploads/seed/dummy_protocol.pdf'), 
(6, 'protocol', 'uploads/seed/dummy_protocol.pdf'),
(1, 'research', 'uploads/seed/dummy_research.pdf'),
(1, 'protocol_review_app', 'uploads/seed/dummy_protocol_review_app.pdf'),
(2, 'research', 'uploads/seed/dummy_research_2.pdf'),
(3, 'protocol_review_app', 'uploads/seed/dummy_protocol_review_app_3.pdf'),
(4, 'research', 'uploads/seed/dummy_research_4.pdf');

-- 6. Seed Payments
INSERT INTO payments (application_id, phase, amount, provider, transaction_reference, status, paid_at) VALUES 
(1, 'initial', 500.00, 'Fawry', 'FW1001', 'completed', '2026-03-02 10:00:00'),
(1, 'sample', 350.00, 'Fawry', 'FW1002', 'completed', '2026-03-05 12:00:00'),
(2, 'initial', 500.00, 'Paymob', 'PM2001', 'completed', '2026-04-11 09:00:00'),
(2, 'sample', 800.00, 'Fawry', 'FW2002', 'completed', '2026-04-14 10:00:00'),
(3, 'initial', 500.00, 'InstaPay', 'IP3001', 'completed', '2026-04-16 11:00:00'),
(4, 'initial', 500.00, 'Fawry', 'FW4001', 'completed', '2026-02-21 10:00:00'),
(4, 'sample', 400.00, 'Fawry', 'FW4002', 'completed', '2026-02-25 10:00:00'),
(5, 'initial', 500.00, 'Fawry', NULL, 'pending', NULL);

-- 7. Seed Reviews
INSERT INTO reviews (application_id, reviewer_id, assigned_by, decision, comments, reviewed_at) VALUES 
(1, 8, 5, 'approved', 'منهجية البحث ممتازة، ولا يوجد مانع أخلاقي من التطبيق.', '2026-03-10 10:00:00'),
(1, 9, 5, 'approved', 'موافق. أهداف الدراسة واضحة وإقرار المرضى مستوفي الشروط.', '2026-03-11 14:00:00'),
(2, 10, 5, 'needs_modification', 'يجب توضيح كيفية حماية بيانات الأطفال المشاركين في الدراسة بدقة أكبر في نموذج الموافقة المستنيرة.', '2026-04-18 09:00:00'),
(2, 8, 5, 'pending', NULL, NULL),
(4, 9, 5, 'rejected', 'يوجد تضارب مصالح واضح مع الشركة المصنعة للمضاد الحيوي لم يتم الإفصاح عنه بشكل كافٍ في النماذج.', '2026-02-28 12:00:00');

-- 8. Seed Certificates
INSERT INTO certificates (application_id, manager_id, certificate_number, issued_to_name, pdf_url, issued_at) VALUES 
(1, 11, 'CERT-2026-10045', 'د. عمر الفاروق', 'uploads/seed/dummy_certificate.pdf', '2026-03-15 10:00:00');

-- 9. Seed Logs
INSERT INTO logs (application_id, user_id, action, created_at) VALUES 
(1, 1, 'تم تقديم البحث بنجاح ورفع المستندات', '2026-03-01 10:00:00'),
(1, 5, 'مراجعة أولية وتوليد رقم تسلسلي للملف', '2026-03-01 12:00:00'),
(1, 6, 'تم حساب حجم العينة (150)', '2026-03-03 09:00:00'),
(1, 11, 'اعتماد نهائي وإصدار شهادة IRB', '2026-03-15 10:00:00'),
(4, 5, 'تحديث حالة البحث إلى مرفوض بناءً على تقرير المراجعة', '2026-02-28 13:00:00');

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;