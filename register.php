<?php
include_once "config/database.php";
session_start();
$db = new database();

// ======================================================
// 1. XỬ LÝ ĐĂNG KÝ (REGISTER)
// ======================================================
if (isset($_POST["Accept"])) {

    unset($_SESSION["error"]);
    unset($_SESSION["errors"]);

    $userName = trim($_POST["userName"]);
    $Email = trim($_POST["email"]);
    $password_raw = $_POST["password"];
    $errors = [];

    // --- Validate y hệt của đại ca ---
    if (empty($userName)) {
        $errors["username"] = "Username không được để trống";
    } elseif (strlen($userName) < 4 || mb_strlen($userName, 'UTF-8') > 20) {
        $errors["username"] = "Username phải >= 4 ký tự và nhỏ hơn 20 ký tự";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $userName)) {
        $errors["username"] = "Username không chứa ký tự đặc biệt";
    }

    if (empty($Email)) {
        $errors["email"] = "Email không được để trống";
    } elseif (strpos($Email, ' ') !== false) {
        $errors["email"] = "Email không được chứa khoảng trắng";
    } else {
        $allowed_domains = ['gmail.com', 'outlook.com', 'yahoo.com'];
        $email_parts = explode('@', $Email);
        if (count($email_parts) == 2) {
            $domain = $email_parts[1];
            if (!in_array($domain, $allowed_domains)) {
                $errors["email"] = "Domain email không được hỗ trợ";
            }
        }
        if (!filter_var($Email, FILTER_VALIDATE_EMAIL)) {
            $errors["email"] = "Email không hợp lệ";
        }
    }

    if (empty($password_raw)) {
        $errors["password"] = "Password không được để trống";
    } else {
        if (strlen($password_raw) < 6) {
            $errors["password"] = "Password phải >= 6 ký tự";
        } elseif (!preg_match('/[A-Z]/', $password_raw)) {
            $errors["password"] = "Phải có ít nhất 1 chữ hoa";
        } elseif (!preg_match('/\d/', $password_raw)) {
            $errors["password"] = "Phải có ít nhất 1 số";
        } elseif (!preg_match('/[\W_]/', $password_raw)) {
            $errors["password"] = "Phải có ký tự đặc biệt";
        }
    }

    // Nếu có lỗi -> Trượt về form Register
    if (!empty($errors)) {
        $_SESSION["errors"] = $errors;
        $_SESSION["old_userName"] = $userName;
        $_SESSION["old_email"] = $Email;
        $_SESSION["form"] = "register";
        header("Location: register.php");
        exit();
    }

    $password = password_hash($password_raw, PASSWORD_DEFAULT);
    $role = "user";

    try {
        // Kiểm tra trùng
        $checkEmailAndUserName = "SELECT * FROM account WHERE username = '$userName' OR email = '$Email'";
        $result = $db->select($checkEmailAndUserName);

        if (count($result) > 0) {
            $error = "username"; // Mặc định báo trùng username phòng hờ vòng lặp tịt ngòi
            foreach ($result as $row) {
                if (strtolower($row["username"]) == strtolower($userName)) {
                    $error = "username";
                    break;
                }
                if (strtolower($row["email"]) == strtolower($Email)) {
                    $error = "email";
                    break;
                }
            }
            $_SESSION["error"] = $error;
            $_SESSION["old_userName"] = $userName;
            $_SESSION["old_email"] = $Email;
            $_SESSION["form"] = "register";
            header("Location: register.php");
            exit();
        } else {
            // INSERT THÀNH CÔNG (Phiên bản chống lỗi thiếu cột State)
            // Tui thêm cột state = 'Hoạt động' vào luôn cho nó khỏi đòi hỏi
            $sql = "INSERT INTO account(username, password, email, role, state) 
                    VALUES('$userName', '$password', '$Email', '$role', 'Hoạt động')";

            // Chạy lệnh lưu vào DB
            $ket_qua = $db->execute($sql);

            if ($ket_qua) {
                // 🚨 DỌN SẠCH DỮ LIỆU CŨ ĐỂ FORM TRỐNG TRẢI
                unset($_SESSION["old_userName"]);
                unset($_SESSION["old_email"]);
                $_SESSION["register_success"] = "Đăng ký thành công! Vui lòng đăng nhập để tiếp tục.";
                $_SESSION["form"] = "login";
                header("Location: register.php");
                exit();
            } else {
                die("<h2 style='color:red;'>🚨 BÁO ĐỘNG ĐỎ: Lỗi Database!</h2>
                     <p>Lệnh SQL này đã bị MySQL từ chối. Đại ca copy câu này quăng vào tab SQL của phpMyAdmin chạy thử xem nó báo lỗi gì nha:</p>
                     <b style='background: #eee; padding: 10px; display: block;'>$sql</b>");
            }
        }
    } catch (Throwable $e) {
        // Lỡ Database bị điên (như sai tên bảng) thì nó báo lỗi ở đây chứ không bị đơ màn hình
        $_SESSION["errors"]["username"] = "Lỗi Database: " . $e->getMessage();
        $_SESSION["form"] = "login";
        header("Location: register.php");
        exit();
    }
}

// ======================================================
// 2. XỬ LÝ ĐĂNG NHẬP (LOGIN) - Đã xóa bản sao bị trùng
// ======================================================
if (isset($_POST["Login"])) {
    unset($_SESSION["login_errors"]);
    unset($_SESSION["login_error"]);

    $userName = trim($_POST["userName"]);
    $password = $_POST["password"];
    $errors = [];

    // --- Validate y hệt của đại ca ---
    if (empty($userName)) {
        $errors["userName"] = "Vui lòng nhập username hoặc email";
    } else {
        if (filter_var($userName, FILTER_VALIDATE_EMAIL)) {
            if (strpos($userName, ' ') !== false) {
                $errors["userName"] = "Email không được chứa khoảng trắng";
            }
        } else {
            if (mb_strlen($userName, 'UTF-8') > 20) {
                $errors["userName"] = "Username không được vượt quá 20 ký tự";
            }
        }
    }

    if (empty($password)) {
        $errors["password"] = "Vui lòng nhập password";
    }

    if (!empty($errors)) {
        $_SESSION["login_errors"] = $errors;
        $_SESSION["old_login"] = $userName;
        $_SESSION["form"] = "login";
        header("Location: register.php");
        exit();
    }

    try {
        $sql = "SELECT * FROM account WHERE username='$userName' OR email='$userName'";
        $result = $db->select($sql);

        if (count($result) > 0) {
            $user = $result[0];

            if (password_verify($password, $user["password"])) {

                // 🚨 TÍNH NĂNG ADMIN VIP: KIỂM TRA BỊ KHÓA 🚨
                $currentState = trim($user['state'] ?? $user['State'] ?? '');

                if ($currentState === 'Đã bị khóa') {
                    // Truyền thông báo lỗi qua Session để load bằng SweetAlert
                    $_SESSION["login_lock_error"] = "Tài khoản của bạn đã bị tạm khóa do vi phạm chính sách. Vui lòng liên hệ Quản trị viên để được hỗ trợ.";
                    $_SESSION["form"] = "login";
                    header("Location: register.php");
                    exit();
                } else {
                    // Cấp thẻ đi qua
                    $_SESSION["user"] = [
                        "id" => $user["id"],
                        "username" => $user["username"],
                        "email" => $user["email"],
                        "role" => $user["role"],
                    ];
                    $redirect = ($_SESSION["user"]["role"] === "admin") ? "admin/admin.php" : "index.php";
                    header("Location: $redirect");
                    exit();
                }
            } else {
                $_SESSION["login_error"] = "password";
                $_SESSION["old_login"] = $userName;
                $_SESSION["form"] = "login";
                header("Location: register.php");
                exit();
            }
        } else {
            $_SESSION["login_error"] = "notfound";
            $_SESSION["old_login"] = $userName;
            $_SESSION["form"] = "login";
            header("Location: register.php");
            exit();
        }
    } catch (Throwable $e) {
        $_SESSION["login_errors"]["userName"] = "Lỗi Database: " . $e->getMessage();
        $_SESSION["form"] = "login";
        header("Location: register.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Tech Store</title>

    <link rel="icon" type="image/x-icon" href="assets/favicon.ico" />
    <link rel="stylesheet" href="bootstrap-5.3.8/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">

    <style>
        .input-box {
            position: relative;
            /* Giữ icon nằm chuẩn trong khung */
        }

        /* Cố định icon chính sang bên trái */
        .input-box .icon-left {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #333;
            /* Có thể đổi màu cho hợp theme của bạn */
            z-index: 10;
        }

        /* Cố định icon con mắt sang bên phải */
        .input-box .icon-right {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #333;
            z-index: 10;
        }

        /* Đẩy chữ trong input để không bị đè lên icon bên trái */
        .input-box input {
            padding-left: 45px !important;
        }

        /* Nếu input có con mắt bên phải thì đẩy chữ tránh xa bên phải */
        .input-box input.has-eye {
            padding-right: 45px !important;
        }

        /* Loại bỏ icon cũ (nếu login.css có set default cho thẻ i) để tránh bị double icon */
        .input-box i:not(.icon-left):not(.icon-right) {
            display: none !important;
        }
    </style>
</head>

<body>

    <div class="container <?php echo (($_SESSION['form'] ?? '') == 'register') ? 'active' : ''; ?>">

        <div class="form-box login">
            <form action="" method="post">
                <h1>Login</h1>

                <div class="input-box" style="margin-bottom: 30px;">
                    <i class="fa-solid fa-user icon-left"></i>
                    <?php $old = $_SESSION['old_login'] ?? ''; ?>
                    <input type="text" name="userName" placeholder="Username or Email" value="<?php echo htmlspecialchars($old); ?>">
                </div>

                <?php
                if (isset($_SESSION["login_errors"]["userName"])) {
                    echo "<div style='color: #dc3545; font-size: 13px; text-align: left; margin-top: -25px; margin-bottom: 15px; padding-left: 15px;'>" . $_SESSION["login_errors"]["userName"] . "</div>";
                } elseif (isset($_SESSION["login_error"]) && $_SESSION["login_error"] == "notfound") {
                    echo "<div style='color: #dc3545; font-size: 13px; text-align: left; margin-top: -25px; margin-bottom: 15px; padding-left: 15px;'>Tài khoản không tồn tại</div>";
                }
                ?>

                <div class="input-box" style="margin-bottom: 30px;">
                    <i class="fa-solid fa-lock icon-left"></i>
                    <input type="password" placeholder="Password" name="password" class="has-eye">
                    <i class="fa-solid fa-eye-slash icon-right toggle-password"></i>
                </div>

                <?php
                if (!empty($_SESSION["login_errors"]["password"])) {
                    echo "<div style='color: #dc3545; font-size: 13px; text-align: left; margin-top: -25px; margin-bottom: 15px; padding-left: 15px;'>" . $_SESSION["login_errors"]["password"] . "</div>";
                } elseif (isset($_SESSION["login_error"]) && $_SESSION["login_error"] == "password") {
                    echo "<div style='color: #dc3545; font-size: 13px; text-align: left; margin-top: -25px; margin-bottom: 15px; padding-left: 15px;'>Sai mật khẩu</div>";
                }
                ?>

                <div class="foget-link">
                    <a href="#">Forgot Password?</a>
                </div>

                <button type="submit" class="btn" name="Login">Login</button>
                <p class="mobile-text" style="cursor: pointer;" onclick="document.querySelector('.container').classList.add('active');">
                    Don't have an account? <b style="color: #3c5fb6;">Register</b>
                </p>

                <div class="social-icons">
                    <a href="login_google.php"><i class="fa-brands fa-google"></i></a>
                    <a href="login_facebook.php"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="login_twitter.php"><i class="fa-brands fa-twitter"></i></a>
                </div>
            </form>
        </div>


        <div class="form-box register">
            <form action="" method="POST">
                <h1>Registration</h1>

                <div class="input-box" style="margin-bottom: 30px;">
                    <i class="fa-solid fa-user icon-left"></i>
                    <input type="text" name="userName" placeholder="Username" value="<?php echo htmlspecialchars($_SESSION['old_userName'] ?? ''); ?>">
                </div>

                <?php
                if (isset($_SESSION["errors"]["username"])) {
                    echo "<div style='color: #dc3545; font-size: 13px; text-align: left; margin-top: -25px; margin-bottom: 15px; padding-left: 15px;'>" . $_SESSION["errors"]["username"] . "</div>";
                } elseif (isset($_SESSION["error"]) && $_SESSION["error"] == "username") {
                    echo "<div style='color: #dc3545; font-size: 13px; text-align: left; margin-top: -25px; margin-bottom: 15px; padding-left: 15px;'>Username đã tồn tại</div>";
                }
                ?>

                <div class="input-box" style="margin-bottom: 30px;">
                    <i class="fa-solid fa-envelope icon-left"></i>
                    <input type="email" name="email" placeholder="Email" value="<?php echo htmlspecialchars($_SESSION['old_email'] ?? ''); ?>">
                </div>

                <?php
                if (isset($_SESSION["errors"]["email"])) {
                    echo "<div style='color: #dc3545; font-size: 13px; text-align: left; margin-top: -25px; margin-bottom: 15px; padding-left: 15px;'>" . $_SESSION["errors"]["email"] . "</div>";
                } elseif (isset($_SESSION["error"]) && $_SESSION["error"] == "email") {
                    echo "<div style='color: #dc3545; font-size: 13px; text-align: left; margin-top: -25px; margin-bottom: 15px; padding-left: 15px;'>Email đã tồn tại</div>";
                }
                ?>

                <div class="input-box" style="margin-bottom: 30px;">
                    <i class="fa-solid fa-lock icon-left"></i>
                    <input type="password" placeholder="Password" name="password" class="has-eye">
                    <i class="fa-solid fa-eye-slash icon-right toggle-password"></i>
                </div>

                <?php
                if (isset($_SESSION["errors"]["password"])) {
                    echo "<div style='color: #dc3545; font-size: 13px; text-align: left; margin-top: -25px; margin-bottom: 15px; padding-left: 15px;'>" . $_SESSION["errors"]["password"] . "</div>";
                }
                ?>

                <button type="submit" class="btn" name="Accept">Register</button>
                <p class="mobile-text" style="cursor: pointer;" onclick="document.querySelector('.container').classList.remove('active');">
                    Already have an account? <b style="color: #3c5fb6;">Login</b>
                </p>

                <div class="social-icons">
                    <a href="login_google.php"><i class="fa-brands fa-google"></i></a>
                    <a href="login_facebook.php"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="login_twitter.php"><i class="fa-brands fa-twitter"></i></a>
                </div>
            </form>
        </div>


        <div class="toggle-box">
            <div class="toggle-panel toggle-left">
                <h1>Hello, Welcome!</h1>
                <p>Don't have an account?</p>
                <button class="btn register-btn" id="toggleRegister">Register</button>
            </div>

            <div class="toggle-panel toggle-right">
                <h1>Welcome Back!</h1>
                <p>Already have an account?</p>
                <button class="btn login-btn" id="toggleLogin">Login</button>
            </div>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/main.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Tìm tất cả các thẻ i có class toggle-password
            const togglePasswords = document.querySelectorAll(".toggle-password");

            togglePasswords.forEach(function(icon) {
                icon.addEventListener("click", function() {
                    // Lấy ô input nằm cạnh (cùng trong thẻ cha .input-box)
                    const input = this.parentElement.querySelector("input");

                    if (input.type === "password") {
                        // Đổi thành text để hiện mật khẩu
                        input.type = "text";
                        // Đổi icon mắt từ nhắm sang mở
                        this.classList.remove("fa-eye-slash");
                        this.classList.add("fa-eye");
                    } else {
                        // Trả lại password để ẩn mật khẩu
                        input.type = "password";
                        // Đổi icon mắt từ mở sang nhắm
                        this.classList.remove("fa-eye");
                        this.classList.add("fa-eye-slash");
                    }
                });
            });
        });
    </script>

    <?php
    // Gỡ các biến Session ở cuối trang
    unset($_SESSION["login_errors"]);
    unset($_SESSION["login_error"]);
    unset($_SESSION["old_login"]);
    unset($_SESSION["errors"]);
    unset($_SESSION["error"]); // Quan trọng nè
    unset($_SESSION["old_userName"]);
    unset($_SESSION["old_email"]);
    ?>

    <?php if (isset($_SESSION["register_success"])): ?>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                // PHÉP THUẬT LÀ ĐÂY: Dùng JS cưỡng chế lột class 'active' ra để trượt về Login
                let container = document.querySelector('.container');
                if (container) {
                    container.classList.remove('active');
                }

                // Bắn pháo hoa ăn mừng
                Swal.fire({
                    icon: 'success',
                    title: 'Tuyệt vời!',
                    text: '<?= $_SESSION["register_success"] ?>',
                    confirmButtonColor: '#0d6efd'
                });
            });
        </script>
    <?php unset($_SESSION["register_success"]);
    endif; ?>

    <?php if (isset($_SESSION["login_lock_error"])): ?>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Từ chối truy cập!',
                    text: '<?= $_SESSION["login_lock_error"] ?>',
                    confirmButtonColor: '#dc3545',
                    backdrop: `rgba(0,0,0,0.5)`,
                    customClass: {
                        popup: 'rounded-4'
                    }
                });
            });
        </script>
    <?php unset($_SESSION["login_lock_error"]);
    endif; ?>
</body>

</html>