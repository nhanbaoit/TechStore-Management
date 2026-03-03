<?php
require_once 'config/database.php';
$db = new database();
$sql = "SELECT * FROM products ORDER BY id ASC";
$products = $db->select($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Tech Store</title>
    <!-- Favicon-->
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico" />
    <link rel="stylesheet" href="bootstrap-5.3.8/css/bootstrap.min.css">
    <!-- Core theme CSS (includes Bootstrap)-->
    <link href="css/styles.css" rel="stylesheet" />
</head>

<body>
    <?php require_once 'includes/top_bar.php'; ?>
    <?php require_once 'includes/header.php'; ?>
     <?php require_once 'includes/navbar.php'; ?>
     <?php require_once 'includes/section.php'; ?>
    <!-- Section-->
    <section class="py-4">
        <div class="container px-4 px-lg-5 mt-4">
            <div class="row gx-4 gx-lg-5 row-cols-2 row-cols-md-3 row-cols-xl-4 justify-content-center">
                <?php foreach ($products as $pr) { ?>
                    <div class="col mb-5">
                        <div class="card h-100">
                            <!-- Product image-->
                            <img class="card-img-top" src="./assets/img/<?= $pr['image']; ?>" alt="..." />
                            <!-- Product details-->
                            <div class="card-body p-4">
                                <div class="text-center">
                                    <!-- Product name-->
                                    <h5 class="fw-bolder"><?= $pr['name']; ?></h5>
                                    <!-- Product price-->
                                    <?= number_format($pr['price'], 0, ',', '.') ?> VNĐ
                                </div>
                            </div>
                            <!-- Product actions-->
                            <div class="card-footer p-4 pt-0 border-top-0 bg-transparent">
                                <div class="text-center">
                                    <a class="btn btn-outline-dark mt-auto" href="products_detail.php?id=<?= $pr['id']; ?>">View detail</a>
                                    <a class="btn btn-outline-success mt-auto" href="cart.php">Add to cart</a>
                                    
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>

            </div>
        </div>
    </section>
    <?php require_once 'includes/footer.php'; ?>
</body>

</html>