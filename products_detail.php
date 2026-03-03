<?php
require_once 'config/database.php';
$db = new database();

// 1. Lấy ID từ URL xuống
$id = isset($_GET['id']) ? $_GET['id'] : 0;

// 2. Viết câu lệnh SQL lấy đúng sản phẩm có ID này
$sql = "SELECT * FROM products WHERE id = $id";
$result = $db->select($sql);

// 3. Kiểm tra xem có sản phẩm không, nếu có thì lấy phần tử đầu tiên
$product = !empty($result) ? $result[0] : null;

// Nếu không tìm thấy sản phẩm, có thể chuyển hướng về trang chủ
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
    <title>TechStore - Modern E-commerce</title>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;700;800&family=Public+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" />

    <link rel="stylesheet" href="bootstrap-5.3.8/css/bootstrap.min.css">

    <link href="assets/css/style.css" rel="stylesheet" />
</head>

<body>
    <?php require_once 'includes/top_bar.php'; ?>
    <?php require_once 'includes/header.php'; ?>
    <?php require_once 'includes/navbar.php'; ?>
    <section class="py-4">
    <div class="container px-4 px-lg-5 mt-4">
        <div class="row gx-4 gx-lg-5 align-items-center">
            <div class="col-md-6">
                <img class="card-img-top mb-5 mb-md-0" src="./assets/img/<?= $product['image']; ?>" alt="..." />
            </div>
            
            <div class="col-md-6">
                <h1 class="display-5 fw-bolder"><?= $product['name']; ?></h1>
                <div class="fs-5 mb-5">
                    <span><?= number_format($product['price'], 0, ',', '.') ?> VNĐ</span>
                </div>
                <p class="lead"><?= $product['description']; ?></p>
                
                <div class="d-flex">
                    <input class="form-control text-center me-3" id="inputQuantity" type="number" value="1" style="max-width: 3rem" />
                    <button class="btn btn-outline-dark flex-shrink-0" type="button">
                        <i class="bi-cart-fill me-1"></i>
                        Thêm vào giỏ hàng
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>

    <?php require_once 'includes/footer.php'; ?>

</body>

</html>