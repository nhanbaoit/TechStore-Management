<?php
session_start();

// Nếu chưa có session user, đá văng ra trang chủ hoặc trang đăng ký
if (!isset($_SESSION['user'])) {
    header('Location: register.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Giỏ hàng - TechStore</title>
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico" />
    <link rel="stylesheet" href="bootstrap-5.3.8/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="assets/css/style.css?v=20260417" rel="stylesheet" />
</head>
<body>

    <?php require_once 'includes/header.php'; ?>

    <div class="container py-5">
        <?php
        // Lấy giỏ hàng ra, nếu không có thì gán mảng rỗng
        $cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
        $totalPrice = 0;
        $totalItems = 0;

        // Tính tổng số lượng và tổng tiền
        foreach ($cart as $item) {
            $totalItems += $item['quantity'];
            $totalPrice += ($item['price'] * $item['quantity']);
        }
        ?>

        <div class="d-flex justify-content-between align-items-end mb-4">
            <h3 class="fw-bold mb-0" style="font-family: var(--font-display);">Giỏ hàng của bạn</h3>
            <span class="text-muted fw-medium pb-1" id="cart-total-items"><?= $totalItems ?> sản phẩm</span>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                    <div class="card-body p-4">

                        <?php if (empty($cart)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-cart-x text-muted" style="font-size: 4rem;"></i>
                                <h5 class="mt-3 fw-bold">Giỏ hàng đang trống!</h5>
                                <p class="text-muted">Bạn chưa chọn món đồ công nghệ nào cả.</p>
                                <a href="index.php" class="btn cart-btn-checkout mt-3 px-4">Mua sắm ngay</a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($cart as $id => $item): ?>
                                <!-- Đã canh chỉnh lại lưới (grid) để nhét thêm cột Checkbox -->
                                <div class="row align-items-center mb-4 pb-4 border-bottom" id="cart-item-<?= $id ?>">
                                    
                                    <!-- Cột 1: Nút tick chọn (Checkbox) -->
                                    <div class="col-1 col-md-1 d-flex justify-content-center">
                                        <input class="form-check-input border-secondary shadow-none" type="checkbox" 
                                               id="select-<?= $id ?>" value="<?= $id ?>" 
                                               style="width: 1.3rem; height: 1.3rem; cursor: pointer;" checked>
                                    </div>

                                    <div class="col-3 col-md-2">
                                        <img src="./assets/img/<?= $item['image']; ?>" alt="Product" class="cart-item-img" style="width: 100%; border-radius: 12px;">
                                    </div>
                                    
                                    <div class="col-8 col-md-3 mb-3 mb-md-0">
                                        <h6 class="fw-bold mb-1" style="font-size: 15px;"><?= htmlspecialchars($item['name']) ?></h6>
                                        <span class="badge bg-light text-secondary border">TechStore</span>
                                    </div>
                                    
                                    <div class="col-12 col-md-3 d-flex justify-content-md-center mb-3 mb-md-0">
                                        <div class="qty-wrapper d-flex align-items-center border rounded-pill p-1">
                                            <button class="btn btn-sm border-0" onclick="updateQuantity('<?= $id ?>', 'decrease')">
                                                <i class="bi bi-dash"></i>
                                            </button>
                                            <input type="number" class="form-control form-control-sm text-center border-0 bg-transparent" 
                                                   id="qty-input-<?= $id ?>" value="<?= $item['quantity'] ?>" 
                                                   style="width: 45px; font-weight: bold;" readonly>
                                            <button class="btn btn-sm border-0" onclick="updateQuantity('<?= $id ?>', 'increase')">
                                                <i class="bi bi-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="col-8 col-md-2 text-md-end">
                                        <span class="fw-bold fs-5 text-primary text-nowrap" id="item-total-price-<?= $id ?>">
                                            <?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?> ₫
                                        </span>
                                    </div>
                                    
                                    <div class="col-4 col-md-1 text-end">
                                        <button class="btn btn-link text-danger p-0 opacity-75" title="Xóa"
                                                onclick="removeFromCart('<?= $id ?>')">
                                            <i class="bi bi-trash3-fill fs-5"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                    </div>
                </div>

                <div class="mt-4 ms-2">
                    <a href="index.php" class="text-decoration-none fw-bold" style="color: var(--secondary);">
                        <i class="bi bi-arrow-left me-2"></i>Tiếp tục mua sắm
                    </a>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm position-sticky" style="top: 20px; border-radius: 16px;">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-4" style="font-family: var(--font-display);">Tổng thanh toán</h5>

                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-muted">Tạm tính</span>
                            <span class="fw-semibold text-nowrap" id="sub-total"><?= number_format($totalPrice, 0, ',', '.') ?> ₫</span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-muted">Phí vận chuyển</span>
                            <span class="fw-bold text-success">Miễn phí</span>
                        </div>

                        <hr class="my-4" style="border-color: #e4e7e9;">

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <span class="text-muted fw-medium">Tổng cộng</span>
                            <span class="fw-bold fs-3 text-primary text-nowrap" id="total-price"><?= number_format($totalPrice, 0, ',', '.') ?> ₫</span>
                        </div>

                        <button class="cart-btn-checkout btn btn-primary w-100 py-3 rounded-pill d-flex justify-content-center align-items-center"
                                onclick="location.href='checkout.php'" <?= empty($cart) ? 'disabled' : '' ?>>
                            Tiến hành thanh toán <i class="bi bi-arrow-right-circle-fill ms-2 fs-5"></i>
                        </button>

                        <div class="text-center mt-4">
                            <p class="text-muted mb-0" style="font-size: 13px;">
                                <i class="bi bi-shield-lock-fill text-success me-1"></i> Thông tin được bảo mật tuyệt đối
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="bootstrap-5.3.8/js/bootstrap.bundle.min.js"></script>
    <script>
        // Hàm cập nhật số lượng (Tăng/Giảm)
        function updateQuantity(id, action) {
            let input = document.getElementById('qty-input-' + id);
            let currentQty = parseInt(input.value);

            // Nếu đang là 1 mà bấm giảm -> Hỏi xóa (Popup giữa màn hình)
            if (action === 'decrease' && currentQty === 1) {
                removeFromCart(id); // Gọi luôn hàm removeFromCart để tái sử dụng popup
                return;
            }

            let newQty = action === 'increase' ? currentQty + 1 : currentQty - 1;

            fetch('update_cart_qty.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id, quantity: newQty })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    input.value = newQty; 
                    document.getElementById('item-total-price-' + id).innerText = data.itemTotalFormatted + ' ₫'; 
                    updateSummary(data); 
                }
            });
        }

        // Hàm cập nhật các con số tổng quát trên trang
        function updateSummary(data) {
            document.getElementById('cart-total-items').innerText = data.totalItems + ' sản phẩm';
            document.getElementById('sub-total').innerText = data.totalPriceFormatted + ' ₫';
            document.getElementById('total-price').innerText = data.totalPriceFormatted + ' ₫';
            
            let cartCountIcon = document.getElementById('cart-count');
            if (cartCountIcon) cartCountIcon.innerText = data.totalItems;
        }

        // ĐÃ CHỈNH SỬA: Hàm xóa sản phẩm có popup cảnh báo ở giữa màn hình
        function removeFromCart(id) {
            Swal.fire({
                title: 'Xóa sản phẩm?',
                text: "Bạn có chắc chắn muốn bỏ sản phẩm này khỏi giỏ hàng?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444', // Màu đỏ cảnh báo
                cancelButtonColor: '#6c757d',  // Màu xám nút hủy
                confirmButtonText: 'Có, xóa ngay!',
                cancelButtonText: 'Giữ lại',
                position: 'center', // Đảm bảo luôn nằm giữa màn hình
                borderRadius: '16px'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Nếu user bấm "Có", tiến hành gọi Ajax để xóa
                    fetch('remove_from_cart.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: id })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            if (data.isEmpty) {
                                window.location.reload();
                            } else {
                                // Xóa thẻ HTML của sản phẩm
                                document.getElementById('cart-item-' + id).remove();
                                updateSummary(data);
                                
                                // Hiện thông báo thành công ở giữa màn hình
                                Swal.fire({
                                    title: 'Thành công!',
                                    text: 'Đã xóa sản phẩm khỏi giỏ hàng.',
                                    icon: 'success',
                                    position: 'center', // Nằm giữa màn hình thay vì trên góc
                                    showConfirmButton: false,
                                    timer: 1500,
                                    borderRadius: '16px'
                                });
                            }
                        }
                    });
                }
            });
        }
    </script>

    <?php require_once 'includes/footer.php'; ?>
</body>
</html>