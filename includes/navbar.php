<?php
// XÓA DÒNG REQUIRE_ONCE ĐI RỒI NHÉ!
// Dùng luôn biến $db từ file index.php truyền sang
$sql_cat = "SELECT * FROM categories";
$categories = $db->select($sql_cat);

$icon_map = [
    'Điện thoại & Tablet' => 'bi-phone',
    'Laptop & Computer'   => 'bi-laptop',
    'Thiết bị âm thanh'   => 'bi-headphones',
    'Phụ kiện'            => 'bi-mouse',
    'Thiết bị gia đình'   => 'bi-house-gear'
];
?>

<nav class="nav-bar">
    <div class="container nav-content">
        <ul class="nav-menu">
            <li>
                <a href="index.php">
                    <i class="bi bi-grid-fill"></i> All Products
                </a>
            </li>
            
            <?php 
            // Kiểm tra xem $categories có dữ liệu không rồi mới lặp để tránh lỗi
            if (!empty($categories)):
                foreach ($categories as $cat) :
                    $icon_class = isset($icon_map[$cat['name']]) ? $icon_map[$cat['name']] : 'bi-box';
            ?>
                    <li>
                        <a href="index.php?category=<?= $cat['id']; ?>">
                            <i class="bi <?= $icon_class; ?>"></i>
                            <?= $cat['name']; ?>
                        </a>
                    </li>
            <?php 
                endforeach; 
            endif; 
            ?>
        </ul>
    </div>
</nav>