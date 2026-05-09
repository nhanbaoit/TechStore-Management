<?php
session_start();

// Mở cái giỏ (nếu chưa có thì tạo mới)
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Nhận dữ liệu gửi lên từ JavaScript (định dạng JSON)
$data = json_decode(file_get_contents('php://input'), true);

if ($data && isset($data['id'])) {
    $id = $data['id'];

    // Nếu sản phẩm đã có trong giỏ, cộng thêm 1
    if (isset($_SESSION['cart'][$id])) {
        $_SESSION['cart'][$id]['quantity'] += 1;
    } else {
        // Nếu chưa có, thêm mới vào giỏ
        $_SESSION['cart'][$id] = [
            'name' => $data['name'],
            'price' => $data['price'],
            'image' => $data['image'],
            'quantity' => 1
        ];
    }

    // Đếm lại tổng số lượng sản phẩm đang có trong giỏ để báo về cho giao diện
    $totalItems = 0;
    foreach ($_SESSION['cart'] as $item) {
        $totalItems += $item['quantity'];
    }

    // Trả về kết quả cho trình duyệt biết là "Thành công"
    echo json_encode(['status' => 'success', 'totalItems' => $totalItems]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Dữ liệu không hợp lệ']);
}
?>