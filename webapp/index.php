<?php
session_start();
require_once 'config/database.php';
require_once 'classes/User.php';

// หากล็อกอินอยู่แล้วให้ไปที่ Dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();
$user = new User($conn);

$error_msg = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        if ($user->login($username, $password)) {
            $_SESSION['user_id'] = $user->id;
            $_SESSION['role'] = $user->role;
            $_SESSION['fullname'] = $user->fullname;
            header("Location: dashboard.php");
            exit();
        } else {
            $error_msg = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
        }
    } else {
        $error_msg = "กรุณากรอกชื่อผู้ใช้และรหัสผ่าน";
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>เข้าสู่ระบบ - ระบบแจ้งซ่อมโรงเรียน</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <h1>🔧 School Repair</h1>
            <p>ระบบแจ้งซ่อมภายในโรงเรียน</p>

            <?php if(!empty($error_msg)): ?>
                <div class="alert alert-danger"><?= $error_msg ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label>ชื่อผู้ใช้งาน (Username)</label>
                    <input type="text" name="username" class="form-control" placeholder="เช่น teacher1" required>
                </div>
                <div class="form-group">
                    <label>รหัสผ่าน (Password)</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn btn-primary">เข้าสู่ระบบ</button>
            </form>
            
            <div style="margin-top: 20px; font-size: 0.85rem; color: #64748b;">
                <p><strong>บัญชีทดสอบ (รหัสผ่าน: 1234):</strong></p>
                <p>admin, teacher1, tech1</p>
            </div>
        </div>
    </div>
</body>
</html>
