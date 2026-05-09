<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = isset($_SESSION['user']) ? 'true' : 'false';

// Tính tổng số lượng sản phẩm trong giỏ hàng để hiển thị trên Header
$totalCartItems_Header = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $totalCartItems_Header += isset($item['quantity']) ? $item['quantity'] : 0;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>TechStore - Modern E-commerce</title>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;700;800&family=Public+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="bootstrap-5.3.8/css/bootstrap.min.css">
    <link href="assets/css/style.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <!-- Main Header -->
    <header class="main-header">
        <div class="container header-content">
            <a href="index.php" class="logo">TECH<span>STORE</span></a>

            <!-- Thêm position: relative vào thẻ bao bọc ngoài cùng -->
            <div class="search-box" style="position: relative;">
                <!-- Form tìm kiếm nằm riêng -->
                <form action="index.php" method="GET" id="searchForm" style="display: flex;">
                    <?php if (isset($_GET['category'])): ?>
                        <input type="hidden" name="category" value="<?= (int) $_GET['category'] ?>">
                    <?php endif; ?>

                    <input type="text" id="searchInput" name="search" autocomplete="off"
                        placeholder="Searching for technology products..."
                        value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>"
                        style="flex-grow: 1;"> <!-- Thêm flex-grow để ô input chiếm hết chỗ trống -->

                    <button type="submit"><i class="bi bi-search"></i></button>
                </form>

                <!-- Bảng Dropdown Lịch Sử (NẰM BÊN NGOÀI THẺ <form>) -->
                <div id="searchDropdown" class="search-dropdown d-none"
                    style="position: absolute; top: 100%; left: 0; right: 0; background-color: #fff; border: 1px solid #ccc; z-index: 9999; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    <div class="px-3 py-2 text-muted small fw-bold"
                        style="background-color: #f8f9fa; border-bottom: 1px solid #eee;">Recent Searches</div>
                    <ul id="historyList" class="list-unstyled mb-0">
                        <!-- JavaScript sẽ tự động nhét lịch sử vào đây -->
                    </ul>
                </div>
            </div>

            <div class="header-icons d-flex align-items-center">

                <?php if (isset($_SESSION['user'])) { ?>
                    <?php
                    $avatar = $_SESSION['user']['avatar'] ?? '';
                    if (empty($avatar)) {
                        $avatar = 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION['user']['username']) . '&background=random';
                    }
                    ?>
                    <button class="icon-btn border-0 bg-transparent p-0 me-3" onclick="viewMyProfile()"
                        title="Xem hồ sơ của tôi">
                        <img src="<?= $avatar ?>" class="rounded-circle border border-2 border-primary" width="40"
                            height="40" style="object-fit: cover; cursor: pointer;">
                    </button>
                <?php } else { ?>
                    <button class="icon-btn border-0 bg-transparent p-0 me-3" onclick="location.href='register.php'"
                        title="Đăng nhập / Đăng ký">
                        <i class="bi bi-person fs-4"></i>
                    </button>
                <?php } ?>

                <button class="cart-btn border-0 bg-transparent p-0 me-3" onclick="handleCartClick(<?= $isLoggedIn ?>)"
                    title="Giỏ hàng">
                    <div class="icon-btn position-relative">
                        <i class="bi bi-cart3 fs-4"></i>
                        <!-- ĐÃ SỬA CHỖ NÀY: Thay vì số 0, giờ sẽ in ra biến PHP -->
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                            id="cart-count" style="font-size: 0.65rem;">
                            <?= $totalCartItems_Header ?>
                        </span>
                    </div>
                </button>

                <?php if (isset($_SESSION['user'])) { ?>
                    <div class="d-flex align-items-center">
                        <a href="admin/logout.php" class="btn btn-outline-danger btn-sm rounded-pill px-3">
                            <i class="fas fa-sign-out-alt me-1"></i> Đăng xuất
                        </a>
                    </div>
                <?php } ?>

            </div>
        </div>
        
        <div class="modal fade" id="myProfileModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title fw-bold"><i class="fas fa-id-badge me-2"></i>Hồ sơ của tôi</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>

                    <div class="modal-body p-4" id="myProfileBody">
                        <div class="text-center my-4">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="mt-2 text-muted">Đang tải thông tin của bạn...</p>
                        </div>
                    </div>

                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <script src="/bootstrap-5.3.8/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const searchInput = document.getElementById('searchInput');
            const searchForm = document.getElementById('searchForm');
            const searchDropdown = document.getElementById('searchDropdown');
            const historyList = document.getElementById('historyList');

            let searchHistory = JSON.parse(localStorage.getItem('techstore_history')) || [];

            function renderHistory() {
                historyList.innerHTML = '';
                if (searchHistory.length === 0) {
                    historyList.innerHTML = '<li class="px-3 py-2 text-muted small">No recent searches</li>';
                    return;
                }
                searchHistory.forEach(item => {
                    let li = document.createElement('li');
                    li.innerHTML = `<a href="index.php?search=${encodeURIComponent(item)}" class="dropdown-item py-2 px-3 text-dark">
    <i class="bi bi-clock-history text-secondary me-2"></i> ${item}
</a>`;
                    historyList.appendChild(li);
                });
            }

            searchInput.addEventListener('focus', function () {
                renderHistory();
                searchDropdown.classList.remove('d-none');
            });

            document.addEventListener('click', function (e) {
                if (!searchInput.contains(e.target) && !searchDropdown.contains(e.target)) {
                    searchDropdown.classList.add('d-none');
                }
            });

            searchForm.addEventListener('submit', function (e) {
                const val = searchInput.value.trim();
                if (val) {
                    searchHistory = searchHistory.filter(item => item !== val);
                    searchHistory.unshift(val); 
                    if (searchHistory.length > 5) searchHistory.pop(); 

                    localStorage.setItem('techstore_history', JSON.stringify(searchHistory));
                }
            });
        });
    </script>
    <script>
        function viewMyProfile() {
            let profileModal = new bootstrap.Modal(document.getElementById('myProfileModal'));
            profileModal.show();

            document.getElementById('myProfileBody').innerHTML = `
        <div class="text-center my-4">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2 text-muted">Đang tải thông tin của bạn...</p>
        </div>
    `;

            fetch('includes/get_my_profile.php')
                .then(response => response.text())
                .then(htmlData => {
                    document.getElementById('myProfileBody').innerHTML = htmlData;
                })
                .catch(error => {
                    document.getElementById('myProfileBody').innerHTML = '<p class="text-danger text-center"><i class="fas fa-exclamation-triangle me-2"></i>Lỗi kết nối máy chủ!</p>';
                });
        }
    </script>
    <script>
        function handleCartClick(isLoggedIn) {
            if (isLoggedIn) {
                window.location.href = 'cart.php';
            } else {
                Swal.fire({
                    title: 'Ối! Bạn chưa đăng nhập',
                    text: "Đăng nhập để xem giỏ hàng và chốt đơn ngay nhé!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3c5fb6',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Đăng nhập ngay',
                    cancelButtonText: 'Để sau',
                    background: '#fff',
                    borderRadius: '16px'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'register.php';
                    }
                });
            }
        }
    </script>
</body>
</html>