document.addEventListener("DOMContentLoaded", function () {
    
    // ==========================================
    // 1. PHẦN LOGIN / REGISTER TOGGLE
    // ==========================================
    const container = document.querySelector('.container');
    
    // Chỉ chạy đoạn code đăng nhập nếu tìm thấy .container chứa form
    if (container && document.getElementById('toggleRegister')) {
        const registerBtn = document.getElementById('toggleRegister');
        const loginBtn = document.getElementById('toggleLogin');
        const mobileRegisterLink = document.querySelector('.register-link');
        const mobileLoginLink = document.querySelector('.login-link');

        if (mobileRegisterLink) {
            mobileRegisterLink.addEventListener('click', (e) => {
                e.preventDefault();
                container.classList.add('active');
            });
        }

        if (mobileLoginLink) {
            mobileLoginLink.addEventListener('click', (e) => {
                e.preventDefault();
                container.classList.remove('active');
            });
        }

        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get("form") === "register") {
            container.classList.add("active");
        }

        // Đã bọc thêm if để tránh lỗi văng JS
        if (registerBtn) {
            registerBtn.addEventListener('click', () => {
                container.classList.add('active');
            });
        }

        if (loginBtn) {
            loginBtn.addEventListener('click', () => {
                container.classList.remove('active');
            });
        }
    }


    // ==========================================
    // 2. PHẦN SLIDER HERO
    // ==========================================
    const slides = document.querySelectorAll('.slide');
    
    // Chỉ chạy code Slider nếu trang hiện tại có class .slide (Trang chủ)
    if (slides.length > 0) {
        const curNum = document.getElementById('curNum');
        const progress = document.getElementById('progress');
        const btnNext = document.getElementById('btnNext');
        const btnPrev = document.getElementById('btnPrev');
        
        const AUTO_MS = 5000;
        let current = 0;
        let timer = null;
        let elapsed = 0;
        let raf = null;
        let start = null;

        function pad(n) { return String(n + 1).padStart(2, '0'); }

        function goTo(idx) {
            slides[current].classList.remove('active');
            current = (idx + slides.length) % slides.length;
            slides[current].classList.add('active');
            if (curNum) curNum.textContent = pad(current);
            resetTimer();
        }

        function resetTimer() {
            if (raf) cancelAnimationFrame(raf);
            start = null;
            elapsed = 0;
            if (progress) progress.style.width = '0%';
            raf = requestAnimationFrame(tick);
        }

        function tick(ts) {
            if (!start) start = ts;
            elapsed = ts - start;
            let pct = Math.min(elapsed / AUTO_MS * 100, 100);
            if (progress) progress.style.width = pct + '%';
            if (elapsed >= AUTO_MS) {
                goTo(current + 1);
                return;
            }
            raf = requestAnimationFrame(tick);
        }

        // Đã bọc thêm kiểm tra tồn tại nút trước khi gắn sự kiện
        if (btnNext) {
            btnNext.addEventListener('click', () => goTo(current + 1));
        }
        
        if (btnPrev) {
            btnPrev.addEventListener('click', () => goTo(current - 1));
        }

        // Bàn phím điều hướng
        document.addEventListener('keydown', e => {
            if (e.key === 'ArrowRight') goTo(current + 1);
            if (e.key === 'ArrowLeft') goTo(current - 1);
        });

        resetTimer();
    }
});
 // ==========================================
    // 3. Tính phí ship
    // ==========================================
const host = "https://provinces.open-api.vn/api/";

// 1. Gọi API lấy danh sách Tỉnh
fetch(host + "?depth=1")
    .then(res => res.json())
    .then(data => {
        data.forEach(item => {
            document.getElementById("province").innerHTML += `<option value="${item.code}">${item.name}</option>`;
        });
    });

// 2. Khi chọn Tỉnh -> Lấy Huyện
document.getElementById("province").addEventListener("change", function() {
    let provinceCode = this.value;
    let districtSelect = document.getElementById("district");
    districtSelect.innerHTML = '<option value="">Chọn Quận/Huyện</option>';
    districtSelect.disabled = !provinceCode;
    
    if(provinceCode) {
        fetch(host + "p/" + provinceCode + "?depth=2")
            .then(res => res.json())
            .then(data => {
                data.districts.forEach(item => {
                    districtSelect.innerHTML += `<option value="${item.code}">${item.name}</option>`;
                });
            });
        
        // --- LOGIC TÍNH PHÍ SHIP TẠM TÍNH ---
        // Ví dụ: TP.HCM có code là 79
        let shipFee = (provinceCode == "79") ? 15000 : 35000;
        updateShippingFee(shipFee);
    }
});

// 3. Khi chọn Huyện -> Lấy Xã
document.getElementById("district").addEventListener("change", function() {
    let districtCode = this.value;
    let wardSelect = document.getElementById("ward");
    wardSelect.innerHTML = '<option value="">Chọn Phường/Xã</option>';
    wardSelect.disabled = !districtCode;

    if(districtCode) {
        fetch(host + "d/" + districtCode + "?depth=2")
            .then(res => res.json())
            .then(data => {
                data.wards.forEach(item => {
                    wardSelect.innerHTML += `<option value="${item.code}">${item.name}</option>`;
                });
            });
    }
});

function updateShippingFee(fee) {
    const subtotal = <?= $totalPrice ?>;
    document.querySelector(".text-success.fw-bold").innerText = fee.toLocaleString() + " ₫";
    document.querySelector(".text-primary.fs-3").innerText = (subtotal + fee).toLocaleString() + " ₫";
}

