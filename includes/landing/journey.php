<section id="workflow" class="workflow fullscreen-section">
    <div>
        <h2 class="section-title">رحلة الموافقة البحثية</h2>
        <p class="section-subtitle">تتبع مسار البيانات الذكي من لحظة رفع البروتوكول وحتى الاعتماد النهائي وإصدار
            الشهادة.</p>

        <div class="timeline-container" id="timelineContainer">
            <div class="timeline-track"></div>
            <div class="timeline-progress" id="timelineProgress"></div>
            <div class="data-packet" id="dataPacket"></div>

            <div class="timeline-step" data-color="#f39c12">
                <div class="timeline-content">
                    <i class="fa-solid fa-cloud-arrow-up timeline-icon" style="color: #f39c12;"></i>
                    <span class="timeline-status status-init"><i class="fa-solid fa-spinner fa-spin"></i> جاري
                        التهيئة...</span>
                    <h4>1. التقديم والدفع</h4>
                    <p>يتم تشفير وتخزين بروتوكول البحث ووثائق الموافقة، ثم تفعيل طلب المراجعة آلياً بعد التحقق من الدفع
                        المبدئي.</p>
                </div>
            </div>

            <div class="timeline-step" data-color="#3498db">
                <div class="timeline-content">
                    <i class="fa-solid fa-calculator timeline-icon" style="color: #3498db;"></i>
                    <span class="timeline-status status-calc"><i class="fa-solid fa-gears"></i> معالجة البيانات</span>
                    <h4>2. التحليل التقني والعينة</h4>
                    <p>تنتقل حزمة البيانات لضابط العينات لحساب المتطلبات الإحصائية، وتحديث حالة الملف لربط التكلفة
                        المتغيرة بنظام الدفع.</p>
                </div>
            </div>

            <div class="timeline-step" data-color="#9b59b6">
                <div class="timeline-content">
                    <i class="fa-solid fa-user-ninja timeline-icon" style="color: #9b59b6;"></i>
                    <span class="timeline-status status-blind"><i class="fa-solid fa-eye-slash"></i> حجب الهوية</span>
                    <h4>3. المراجعة العلمية العمياء</h4>
                    <p>يتم إخفاء هوية الباحثين بالكامل وإرسال الحزمة للمراجعين لتقييم المنهجية وإصدار قرار
                        (قبول/تعديل/رفض).</p>
                </div>
            </div>

            <div class="timeline-step" data-color="#27ae60">
                <div class="timeline-content">
                    <i class="fa-solid fa-certificate timeline-icon" style="color: #27ae60;"></i>
                    <span class="timeline-status status-done"><i class="fa-solid fa-check-double"></i> مكتمل
                        وموثق</span>
                    <h4>4. الاعتماد والتشفير النهائي</h4>
                    <p>باعتماد مدير اللجنة، يُغلق مسار العمل وتُصدر شهادة IRB رقمية تحمل رمز تحقق فريد قابلة للطباعة
                        فوراً.</p>
                </div>
            </div>

        </div>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const container = document.getElementById('timelineContainer');
        const progressLine = document.getElementById('timelineProgress');
        const dataPacket = document.getElementById('dataPacket');
        const steps = document.querySelectorAll('.timeline-step');
        const contents = document.querySelectorAll('.timeline-content');

        // 1. Pop-in Animation for Cards
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, { threshold: 0.3 });

        contents.forEach(content => observer.observe(content));

        // 2. Highly Stable Scroll-Driven Progress Logic
        let ticking = false;

        const updateTimeline = () => {
            if (!container) return;

            const rect = container.getBoundingClientRect();
            const windowHeight = window.innerHeight;

            // Calculate progress exactly based on viewport center
            const startOffset = windowHeight / 2;
            let progress = (startOffset - rect.top) / rect.height;
            progress = Math.max(0, Math.min(1, progress)); // Clamp between 0 and 1

            const percentage = progress * 100;

            // Update DOM (hardware accelerated via CSS)
            progressLine.style.height = percentage + '%';
            dataPacket.style.top = percentage + '%';

            // Check active steps based on dynamic offset
            steps.forEach((step) => {
                const stepTop = step.offsetTop;
                // Add a small pixel offset so it activates right when the packet hits the dot
                const stepPercentage = ((stepTop + 15) / container.offsetHeight) * 100;

                if (percentage >= stepPercentage) {
                    step.classList.add('is-active');

                    const color = step.getAttribute('data-color');
                    dataPacket.style.borderColor = color;
                    dataPacket.style.boxShadow = `0 0 15px ${color}`;
                    progressLine.style.backgroundColor = color;
                    progressLine.style.boxShadow = `0 0 10px ${color}`;
                } else {
                    step.classList.remove('is-active');
                }
            });
        };

        // Use requestAnimationFrame to prevent scroll jank
        window.addEventListener('scroll', () => {
            if (!ticking) {
                window.requestAnimationFrame(() => {
                    updateTimeline();
                    ticking = false;
                });
                ticking = true;
            }
        });

        // Initial check on load
        updateTimeline();
    });
</script>