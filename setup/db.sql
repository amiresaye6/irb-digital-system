-- Disable foreign key checks to ensure smooth creation
SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS irb_system;

USE irb_system;

-- 1. Drop existing tables if they exist
DROP TABLE IF EXISTS signatures;
DROP TABLE IF EXISTS logs;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS certificates;
DROP TABLE IF EXISTS review_comments;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS documents;
DROP TABLE IF EXISTS sample_sizes;
DROP TABLE IF EXISTS applications;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS password_resets;


DROP EVENT IF EXISTS evt_twelve_hour_payment_cleanup;
DROP PROCEDURE IF EXISTS  sp_cleanup_expired_payments;

DROP EVENT IF EXISTS evt_48hr_review_timeout;
DROP PROCEDURE IF EXISTS  sp_cleanup_expired_reviews;

-- 2. Create Tables
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role ENUM('student','admin','sample_officer','reviewer','manager','super_admin') NOT NULL,
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
    current_stage ENUM(
        'pending_admin', 'awaiting_initial_payment', 'awaiting_sample_calc',
        'awaiting_sample_payment', 'under_review','approved_by_reviewer','approved', 'rejected'
    ) DEFAULT 'pending_admin',
    is_blinded BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX(student_id),
    INDEX(current_stage)
);

CREATE TABLE sample_sizes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT,
    sampler_id INT,
    calculated_size INT,
    sample_amount DECIMAL(10,2),
    notes TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (sampler_id) REFERENCES users(id)
);

Create TABLE keywords (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT,
    serial_number VARCHAR(20),
    keyword VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
);

CREATE TABLE documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT,
    document_type ENUM(
        'research','protocol', 'conflict_of_interest', 'irb_checklist',
        'pi_consent', 'patient_consent', 'photos_biopsies_consent' ,'protocol_review_app'
    ) NOT NULL, 
    file_path VARCHAR(255),
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
);

CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT,
    phase ENUM('initial','sample'),
    amount DECIMAL(10,2) ,
    provider VARCHAR(50),
    transaction_reference VARCHAR(100),
    gateway_transaction_id VARCHAR(100) NULL,
    status ENUM('pending','completed','failed') DEFAULT 'pending',
    gateway_response JSON NULL,
    paid_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
);
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT,
    reviewer_id INT,
    assigned_by INT,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    assignment_status ENUM(
        'awaiting_acceptance', 
        'accepted', 
        'refused', 
        'timed_out'
    ) DEFAULT 'awaiting_acceptance',

    decision ENUM(
        'pending', 
        'approved', 
        'needs_modification', 
        'rejected'
    ) DEFAULT 'pending',

    refusal_reason TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
    reviewed_at TIMESTAMP NULL,
    
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewer_id) REFERENCES users(id),
    FOREIGN KEY (assigned_by) REFERENCES users(id)
);

CREATE TABLE review_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    review_id INT NOT NULL,
    comment TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE
);

CREATE TABLE certificates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT UNIQUE,
    student_id INT,
    manager_id INT,
    certificate_number VARCHAR(100),
    issued_to_name VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    pdf_url VARCHAR(255),
    issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (manager_id) REFERENCES users(id),
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);


-- ALTER TABLE certificates 
-- ADD COLUMN student_id INT AFTER application_id,
-- ADD FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE;


CREATE TABLE signatures (
    signatureId INT PRIMARY KEY AUTO_INCREMENT,
    userId INT NOT NULL, 
    signature_url VARCHAR(255) NOT NULL, 
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY (userId), 
    FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    application_id INT NULL,
    message TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    channel ENUM('system','email','sms'),
    is_read BOOLEAN DEFAULT FALSE,
    email_sent BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE SET NULL
);

CREATE TABLE logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT,
    user_id INT,
    action VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    type VARCHAR(50) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Triggers for Payments
SET GLOBAL event_scheduler = ON;
DELIMITER //

-- stord proceadure to make any unpayed link failed after 12 hours  

CREATE PROCEDURE sp_cleanup_expired_payments()
BEGIN
    -- 1. Log the automated failure for auditing
    INSERT INTO logs (application_id, user_id, action, type)
    SELECT application_id, NULL, 'تم تحويل الدفع إلى فاشل تلقائياً لانتهاء صلاحية الرابط (12 ساعة)', 'system_cleanup'
    FROM payments 
    WHERE status = 'pending' 
    AND created_at < NOW() - INTERVAL 12 HOUR;

    -- 2. Update the status to failed
    UPDATE payments 
    SET status = 'failed' 
    WHERE status = 'pending' 
    AND created_at < NOW() - INTERVAL 12 HOUR;
END //

-- stord proceadure to for expired reviews
CREATE PROCEDURE sp_cleanup_expired_reviews()
BEGIN
    -- 1. Log the timeout for auditing
    INSERT INTO logs (application_id, user_id, action, type)
    SELECT application_id, reviewer_id, 'تم انتهاء مهلة قبول المراجعة تلقائياً (48 ساعة)', 'system_timeout'
    FROM reviews 
    WHERE assignment_status = 'awaiting_acceptance' 
    AND assigned_at < NOW() - INTERVAL 48 HOUR;

    -- 2. Update the status to timed_out
    UPDATE reviews 
    SET assignment_status = 'timed_out' 
    WHERE assignment_status = 'awaiting_acceptance' 
    AND assigned_at < NOW() - INTERVAL 48 HOUR;
END //

-- CREATE TRIGGER before_payments_insert
-- BEFORE INSERT ON payments
-- FOR EACH ROW
-- BEGIN
--     IF NEW.phase = 'initial' THEN
--         SET NEW.amount = 500.00;
--     ELSEIF NEW.phase = 'sample' THEN
--         SET NEW.amount = (SELECT sample_amount FROM sample_sizes WHERE application_id = NEW.application_id LIMIT 1);
--     END IF;
-- END//

-- CREATE TRIGGER before_payments_update
-- BEFORE UPDATE ON payments
-- FOR EACH ROW
-- BEGIN
--     IF NEW.phase = 'initial' THEN
--         SET NEW.amount = 500.00;
--     ELSEIF NEW.phase = 'sample' THEN
--         SET NEW.amount = (SELECT sample_amount FROM sample_sizes WHERE application_id = NEW.application_id LIMIT 1);
--     END IF;
-- END//

CREATE TRIGGER after_reviews_update
AFTER UPDATE ON reviews
FOR EACH ROW
BEGIN
    IF NEW.decision = 'approved' AND OLD.decision != 'approved' THEN
        UPDATE applications 
        SET current_stage = 'approved_by_reviewer' 
        WHERE id = NEW.application_id AND current_stage = 'under_review';
    END IF;
    IF NEW.decision != OLD.decision THEN
        INSERT INTO logs (application_id, user_id, action, type) 
        VALUES (NEW.application_id, NEW.reviewer_id, 
            CONCAT('المراجع سجل قراره: ', 
                CASE NEW.decision 
                    WHEN 'approved' THEN 'موافقة'
                    WHEN 'needs_modification' THEN 'يحتاج تعديلات'
                    WHEN 'rejected' THEN 'مرفوض'
                    ELSE NEW.decision 
                END
            ),
            'decision'
        );
    END IF;
END//

CREATE TRIGGER after_applications_update_log
AFTER UPDATE ON applications
FOR EACH ROW
BEGIN
    IF NEW.current_stage != OLD.current_stage THEN
        INSERT INTO logs (application_id, user_id, action, type) 
        VALUES (NEW.id, NULL, CONCAT('تحديث حالة البحث إلى: ', NEW.current_stage), 'status_change');
    END IF;
END//

CREATE TRIGGER after_documents_insert_log
AFTER INSERT ON documents
FOR EACH ROW
BEGIN
    INSERT INTO logs (application_id, user_id, action, type) 
    VALUES (NEW.application_id, NULL, CONCAT('تم رفع مستند جديد: ', NEW.document_type), 'document');
END//

CREATE TRIGGER after_documents_update_log
AFTER UPDATE ON documents
FOR EACH ROW
BEGIN
    IF NEW.file_path != OLD.file_path THEN
        INSERT INTO logs (application_id, user_id, action, type) 
        VALUES (NEW.application_id, NULL, CONCAT('تم تحديث مستند: ', NEW.document_type), 'document');
    END IF;
END//

DELIMITER ;

-- create and run the failed payments update event
CREATE EVENT evt_twelve_hour_payment_cleanup
ON SCHEDULE EVERY 12 HOUR
STARTS CURRENT_TIMESTAMP
DO
    CALL sp_cleanup_expired_payments();

CREATE EVENT evt_48hr_review_timeout
ON SCHEDULE EVERY 1 HOUR
STARTS CURRENT_TIMESTAMP
DO
    CALL sp_cleanup_expired_reviews();

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
('manager', 'أ.د. طارق الحديدي (مدير IRB)', 'manager@irb.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '25001011234011', '01099999999', 'كلية الطب', 'إدارة الجودة والبحث', NULL, NULL, 1),
('student', 'د. يوسف الشناوي', 'youssef@med.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '29001011234012', '01055555555', 'كلية الطب', 'جراحة العظام', 'uploads/seed/dummy_id_front.jpg', 'uploads/seed/dummy_id_back.jpg', 1),
('student', 'د. سلمى رضا', 'salma@med.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '29001011234013', '01066666666', 'كلية الأسنان', 'طب الفم والأسنان', 'uploads/seed/dummy_id_front.jpg', 'uploads/seed/dummy_id_back.jpg', 1),
('student', 'د. ماجد توفيق', 'maged@med.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '29001011234014', '01077777777', 'كلية التمريض', 'تمريض باطني وجراحي', 'uploads/seed/dummy_id_front.jpg', 'uploads/seed/dummy_id_back.jpg', 1),
('student', 'د. سارة كمال', 'sara@med.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '29001011234015', '01088888888', 'كلية الطب', 'الرمد', 'uploads/seed/dummy_id_front.jpg', 'uploads/seed/dummy_id_back.jpg', 1),
('student', 'د. أحمد مصطفى', 'ahmed@med.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '29001011234016', '01099999990', 'كلية الطب', 'الباطنة', 'uploads/seed/dummy_id_front.jpg', 'uploads/seed/dummy_id_back.jpg', 1),
('super_admin', 'أ.د. احمد عناني', 'superAdmin@irb.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '21001011234605', '01112345699', NULL, NULL, NULL, NULL, 1);

-- 4. Seed Applications
INSERT INTO applications (student_id, serial_number, title, principal_investigator, co_investigators, current_stage, is_blinded, created_at) VALUES 
(1, 'IRB-2026-001', 'تأثير الأدوية الحديثة على مرضى السكري من النوع الثاني المتقدم', 'د. عمر الفاروق', '["د. أحمد مصطفى", "د. سارة كمال"]', 'approved', 1, '2026-03-01 10:00:00'),
(2, 'IRB-2026-002', 'معدلات انتشار السمنة المفرطة بين طلاب المدارس الابتدائية في الدلتا', 'د. ليلى عثمان', '["د. يوسف الشناوي"]', 'under_review', 1, '2026-04-10 11:30:00'),
(3, 'IRB-2026-003', 'مقارنة بين تقنيات التخدير الموضعي والكلي في جراحات الفتق بالمنظار', 'د. كريم محسن', '[]', 'awaiting_sample_calc', 1, '2026-04-15 09:15:00'),
(4, 'IRB-2026-004', 'تقييم فعالية المضادات الحيوية واسعة المجال في التهابات الجهاز التنفسي', 'د. نهى عبد الرحمن', '["د. منى زكي", "د. رامي إمام", "د. حسن يوسف"]', 'rejected', 1, '2026-02-20 14:00:00'),
(1, 'IRB-2026-005', 'استخدام الذكاء الاصطناعي في التشخيص المبكر لاعتلال الشبكية السكري', 'د. عمر الفاروق', '["د. أحمد مصطفى", "د. سارة كمال"]', 'awaiting_initial_payment', 1, '2026-04-20 16:45:00'),
(2, 'IRB-2026-006', 'مدى استجابة الأطفال الخدج لبروتوكولات التغذية الوريدية الحديثة', 'د. ليلى عثمان', '[]', 'pending_admin', 1, '2026-04-21 08:00:00'),
(12, 'IRB-2026-007', 'أثر العلاج الطبيعي المكثف بعد جراحات استبدال مفصل الركبة', 'د. يوسف الشناوي', '[]', 'awaiting_sample_payment', 1, '2026-04-22 09:00:00'),
(13, 'IRB-2026-008', 'مدى انتشار تسوس الأسنان لدى الأطفال في المناطق الريفية', 'د. سلمى رضا', '["د. هالة صدقي"]', 'approved', 1, '2026-01-15 10:30:00'),
(14, 'IRB-2026-009', 'تأثير برامج التثقيف الصحي على جودة الحياة لمرضى الفشل الكلوي', 'د. ماجد توفيق', '[]', 'under_review', 1, '2026-04-18 11:15:00'),
(15, 'IRB-2026-010', 'مضاعفات جراحات المياه البيضاء وعلاقتها بالأمراض المزمنة', 'د. سارة كمال', '["د. عمر الفاروق"]', 'awaiting_sample_calc', 1, '2026-04-23 08:30:00'),
(16, 'IRB-2026-011', 'فاعلية البروتوكولات المستحدثة في علاج جرثومة المعدة', 'د. أحمد مصطفى', '[]', 'pending_admin', 1, '2026-04-23 12:00:00'),
(14, 'IRB-2026-012', 'تأثير الإدارة الذاتية لمرضى الربو على تقليل نوبات الاختناق', 'د. ماجد توفيق', '[]', 'approved_by_reviewer', 1, '2026-04-20 11:00:00'),
(15, 'IRB-2026-013', 'مضاعفات ارتفاع ضغط الدم وتأثيره على شبكية العين', 'د. سارة كمال', '["د. ليلى عثمان"]', 'approved_by_reviewer', 1, '2026-04-21 12:00:00'),
(1, 'IRB-2026-014', 'مقارنة تأثير التدخل الجراحي المبكر والتأخير في حالات الكسور المضاعفة', 'د. عمر الفاروق', '[]', 'under_review', 1, '2026-04-22 09:00:00'),
(2, 'IRB-2026-015', 'تأثير السهر الطويل على كفاءة الأداء الدراسي لدى المراهقين', 'د. ليلى عثمان', '[]', 'approved', 1, '2026-04-23 10:00:00');

-- 5. Seed Sample Sizes
INSERT INTO sample_sizes (application_id, sampler_id, calculated_size, sample_amount, notes, created_at) VALUES 
(1, 6, 150, 350.00, 'تم حساب العينة بناء على معدل الانتشار السنوي لمرض السكري وتم إضافة 10% لتجنب التسرب', '2026-03-03 09:00:00'),
(2, 7, 500, 800.00, 'حجم العينة ممثل لمدارس المرحلة الابتدائية بعدة محافظات في الدلتا', '2026-04-12 10:15:00'),
(4, 6, 300, 400.00, 'الحد الأدنى المطلوب لتحقيق دلالة إحصائية في هذه المقارنة', '2026-02-23 11:30:00'),
(7, 6, 60, 300.00, 'حجم العينة كافٍ للدراسة المقطعية مع مراعاة الندرة النسبية', '2026-04-22 14:00:00'),
(8, 7, 1000, 1200.00, 'حجم العينة كبير نسبياً لتغطية الاختلافات الجغرافية في ريف المحافظة', '2026-01-20 09:45:00'),
(9, 6, 120, 400.00, 'تم الحساب بناء على قوة إحصائية 80% ومستوى ثقة 95%', '2026-04-19 13:20:00'),
(12, 6, 200, 400.00, 'عينة ممثلة للطلاب', '2026-04-21 09:00:00'),
(13, 7, 150, 300.00, 'تم احتساب الحجم المطلوب', '2026-04-22 09:00:00'),
(14, 6, 180, 350.00, 'حجم مناسب', '2026-04-23 09:00:00'),
(15, 7, 300, 500.00, 'تمت الموافقة على الحجم', '2026-04-23 10:00:00');

-- 6. Seed Documents
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
(7, 'protocol', 'uploads/seed/dummy_protocol.pdf'),
(8, 'protocol', 'uploads/seed/dummy_protocol.pdf'),
(9, 'protocol', 'uploads/seed/dummy_protocol.pdf'),
(10, 'protocol', 'uploads/seed/dummy_protocol.pdf'),
(11, 'protocol', 'uploads/seed/dummy_protocol.pdf'),
(12, 'protocol', 'uploads/seed/dummy_protocol.pdf'),
(13, 'protocol', 'uploads/seed/dummy_protocol.pdf'),
(14, 'protocol', 'uploads/seed/dummy_protocol.pdf'),
(15, 'protocol', 'uploads/seed/dummy_protocol.pdf');

-- 6. Seed Payments
INSERT INTO payments (application_id, phase, amount, provider, transaction_reference, gateway_transaction_id, status, gateway_response, paid_at, created_at) VALUES 
(1, 'initial', 500.00, 'Fawry', 'FW1001', '511625001', 'completed', '{"message": "Approved"}', '2026-03-02 10:00:00', '2026-03-02 09:50:00'),
(1, 'sample', 350.00, 'Fawry', 'FW1002', '511625002', 'completed', '{"message": "Approved"}', '2026-03-05 12:00:00', '2026-03-05 11:45:00'),
(2, 'initial', 500.00, 'Paymob', 'PM2001', '511625436', 'completed', '{"message": "Approved", "source_data": {"type": "card", "sub_type": "MasterCard"}}', '2026-04-11 09:00:00', '2026-04-11 08:55:00'),
(2, 'sample', 800.00, 'Fawry', 'FW2002', '511625004', 'completed', '{"message": "Approved"}', '2026-04-14 10:00:00', '2026-04-14 09:50:00'),
(3, 'initial', 500.00, 'InstaPay', 'IP3001', '511625005', 'completed', '{"message": "Approved"}', '2026-04-16 11:00:00', '2026-04-16 10:55:00'),
(4, 'initial', 500.00, 'Fawry', 'FW4001', '511625006', 'completed', '{"message": "Approved"}', '2026-02-21 10:00:00', '2026-02-21 09:55:00'),
(4, 'sample', 400.00, 'Fawry', 'FW4002', '511625007', 'completed', '{"message": "Approved"}', '2026-02-25 10:00:00', '2026-02-25 09:50:00'),
(5, 'initial', 500.00, 'Paymob', 'PM5001', '511676577', 'pending', NULL, NULL, '2026-04-23 19:43:43');

-- 8. Seed Reviews (Updated with Workflow State & Academic Decision)
INSERT INTO reviews (application_id, reviewer_id, assigned_by, assigned_at, assignment_status, decision, refusal_reason, reviewed_at) VALUES 
(1, 8, 5, '2026-03-08 09:00:00', 'accepted', 'approved', NULL, '2026-03-10 10:00:00'),
(2, 8, 5, '2026-05-02 10:00:00', 'awaiting_acceptance', 'pending', NULL, NULL),
(4, 9, 5, '2026-02-26 11:00:00', 'accepted', 'rejected', NULL, '2026-02-28 12:00:00'),
(8, 8, 5, '2026-01-23 09:00:00', 'accepted', 'approved', NULL, '2026-01-25 10:00:00'),
(9, 10, 5, '2026-05-01 14:30:00', 'refused', 'pending', 'اعتذار لضيق الوقت وضغط العمل الحالي', NULL),
(12, 9, 5, '2026-04-20 09:00:00', 'timed_out', 'pending', NULL, NULL),
(13, 10, 5, '2026-05-03 11:00:00', 'awaiting_acceptance', 'pending', NULL, NULL),
(14, 8, 5, '2026-05-02 09:00:00', 'accepted', 'pending', NULL, NULL),
(15, 10, 5, '2026-04-22 08:30:00', 'accepted', 'approved', NULL, '2026-04-24 09:30:00');

-- 8b. Seed Review Comments (review_id maps to review insertion order above)
INSERT INTO review_comments (review_id, comment, created_at) VALUES 
(1, 'منهجية البحث ممتازة، ولا يوجد مانع أخلاقي من التطبيق.', '2026-03-10 10:00:00'),
(2, 'موافق. أهداف الدراسة واضحة وإقرار المرضى مستوفي الشروط.', '2026-03-11 14:00:00'),
(3, 'يجب توضيح كيفية حماية بيانات الأطفال المشاركين في الدراسة بدقة أكبر في نموذج الموافقة المستنيرة.', '2026-04-18 09:00:00'),
(3, 'أيضاً يُرجى مراجعة صياغة نموذج الموافقة المستنيرة ليكون أكثر وضوحاً لأولياء الأمور.', '2026-04-18 10:30:00'),
(4, 'يوجد تضارب مصالح واضح مع الشركة المصنعة للمضاد الحيوي لم يتم الإفصاح عنه بشكل كافٍ في النماذج.', '2026-02-28 12:00:00'),
(5, 'البروتوكول ممتاز ولا توجد أي ملاحظات أخلاقية.', '2026-01-25 10:00:00'),
(6, 'استمارات الموافقة المستنيرة مكتوبة بلغة بسيطة ومناسبة.', '2026-01-26 12:00:00'),
(7, 'مراجعة أولية مقبولة. البروتوكول واضح والمنهجية سليمة.', '2026-04-23 09:00:00'),
(8, 'لا يوجد موانع أخلاقية. العينة مناسبة.', '2026-04-23 10:00:00'),
(9, 'موافق. البحث مستوفٍ لجميع الشروط.', '2026-04-24 09:00:00');

-- 9. Seed Certificates
INSERT INTO certificates (application_id, student_id, manager_id, certificate_number, issued_to_name, pdf_url, issued_at) VALUES 
(1, 1, 11, 'CERT-2026-10045', 'د. عمر الفاروق', 'uploads/seed/dummy_certificate.pdf', '2026-03-15 10:00:00'),
(8, 13, 11, 'CERT-2026-10046', 'د. سلمى رضا', 'uploads/seed/dummy_certificate.pdf', '2026-02-01 10:00:00');

-- 10. Seed Logs
INSERT INTO logs (application_id, user_id, action, type, created_at) VALUES 
(1, 1, 'تم تقديم البحث بنجاح ورفع المستندات', 'submission', '2026-03-01 10:00:00'),
(1, 5, 'مراجعة أولية وتوليد رقم تسلسلي للملف', 'assignment', '2026-03-01 12:00:00'),
(1, 6, 'تم حساب حجم العينة (150)', 'status_change', '2026-03-03 09:00:00'),
(1, 11, 'اعتماد نهائي وإصدار شهادة IRB', 'certificate', '2026-03-15 10:00:00'),
(4, 5, 'تحديث حالة البحث إلى مرفوض بناءً على تقرير المراجعة', 'decision', '2026-02-28 13:00:00'),
(8, 13, 'تم تقديم البحث بنجاح', 'submission', '2026-01-15 10:30:00'),
(8, 7, 'تم حساب حجم العينة (1000)', 'status_change', '2026-01-20 09:45:00'),
(8, 11, 'اعتماد نهائي وإصدار شهادة IRB', 'certificate', '2026-02-01 10:00:00');

-- 11. Seed Notifications
INSERT INTO notifications (user_id, application_id, message, channel, is_read, email_sent, created_at) VALUES 
(2, 2, 'بحثك (IRB-2026-002) يحتاج إلى تعديلات بناءً على ملاحظات المراجعة الفنية. يرجى مراجعة التعليقات وتحديث المستندات.', 'system', 0, 1, '2026-04-18 09:05:00'),
(4, 4, 'تم رفض بحثك (IRB-2026-004). يرجى مراجعة أسباب الرفض في تفاصيل البحث.', 'system', 1, 1, '2026-02-28 12:05:00'),
(1, 1, 'تهانينا! تم اعتماد بحثك (IRB-2026-001) نهائياً وإصدار شهادة IRB.', 'system', 1, 1, '2026-03-15 10:05:00'),
(13, 8, 'تهانينا! تم اعتماد بحثك (IRB-2026-008) نهائياً وإصدار شهادة IRB.', 'system', 0, 1, '2026-02-01 10:05:00'),
(1, 5, 'بحثك (IRB-2026-005) botato chips chips botato سداد رسوم التقديم الأولية.', 'system', 0, 1, '2026-04-20 17:00:00');

-- Seed Keywords
INSERT INTO keywords (application_id, serial_number, keyword) VALUES 
-- Keywords for IRB-2026-001
(1, 'IRB-2026-001', 'السكري'),
(1, 'IRB-2026-001', 'النوع الثاني'),
(1, 'IRB-2026-001', 'أدوية حديثة'),
(1, 'IRB-2026-001', 'مرضى السكري'),
(1, 'IRB-2026-001', 'علاج السكري'),

-- Keywords for IRB-2026-002
(2, 'IRB-2026-002', 'السمنة'),
(2, 'IRB-2026-002', 'طلاب المدارس'),
(2, 'IRB-2026-002', 'الابتدائية'),
(2, 'IRB-2026-002', 'السمنة المفرطة'),
(2, 'IRB-2026-002', 'الدلتا'),

-- Keywords for IRB-2026-003
(3, 'IRB-2026-003', 'التخدير'),
(3, 'IRB-2026-003', 'تخدير موضعي'),
(3, 'IRB-2026-003', 'تخدير كلي'),
(3, 'IRB-2026-003', 'الفتق'),
(3, 'IRB-2026-003', 'جراحة بالمنظار'),

-- Keywords for IRB-2026-004
(4, 'IRB-2026-004', 'مضادات حيوية'),
(4, 'IRB-2026-004', 'الجهاز التنفسي'),
(4, 'IRB-2026-004', 'التهابات'),
(4, 'IRB-2026-004', 'واسعة المجال'),
(4, 'IRB-2026-004', 'فعالية العلاج'),

-- Keywords for IRB-2026-005
(5, 'IRB-2026-005', 'ذكاء اصطناعي'),
(5, 'IRB-2026-005', 'التشخيص المبكر'),
(5, 'IRB-2026-005', 'اعتلال الشبكية'),
(5, 'IRB-2026-005', 'الشبكية السكري'),
(5, 'IRB-2026-005', 'تكنولوجيا طبية'),

-- Keywords for IRB-2026-006
(6, 'IRB-2026-006', 'الأطفال الخدج'),
(6, 'IRB-2026-006', 'التغذية الوريدية'),
(6, 'IRB-2026-006', 'بروتوكولات حديثة'),
(6, 'IRB-2026-006', 'الاستجابة العلاجية'),
(6, 'IRB-2026-006', 'حديثي الولادة'),

-- Keywords for IRB-2026-007
(7, 'IRB-2026-007', 'علاج طبيعي'),
(7, 'IRB-2026-007', 'مفصل الركبة'),
(7, 'IRB-2026-007', 'استبدال المفصل'),
(7, 'IRB-2026-007', 'جراحة العظام'),
(7, 'IRB-2026-007', 'إعادة تأهيل'),

-- Keywords for IRB-2026-008
(8, 'IRB-2026-008', 'تسوس الأسنان'),
(8, 'IRB-2026-008', 'الأطفال'),
(8, 'IRB-2026-008', 'المناطق الريفية'),
(8, 'IRB-2026-008', 'صحة الفم'),
(8, 'IRB-2026-008', 'طب الأسنان'),

-- Keywords for IRB-2026-009
(9, 'IRB-2026-009', 'التثقيف الصحي'),
(9, 'IRB-2026-009', 'الفشل الكلوي'),
(9, 'IRB-2026-009', 'جودة الحياة'),
(9, 'IRB-2026-009', 'مرضى الكلى'),
(9, 'IRB-2026-009', 'برامج توعوية'),

-- Keywords for IRB-2026-010
(10, 'IRB-2026-010', 'المياه البيضاء'),
(10, 'IRB-2026-010', 'جراحات العيون'),
(10, 'IRB-2026-010', 'مضاعفات'),
(10, 'IRB-2026-010', 'الأمراض المزمنة'),
(10, 'IRB-2026-010', 'الكتاراكت'),

-- Keywords for IRB-2026-011
(11, 'IRB-2026-011', 'جرثومة المعدة'),
(11, 'IRB-2026-011', 'هيليكوباكتر'),
(11, 'IRB-2026-011', 'بروتوكولات علاجية'),
(11, 'IRB-2026-011', 'أمراض الجهاز الهضمي'),
(11, 'IRB-2026-011', 'علاج المعدة'),

-- Keywords for IRB-2026-012
(12, 'IRB-2026-012', 'الربو'),
(12, 'IRB-2026-012', 'الإدارة الذاتية'),
(12, 'IRB-2026-012', 'نوبات الاختناق'),
(12, 'IRB-2026-012', 'أمراض الصدر'),
(12, 'IRB-2026-012', 'التحكم بالربو'),

-- Keywords for IRB-2026-013
(13, 'IRB-2026-013', 'ضغط الدم'),
(13, 'IRB-2026-013', 'شبكية العين'),
(13, 'IRB-2026-013', 'ارتفاع الضغط'),
(13, 'IRB-2026-013', 'مضاعفات العين'),
(13, 'IRB-2026-013', 'اعتلال الشبكية'),

-- Keywords for IRB-2026-014
(14, 'IRB-2026-014', 'الكسور المضاعفة'),
(14, 'IRB-2026-014', 'التدخل الجراحي'),
(14, 'IRB-2026-014', 'جراحة مبكرة'),
(14, 'IRB-2026-014', 'جراحة العظام'),
(14, 'IRB-2026-014', 'إصابات العظام'),

-- Keywords for IRB-2026-015
(15, 'IRB-2026-015', 'السهر'),
(15, 'IRB-2026-015', 'الأداء الدراسي'),
(15, 'IRB-2026-015', 'المراهقين'),
(15, 'IRB-2026-015', 'النوم'),
(15, 'IRB-2026-015', 'الصحة النفسية');
-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(64) UNIQUE NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX(email),
    INDEX(expires_at)
);
