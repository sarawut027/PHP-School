<?php
session_start();
require_once 'config/database.php';
require_once 'classes/RepairRequest.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$message = '';
$messageType = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $db = new Database();
    $conn = $db->getConnection();
    $repair = new RepairRequest($conn);

    $building = $_POST['building'];
    $room = $_POST['room'];
    $problem_type = $_POST['problem_type'];
    $description = $_POST['description'];
    $urgency = $_POST['urgency'];
    
    // Validate File Upload (Must have file)
    if(isset($_FILES['repair_image']) && $_FILES['repair_image']['error'] == 0) {
        // Create Request
        $repair_id = $repair->create($_SESSION['user_id'], $building, $room, $problem_type, $description, $urgency);
        
        if($repair_id) {
            // Process Image Upload
            $targetDir = "uploads/";
            if(!is_dir($targetDir)) mkdir($targetDir, 0777, true);
            
            $fileName = uniqid() . "_" . basename($_FILES["repair_image"]["name"]);
            $targetFilePath = $targetDir . $fileName;
            
            if(move_uploaded_file($_FILES["repair_image"]["tmp_name"], $targetFilePath)) {
                // Insert into repair_images table
                $stmt = $conn->prepare("INSERT INTO repair_images (repair_id, image_type, image_path) VALUES (?, 'before', ?)");
                $stmt->execute([$repair_id, $targetFilePath]);
                
                $message = "บันทึกใบแจ้งซ่อมและอัปโหลดรูปภาพสำเร็จ";
                $messageType = "success";
            }
        } else {
            $message = "เกิดข้อผิดพลาดในการสร้างใบแจ้งซ่อม";
            $messageType = "error";
        }
    } else {
        $message = "กรุณาแนบรูปภาพประกอบก่อนซ่อม";
        $messageType = "warning";
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>แจ้งซ่อมใหม่ - ระบบแจ้งซ่อมโรงเรียน</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <nav class="navbar">
        <a href="dashboard.php" class="nav-brand">← กลับหน้าหลัก</a>
    </nav>

    <div class="container" style="max-width: 600px;">
        <div style="background: var(--surface); padding: 2rem; border-radius: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
            <h2 style="margin-bottom: 20px; color: var(--primary);">📝 แจ้งซ่อมใหม่ (New Request)</h2>

            <form action="" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>อาคาร <span class="req" style="color:var(--danger);">*</span></label>
                    <select name="building" class="form-control" required>
                        <option value="">เลือกอาคาร...</option>
                        <option value="อาคาร 1 (เรียนรวม)">อาคาร 1 (เรียนรวม)</option>
                        <option value="อาคาร 2 (วิทยาศาสตร์)">อาคาร 2 (วิทยาศาสตร์)</option>
                        <option value="โรงอาหาร">โรงอาหาร</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>ห้อง <span class="req" style="color:var(--danger);">*</span></label>
                    <input type="text" name="room" class="form-control" placeholder="เช่น 201" required>
                </div>

                <div class="form-group">
                    <label>ประเภทปัญหา <span class="req" style="color:var(--danger);">*</span></label>
                    <select name="problem_type" class="form-control" required>
                        <option value="">เลือกปัญหา...</option>
                        <option value="electrical">ไฟฟ้า</option>
                        <option value="furniture">เฟอร์นิเจอร์</option>
                        <option value="computer">คอมพิวเตอร์</option>
                        <option value="aircon">เครื่องปรับอากาศ</option>
                        <option value="other">อื่นๆ</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>รายละเอียดปัญหา <span class="req" style="color:var(--danger);">*</span></label>
                    <textarea name="description" class="form-control" rows="3" required></textarea>
                </div>

                <div class="form-group">
                    <label>ความเร่งด่วน <span class="req" style="color:var(--danger);">*</span></label>
                    <div class="radio-group">
                        <label><input type="radio" name="urgency" value="low"> ทั่วไป</label>
                        <label><input type="radio" name="urgency" value="medium" checked> ปานกลาง</label>
                        <label><input type="radio" name="urgency" value="high"> ด่วนมาก</label>
                    </div>
                </div>

                <div class="form-group">
                    <label>แนบรูปภาพประกอบก่อนซ่อม <span class="req" style="color:var(--danger);">*</span></label>
                    <div class="upload-area" onclick="document.getElementById('fileUpload').click()">
                        <div class="upload-icon">📸</div>
                        <div>คลิกเพื่อเลือกไฟล์รูปภาพ</div>
                        <small style="color: var(--text-muted);">รองรับ .jpg, .png</small>
                    </div>
                    <input type="file" id="fileUpload" name="repair_image" accept="image/png, image/jpeg" required>
                </div>

                <button type="submit" class="btn btn-primary">บันทึกการแจ้งซ่อม</button>
            </form>
        </div>
    </div>

    <script>
        <?php if(!empty($message)): ?>
            Swal.fire({
                icon: '<?= $messageType ?>',
                title: '<?= $messageType == "success" ? "สำเร็จ!" : "แจ้งเตือน" ?>',
                text: '<?= $message ?>',
                confirmButtonColor: '#4f46e5'
            }).then((result) => {
                <?php if($messageType == 'success'): ?>
                    window.location.href = 'dashboard.php';
                <?php endif; ?>
            });
        <?php endif; ?>

        // Show selected file name
        document.getElementById('fileUpload').addEventListener('change', function(e) {
            if(e.target.files.length > 0) {
                document.querySelector('.upload-area div:nth-child(2)').innerText = "เลือกไฟล์แล้ว: " + e.target.files[0].name;
            }
        });
    </script>
</body>
</html>
