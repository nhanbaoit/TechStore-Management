<?php
require_once 'config/database.php'; // Sửa lại đường dẫn cho gọn

$db = new Database(); // Đã sửa lại chữ D viết hoa cho đúng tên Class

// 1. Lấy ID từ URL xuống (Ép kiểu INT để chống hack SQL Injection)
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    header("Location: index.php");
    exit();
}

// 2. Viết câu lệnh SQL lấy đúng sản phẩm có ID này
$sql = "SELECT * FROM products WHERE id = $id";
$result = $db->select($sql);

// Lấy sản phẩm đầu tiên trong mảng
$product = (!empty($result)) ? $result[0] : null;

// Nếu không tìm thấy sản phẩm thì đá về trang chủ
if (!$product) {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title><?= htmlspecialchars($product['name']); ?> - TechStore</title>

    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;700;800&family=Public+Sans:wght@400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="bootstrap-5.3.8/css/bootstrap.min.css">
    <link href="assets/css/style.css" rel="stylesheet" />

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <?php require_once 'includes/top_bar.php'; ?>
    <?php require_once 'includes/header.php'; ?>
    <?php require_once 'includes/navbar.php'; ?>

    <section class="py-4">
        <div class="container px-4 px-lg-5 mt-4">
            <div class="row gx-4 gx-lg-5 align-items-center">
                <div class="col-md-6 text-center">
                    <img class="card-img-top mb-5 mb-md-0 rounded border shadow-sm"
                        src="./assets/img/<?= htmlspecialchars($product['image']); ?>"
                        alt="<?= htmlspecialchars($product['name']); ?>"
                        style="max-height: 400px; object-fit: contain; padding: 20px; background: #fff;"
                        onerror="this.onerror=null; this.src='https://placehold.co/600x400/eeeeee/999999?text=No+Image';" />
                </div>

                <div class="col-md-6">
                    <h1 class="display-5 fw-bolder text-dark mb-3"><?= htmlspecialchars($product['name']); ?></h1>

                    <div class="fs-4 mb-4 fw-bold text-primary">
                        <span><?= number_format($product['price'], 0, ',', '.') ?> VNĐ</span>
                    </div>

                    <p class="lead text-secondary mb-4" style="line-height: 1.8;">
                        <?= nl2br(htmlspecialchars($product['description'])); ?>
                    </p>

                    <div class="d-flex gap-3 align-items-center">
                        <input class="form-control text-center fw-bold" id="inputQuantity" type="number" value="1"
                            min="1" style="max-width: 5rem; height: 46px;" />

                        <button class="btn btn-outline-dark flex-shrink-0 px-4" style="height: 46px;" type="button"
                            onclick="handleCartAction(<?= $product['id'] ?>, '<?= addslashes($product['name']) ?>', <?= $product['price'] ?>, '<?= htmlspecialchars($product['image']) ?>', 'cart')">
                            <i class="bi-cart-plus me-2"></i> Add to cart
                        </button>

                        <button class="btn btn-primary flex-shrink-0 px-4" style="height: 46px;" type="button"
                            onclick="handleCartAction(<?= $product['id'] ?>, '<?= addslashes($product['name']) ?>', <?= $product['price'] ?>, '<?= htmlspecialchars($product['image']) ?>', 'buy')">
                            <i class="bi-bag-check-fill me-2"></i> Buy Now
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="row gx-4 gx-lg-5 mt-5">
            <div class="col-md-12">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4 p-md-5">
                        <h3 class="fw-bold mb-4 border-bottom pb-3 text-primary"><i
                                class="bi bi-gear-fill me-2"></i>Thông số kỹ thuật</h3>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <tbody>
                                    <?php
                                    $specs = json_decode($product['specifications'] ?? '{}', true);

                                    if (!empty($specs) && is_array($specs)) {
                                        foreach ($specs as $key => $value) {
                                            echo "<tr>";
                                            echo "<td class='fw-semibold text-secondary py-3' style='width: 30%'>" . htmlspecialchars($key) . "</td>";
                                            echo "<td class='text-dark py-3'>" . htmlspecialchars($value) . "</td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        // Nếu data không phải dạng JSON (mà là text bình thường đại ca nhập trong form Edit)
                                        // Thì in ra dạng văn bản bình thường luôn
                                        if (!empty(trim($product['specifications']))) {
                                            echo "<tr><td class='text-dark py-3' style='white-space: pre-line'>" . htmlspecialchars($product['specifications']) . "</td></tr>";
                                        } else {
                                            echo "<tr><td class='text-center py-4 text-muted'>Thông số đang được cập nhật...</td></tr>";
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php require_once 'includes/pagination.php'; ?>
    <?php require_once 'includes/footer.php'; ?>

    <script>
        function handleCartAction(id, name, price, image, actionType) {
            // Lấy số lượng khách vừa nhập
            let qty = parseInt(document.getElementById('inputQuantity').value) || 1;

            // Bắn AJAX qua file add_to_cart.php để lưu vào Session
            fetch('add_to_cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id: id,
                    name: name,
                    price: price,
                    image: image,
                    quantity: qty
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        // KIỂM TRA KHÁCH BẤM NÚT NÀO ĐỂ CHUYỂN TRANG
                        if (actionType === 'cart') {
                            // Bấm Add to Cart -> Bay vô cart.php
                            window.location.href = 'cart.php';
                        } else if (actionType === 'buy') {
                            // Bấm Buy Now -> Bay thẳng ra checkout.php
                            window.location.href = 'checkout.php';
                        }
                    } else {
                        Swal.fire('Lỗi', 'Không thể thao tác giỏ hàng lúc này!', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Lỗi', 'Có lỗi kết nối máy chủ!', 'error');
                });
        }
    </script>
</body>

</html>