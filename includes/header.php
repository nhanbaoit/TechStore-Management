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
    <!-- Main Header -->
    <header class="main-header">
        <div class="container header-content">
            <a href="index.php" class="logo">TECH<span>STORE</span></a>

            <div class="search-box">
                <form action="index.php" method="GET">
                    <input type="text" name="search" placeholder="Searching for technology products..." value="<?= isset($_GET['search']) ? $_GET['search'] : '' ?>">
                    <button type="submit"><i class="bi bi-search"></i></button>
                </form>
            </div>

            <div class="header-icons">
                <button class="icon-btn">
                    <i class="bi bi-person"></i>
                </button>
                <button class="cart-btn" onclick="location.href='cart.php'">
                    <div class="icon-btn">
                        <i class="bi bi-cart3"></i>
                        <span class="badge">0</span>
                    </div>
                    <div class="cart-info">
                        <span class="cart-label">Cart</span>
                        <span class="cart-price">0 VNĐ</span>
                    </div>
                </button>
            </div>
        </div>
    </header>

</body>

</html>