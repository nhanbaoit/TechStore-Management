<?php
require_once __DIR__ . '/config/database.php';
$db = new database();

// 1. BẮT DỮ LIỆU TỪ URL
$category_id = isset($_GET['category']) ? (int) $_GET['category'] : 0;
$search_keyword = isset($_GET['search']) ? trim($_GET['search']) : '';

// 2. XÂY DỰNG ĐIỀU KIỆN WHERE (CHUẨN TOÀN CỤC)
$whereClause = "";

// ƯU TIÊN 1: Nếu có gõ tìm kiếm -> Xóa bỏ ranh giới danh mục, tìm kiếm toàn server
if ($search_keyword !== '') {
    $safe_search = addslashes($search_keyword); // Chống hack SQL
    // Nhớ sửa 'name' thành cột tên sản phẩm trong DB của đại ca nhé
    $whereClause = " WHERE name LIKE '%$safe_search%'";
}
// ƯU TIÊN 2: Nếu không gõ tìm kiếm, mà có bấm vào thẻ Danh mục -> Lọc theo danh mục
elseif ($category_id > 0) {
    // Nhớ sửa 'id_category' thành tên cột chuẩn trong DB
    $whereClause = " WHERE id_category = $category_id";
}
// Nếu không có cả 2 -> $whereClause rỗng (Lấy tất cả sản phẩm)

// 3. TÍNH TOÁN PHÂN TRANG
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$PerPage = 10;
$startPage = ($page - 1) * $PerPage;

// LƯU Ý: Đại ca check lại xem bảng trong DB tên là `product` (số ít) hay `products` (số nhiều) để sửa cho đúng nha!
$sqlCount = "SELECT COUNT(id) as total FROM products" . $whereClause;
$resultCount = $db->select($sqlCount);
$totalProducts = !empty($resultCount) ? (int) $resultCount[0]['total'] : 0;
$maxPage = ceil($totalProducts / $PerPage);

// 4. LẤY DỮ LIỆU SẢN PHẨM CUỐI CÙNG
$sql = "SELECT * FROM products" . $whereClause . " ORDER BY id ASC LIMIT $startPage, $PerPage";
$paged_products = $db->select($sql);



?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta http-equiv="Expires" content="0" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Tech Store</title>
    <!-- Favicon-->
    <link rel="icon" type="image/png" href="/logo-btd.png?v=999">
    <link rel="stylesheet" href="bootstrap-5.3.8/css/bootstrap.min.css">
    <!-- Core theme CSS (includes Bootstrap)-->
    <link href="css/styles.css?v=<?php echo time(); ?>" rel="stylesheet" />
    
</head>

<body>
    <?php require_once 'includes/top_bar.php'; ?>
    <?php require_once 'includes/header.php'; ?>
    <?php require_once 'includes/navbar.php'; ?>
    <?php require_once 'includes/section.php'; ?>
    <!-- Section-->
    <section class="py-4">
        <div class="container px-4 px-lg-5 mt-4">
            <div class="row gx-3 gx-lg-4 row-cols-2 row-cols-md-3 row-cols-lg-5 justify-content-center"
                id="productContainer">
                <?php
                // Giả sử biến chứa danh sách sản phẩm là $listProducts
                if (!empty($paged_products)):
                    ?>

                    <?php foreach ($paged_products as $pr): ?>
                        <div class="col mb-5">
                            <div class="product-card h-100 d-flex flex-column"> <a
                                    href="products_detail.php?id=<?= $pr['id']; ?>" class="product-link">
                                    <div class="product-image">
                                        <img src="./assets/img/<?= $pr['image']; ?>" alt="<?= $pr['name']; ?>" />
                                    </div>

                                    <h5 class="product-name"><?= $pr['name']; ?></h5>
                                    <div class="product-price"><?= number_format($pr['price'], 0, ',', '.') ?> VNĐ</div>
                                </a>

                                <div class="mt-auto pt-3">
                                    <div class="d-grid gap-2">
                                        <button class="btn btn-primary w-100"
                                            onclick="addToCart('<?= $pr['id'] ?>', '<?= $pr['name'] ?>', <?= $pr['price'] ?>, '<?= $pr['image']; ?>')">
                                            Add to cart
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>

                    <div class="col-12 d-flex justify-content-center py-5 my-5 w-100">
                        <div class="empty-state text-center" style="max-width: 600px; width: 100%;">

                            <i class="fas fa-box-open text-muted mb-3" style="font-size: 5rem; opacity: 0.3;"></i>
                            <h3 class="text-secondary fw-bold mt-3">Ối! Không tìm thấy sản phẩm</h3>

                            <?php if (isset($_GET['search']) && trim($_GET['search']) != ''): ?>
                                <p class="text-muted fs-5 mt-3">Chúng tôi không tìm thấy
                                    "<b><?= htmlspecialchars($_GET['search']) ?></b>" trong danh mục này.</p>
                            <?php else: ?>
                                <p class="text-muted fs-5 mt-3">Hiện tại danh mục này đang trống hoặc sản phẩm đã hết hàng.</p>
                            <?php endif; ?>

                            <a href="index.php" class="btn btn-primary mt-4 px-4 py-2 rounded-pill shadow-sm">
                                <i class="fas fa-undo me-2"></i>Xem tất cả sản phẩm
                            </a>

                        </div>
                    </div>

                <?php endif; ?>

            </div>
        </div>
    </section>
    <?php require_once 'includes/pagination.php'; ?>
    <?php require_once 'includes/footer.php'; ?>
    <script src="./assets/js/main.js?v=<?php echo time(); ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Hàm thêm vào giỏ hàng chạy bằng Fetch API (hiện đại, mượt mà)
        function addToCart(id, name, price, image) {
            fetch('add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id: id,
                    name: name,
                    price: price,
                    image: image
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        // 1. Cập nhật con số trên cái cục Badge màu đỏ của giỏ hàng
                        document.getElementById('cart-count').innerText = data.totalItems;

                        // 2. Bắn pháo hoa thông báo (SweetAlert2 Toast)
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: 'Đã thêm ' + name + ' vào giỏ!',
                            showConfirmButton: false,
                            timer: 2000,
                            timerProgressBar: true,
                            background: '#fff',
                            iconColor: '#3c5fb6' // Màu xanh chủ đạo
                        });
                    }
                })
                .catch(error => {
                    console.error('Lỗi rồi :', error);
                });
        }
    </script>
    </script> <?php if (isset($_SESSION["order_success"])): ?>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Đặt hàng thành công!',
                    text: '<?= $_SESSION["order_success"] ?>',
                    confirmButtonColor: '#3c5fb6', /* Màu xanh chủ đạo TechStore */
                    borderRadius: '16px',
                    backdrop: `rgba(0,0,0,0.4)`
                });
            });
        </script>
        <?php unset($_SESSION["order_success"]); // Bắn xong phải xóa để F5 không bị bắn lại ?>
    <?php endif; ?>
</body>

</html>