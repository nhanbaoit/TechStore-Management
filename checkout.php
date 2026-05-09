<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
include_once "config/database.php";

// 1. Chặn cửa
if (!isset($_SESSION['user'])) {
    header('Location: register.php');
    exit();
}
$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
if (empty($cart)) {
    header('Location: cart.php');
    exit();
}

$db = new database();
$user = $_SESSION['user'];

// Tính tổng tiền giỏ hàng (Chưa ship)
$totalPrice = 0;
foreach ($cart as $item) {
    $totalPrice += ($item['price'] * $item['quantity']);
}

// ======================================================
// XỬ LÝ LƯU DATABASE KHI BẤM "XÁC NHẬN ĐẶT HÀNG"
// ======================================================
if (isset($_POST['btn_DatHang'])) {
    $fullname = addslashes($_POST['fullname']);
    $email = addslashes($_POST['email']);
    $phone = addslashes($_POST['phone']);
    $note = addslashes($_POST['note']);
    $payment_method = $_POST['payment_method'];
    $user_id = $user['id'];

    // 1. GỘP CHUỖI ĐỊA CHỈ TỪ CÁC Ô NHẬP LIỆU
    $address_detail = addslashes(trim($_POST['address_detail']));
    $ward = addslashes($_POST['ward_text']);
    $district = addslashes($_POST['district_text']);
    $province = addslashes($_POST['province_text']);
    $full_address = "$address_detail, $ward, $district, $province";

    // 2. TÍNH TIỀN SHIP BÊN PHÍA SERVER CHO AN TOÀN (Chống hack F12)
    $province_code = $_POST['province'];
    $ship_fee = ($province_code == "79") ? 15000 : 35000;
    $final_total = $totalPrice + $ship_fee;

    try {
        // BƯỚC 1: Lưu vào bảng orders (Gắn thêm trạng thái Chờ xác nhận)
        $sql_order = "INSERT INTO orders (user_id, full_name, email, phone_number, address, note, total_money, payment_method, status) 
                      VALUES ('$user_id', '$fullname', '$email', '$phone', '$full_address', '$note', '$final_total', '$payment_method', 'Chờ xác nhận')";
        $db->execute($sql_order);

        // BƯỚC 2: Lấy mã Đơn hàng (order_id) vừa tạo ra
        $sql_get_id = "SELECT MAX(id) as last_id FROM orders WHERE user_id = '$user_id'";
        $result_id = $db->select($sql_get_id);
        $order_id = $result_id[0]['last_id'];

        // BƯỚC 3: Lưu từng món hàng vào bảng order_details (Đã bỏ cột total_money thừa)
        foreach ($cart as $product_id => $item) {
            $price = $item['price'];
            $quantity = $item['quantity'];

            $sql_detail = "INSERT INTO order_details (order_id, product_id, price, quantity) 
                           VALUES ('$order_id', '$product_id', '$price', '$quantity')";
            $db->execute($sql_detail);
        }

        // BƯỚC 4: KIỂM TRA PHƯƠNG THỨC THANH TOÁN
        if ($payment_method == 'VNPAY') {
            require_once 'vnpay_config.php';
            
            // 🚨 SỬA LỖI Ở ĐÂY: Ép server xài múi giờ Việt Nam
            date_default_timezone_set('Asia/Ho_Chi_Minh');
            
            $vnp_TxnRef = $order_id;
            $vnp_OrderInfo = "Thanh toan don hang ORD-" . $order_id;
            $vnp_OrderType = "billpayment";
            $vnp_Amount = $final_total * 100;
            $vnp_Locale = 'vn';
            $vnp_IpAddr = $_SERVER['REMOTE_ADDR'];
            
            // Tạo thời gian bắt đầu và thời gian hết hạn (cho khách 15 phút để quẹt thẻ)
            $startTime = date("YmdHis");
            $expire = date('YmdHis', strtotime('+15 minutes', strtotime($startTime)));

            $inputData = array(
                "vnp_Version" => "2.1.0",
                "vnp_TmnCode" => $vnp_TmnCode,
                "vnp_Amount" => $vnp_Amount,
                "vnp_Command" => "pay",
                "vnp_CreateDate" => $startTime,
                "vnp_CurrCode" => "VND",
                "vnp_IpAddr" => $vnp_IpAddr,
                "vnp_Locale" => $vnp_Locale,
                "vnp_OrderInfo" => $vnp_OrderInfo,
                "vnp_OrderType" => $vnp_OrderType,
                "vnp_ReturnUrl" => $vnp_Returnurl,
                "vnp_TxnRef" => $vnp_TxnRef,
                "vnp_ExpireDate" => $expire // Truyền thêm cái này VNPay mới chịu
            );

            ksort($inputData);
            $query = "";
            $i = 0;
            $hashdata = "";
            foreach ($inputData as $key => $value) {
                if ($i == 1) {
                    $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
                } else {
                    $hashdata .= urlencode($key) . "=" . urlencode($value);
                    $i = 1;
                }
                $query .= urlencode($key) . "=" . urlencode($value) . '&';
            }

            $vnp_Url = $vnp_Url . "?" . $query;
            if (isset($vnp_HashSecret)) {
                $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
                $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
            }
            
            unset($_SESSION['cart']); 
            header('Location: ' . $vnp_Url);
            exit();

        } else {
            // NẾU CHỌN COD
            unset($_SESSION['cart']);
            $_SESSION['order_success'] = "Tuyệt vời! Đơn hàng #ORD-" . sprintf('%04d', $order_id) . " của bạn đã được ghi nhận.";
            header("Location: index.php");
            exit();
        }

        // Chuyển hướng về trang chủ
        header("Location: index.php");
        exit();
    } catch (Throwable $e) {
        $error_msg = "Lỗi hệ thống khi đặt hàng: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Thanh Toán - TechStore</title>
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico" />
    <link rel="stylesheet" href="bootstrap-5.3.8/css/bootstrap.min.css">
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css">
    <link href="assets/css/style.css?v=20260417" rel="stylesheet" />
</head>

<body class="bg-light">

    <?php require_once 'includes/header.php'; ?>

    <div class="container py-5">
        <h3 class="fw-bold mb-4">Hoàn tất đơn hàng</h3>

        <?php if (isset($error_msg)): ?>
            <div class="alert alert-danger"><?= $error_msg ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-4">Thông tin giao hàng</h5>

                            <div class="mb-3">
                                <label class="form-label">Họ và tên người nhận <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="fullname"
                                    value="<?= htmlspecialchars($user['username']) ?>" required>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" name="email"
                                        value="<?= htmlspecialchars($user['email']) ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="phone" required
                                        placeholder="09xx xxx xxx">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Địa chỉ nhận hàng chi tiết <span
                                        class="text-danger">*</span></label>
                                <div class="row g-3 mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Tỉnh/Thành phố <span
                                                class="text-danger">*</span></label>
                                        <select class="form-select" id="province" name="province" required>
                                            <option value="">Chọn Tỉnh/Thành</option>
                                        </select>
                                        <input type="hidden" name="province_text" id="province_text">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Quận/Huyện <span class="text-danger">*</span></label>
                                        <select class="form-select" id="district" name="district" required disabled>
                                            <option value="">Chọn Quận/Huyện</option>
                                        </select>
                                        <input type="hidden" name="district_text" id="district_text">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Phường/Xã <span class="text-danger">*</span></label>
                                        <select class="form-select" id="ward" name="ward" required disabled>
                                            <option value="">Chọn Phường/Xã</option>
                                        </select>
                                        <input type="hidden" name="ward_text" id="ward_text">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Địa chỉ chi tiết (Số nhà, tên đường)</label>
                                    <input type="text" class="form-control" name="address_detail" required
                                        placeholder="Ví dụ: 123 Đường ABC...">
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Ghi chú thêm (Tùy chọn)</label>
                                <textarea class="form-control" name="note" rows="2"
                                    placeholder="Giao giờ hành chính, gọi trước khi đến..."></textarea>
                            </div>

                            <h5 class="fw-bold mb-3">Phương thức thanh toán</h5>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="payment_method" value="COD"
                                    id="payCOD" checked>
                                <label class="form-check-label" for="payCOD">
                                    Thanh toán khi nhận hàng (COD)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" value="VNPAY"
                                    id="payVNPay">
                                <label class="form-check-label" for="payVNPay">
                                    Thanh toán trực tuyến VNPay
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm position-sticky" style="top: 20px; border-radius: 16px;">
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-4">Tóm tắt đơn hàng</h5>

                            <div class="order-items mb-4" style="max-height: 300px; overflow-y: auto;">
                                <?php foreach ($cart as $item): ?>
                                    <div class="d-flex justify-content-between mb-3 border-bottom pb-2">
                                        <div class="pe-3">
                                            <h6 class="mb-0" style="font-size: 14px;"><?= htmlspecialchars($item['name']) ?>
                                            </h6>
                                            <small class="text-muted">SL: <?= $item['quantity'] ?></small>
                                        </div>
                                        <span class="fw-medium text-nowrap">
                                            <?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?> ₫
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="d-flex justify-content-between mb-3">
                                <span class="text-muted">Tạm tính</span>
                                <span class="fw-semibold"><?= number_format($totalPrice, 0, ',', '.') ?> ₫</span>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span class="text-muted">Phí vận chuyển</span>
                                <span class="fw-bold text-success" id="displayShipping">Miễn phí</span>
                            </div>

                            <hr class="my-4" style="border-color: #e4e7e9;">

                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <span class="text-muted fw-medium">Tổng cộng</span>
                                <span class="fw-bold fs-3 text-primary" id="displayTotal">
                                    <?= number_format($totalPrice, 0, ',', '.') ?> ₫
                                </span>
                            </div>

                            <button type="submit" name="btn_DatHang" class="btn btn-primary w-100 py-3 fw-bold"
                                style="border-radius: 12px; font-size: 16px;">
                                XÁC NHẬN ĐẶT HÀNG
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script src="bootstrap-5.3.8/js/bootstrap.bundle.min.js"></script>
    <?php require_once 'includes/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const host = "https://provinces.open-api.vn/api/";

            // 1. Lấy danh sách Tỉnh/Thành
            fetch(host + "?depth=1")
                .then(res => {
                    if (!res.ok) throw new Error("Không thể kết nối API");
                    return res.json();
                })
                .then(data => {
                    let provinceSelect = document.getElementById("province");
                    data.forEach(item => {
                        let option = new Option(item.name, item.code);
                        provinceSelect.add(option);
                    });
                })
                .catch(err => console.error("Lỗi lấy tỉnh thành:", err));

            // 2. Khi chọn Tỉnh -> Lấy Huyện
            document.getElementById("province").addEventListener("change", function () {
                let provinceCode = this.value;
                let districtSelect = document.getElementById("district");
                let wardSelect = document.getElementById("ward");

                // Lưu Text (tên Tỉnh) vào input ẩn để gửi lên PHP
                document.getElementById("province_text").value = this.options[this.selectedIndex].text;

                districtSelect.innerHTML = '<option value="">Chọn Quận/Huyện</option>';
                wardSelect.innerHTML = '<option value="">Chọn Phường/Xã</option>';
                districtSelect.disabled = !provinceCode;
                wardSelect.disabled = true;

                if (provinceCode) {
                    fetch(host + "p/" + provinceCode + "?depth=2")
                        .then(res => res.json())
                        .then(data => {
                            data.districts.forEach(item => {
                                districtSelect.add(new Option(item.name, item.code));
                            });
                        });

                    // Tính phí ship (Ví dụ: HCM (mã 79) là 15k, tỉnh khác 35k)
                    let shipFee = (provinceCode == "79") ? 15000 : 35000;
                    updateShippingFee(shipFee);
                }
            });

            // 3. Khi chọn Huyện -> Lấy Xã
            document.getElementById("district").addEventListener("change", function () {
                let districtCode = this.value;
                let wardSelect = document.getElementById("ward");

                // Lưu Text (tên Huyện) vào input ẩn
                document.getElementById("district_text").value = this.options[this.selectedIndex].text;

                wardSelect.innerHTML = '<option value="">Chọn Phường/Xã</option>';
                wardSelect.disabled = !districtCode;

                if (districtCode) {
                    fetch(host + "d/" + districtCode + "?depth=2")
                        .then(res => res.json())
                        .then(data => {
                            data.wards.forEach(item => {
                                wardSelect.add(new Option(item.name, item.code));
                            });
                        });
                }
            });

            // 4. Khi chọn Phường/Xã -> Lưu text
            document.getElementById("ward").addEventListener("change", function () {
                document.getElementById("ward_text").value = this.options[this.selectedIndex].text;
            });
        });

        function updateShippingFee(fee) {
            const subtotal = <?= $totalPrice ?>;
            const shipElement = document.getElementById("displayShipping");
            const totalElement = document.getElementById("displayTotal");

            if (shipElement) shipElement.innerText = fee.toLocaleString('vi-VN') + " ₫";
            if (totalElement) totalElement.innerText = (subtotal + fee).toLocaleString('vi-VN') + " ₫";
        }
    </script>
</body>

</html>