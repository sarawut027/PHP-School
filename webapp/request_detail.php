<?php
session_start();
require_once 'config/database.php';
require_once 'classes/RepairRequest.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$id = $_GET['id'];
$db = new Database();
$conn = $db->getConnection();
$repair = new RepairRequest($conn);

$message = $_SESSION['flash_message'] ?? '';
$messageType = $_SESSION['flash_type'] ?? '';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'assign' && $_SESSION['role'] == 'admin') {
        $technician_id = $_POST['technician_id'];
        $due_date = $_POST['due_date'];
        
        if ($repair->assignTo($id, $technician_id, $due_date)) {
            $_SESSION['flash_message'] = "มอบหมายงานสำเร็จ";
            $_SESSION['flash_type'] = "success";
            header("Location: request_detail.php?id=" . urlencode($id));
            exit();
        } else {
            $message = "เกิดข้อผิดพลาดในการมอบหมายงาน";
            $messageType = "error";
        }
    } 
    elseif ($action == 'start_repair' && $_SESSION['role'] == 'technician') {
        if ($repair->updateStatus($id, 'in_progress')) {
            $_SESSION['flash_message'] = "เริ่มดำเนินการซ่อมแซมแล้ว";
            $_SESSION['flash_type'] = "success";
            header("Location: request_detail.php?id=" . urlencode($id));
            exit();
        } else {
            $message = "เกิดข้อผิดพลาดในการเริ่มดำเนินการซ่อม";
            $messageType = "error";
        }
    }
    elseif ($action == 'complete_repair' && $_SESSION['role'] == 'technician') {
        $cost = $_POST['cost'] ?? 0;
        $result = $_POST['result'] ?? '';
        
        // Handle After Image Upload
        if(isset($_FILES['after_image']) && $_FILES['after_image']['error'] == 0) {
            $targetDir = "uploads/";
            if(!is_dir($targetDir)) mkdir($targetDir, 0777, true);
            
            $fileName = uniqid() . "_after_" . basename($_FILES["after_image"]["name"]);
            $targetFilePath = $targetDir . $fileName;
            
            if(move_uploaded_file($_FILES["after_image"]["tmp_name"], $targetFilePath)) {
                $stmt = $conn->prepare("INSERT INTO repair_images (repair_id, image_type, image_path) VALUES (?, 'after', ?)");
                $stmt->execute([$id, $targetFilePath]);
            }
        }
        
        if ($repair->updateStatus($id, 'completed', $cost, $result)) {
            $_SESSION['flash_message'] = "บันทึกผลการซ่อมสำเร็จ";
            $_SESSION['flash_type'] = "success";
            header("Location: request_detail.php?id=" . urlencode($id));
            exit();
        } else {
            $message = "เกิดข้อผิดพลาดในการบันทึกผลการซ่อม";
            $messageType = "error";
        }
    }
}

$requestData = $repair->readOne($id);
$imagesData = $repair->getImages($id);
$images = ['before' => [], 'after' => []];

while($img = $imagesData->fetch(PDO::FETCH_ASSOC)) {
    $images[$img['image_type']][] = $img['image_path'];
}

$technicians = $repair->getTechnicians();

if (!$requestData) {
    echo "ไม่พบข้อมูลใบแจ้งซ่อม";
    exit();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>รายละเอียดแจ้งซ่อม <?= htmlspecialchars($requestData['request_no']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .detail-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        .image-gallery {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 10px;
        }
        .image-gallery img {
            max-width: 200px;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; background: white; }
            .container { box-shadow: none; border: none; max-width: 100%; }
        }
    </style>
</head>
<body>
    <nav class="navbar no-print">
        <a href="dashboard.php" class="nav-brand">← กลับหน้าหลัก</a>
        <div class="nav-user">
            <button onclick="window.print()" class="btn btn-secondary" style="padding: 0.3rem 1rem;">🖨️ พิมพ์/PDF</button>
        </div>
    </nav>

    <div class="container" style="max-width: 800px;">
        <div style="background: var(--surface); padding: 2rem; border-radius: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px;">
                <h2 style="color: var(--primary);">📄 ใบแจ้งซ่อม: <?= htmlspecialchars($requestData['request_no']) ?></h2>
                <span class="badge <?= $requestData['status'] ?>" style="font-size: 1.1rem; padding: 5px 15px;">
                    <?= strtoupper($requestData['status']) ?>
                </span>
            </div>

            <div class="detail-grid">
                <div class="detail-box">
                    <strong>ผู้แจ้ง:</strong> <?= htmlspecialchars($requestData['requester_name']) ?><br>
                    <strong>วันที่แจ้ง:</strong> <?= $requestData['created_at'] ?><br>
                    <strong>สถานที่:</strong> อาคาร <?= htmlspecialchars($requestData['building']) ?> ห้อง <?= htmlspecialchars($requestData['room']) ?><br>
                    <strong>วันที่เสร็จสิ้น:</strong> <?= !empty($requestData['completion_date']) ? $requestData['completion_date'] : '<span style="color:#64748b;">-</span>' ?><br>
                </div>
                <div class="detail-box">
                    <strong>ประเภทปัญหา:</strong> <?= htmlspecialchars($requestData['problem_type']) ?><br>
                    <strong>ความเร่งด่วน:</strong> <?= htmlspecialchars($requestData['urgency']) ?><br>
                    <strong>ช่างที่รับผิดชอบ:</strong> <?= $requestData['technician_name'] ? htmlspecialchars($requestData['technician_name']) : '<span style="color:red">ยังไม่ระบุ</span>' ?><br>
                    <strong>ค่าใช้จ่าย:</strong> <?= isset($requestData['cost']) && $requestData['cost'] !== null ? number_format($requestData['cost'], 2) . ' บาท' : '<span style="color:#64748b;">-</span>' ?><br>
                </div>
            </div>

            <div class="detail-box" style="margin-bottom: 20px;">
                <strong>รายละเอียดปัญหา:</strong>
                <p style="margin-top: 10px; white-space: pre-wrap;"><?= htmlspecialchars($requestData['description']) ?></p>
                
                <?php if(!empty($images['before'])): ?>
                    <div style="margin-top: 15px;">
                        <strong>รูปภาพก่อนซ่อม:</strong>
                        <div class="image-gallery">
                            <?php foreach($images['before'] as $img): ?>
                                <img src="<?= htmlspecialchars($img) ?>" alt="Before Repair">
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <?php if($requestData['status'] == 'completed'): ?>
                <div class="detail-box" style="margin-bottom: 20px; background: #ecfdf5; border-color: #a7f3d0;">
                    <h3 style="color: #047857; margin-bottom: 10px;">✅ ผลการดำเนินการ</h3>
                    <strong>วันที่เสร็จสิ้น:</strong> <?= $requestData['completion_date'] ?><br>
                    <strong>ค่าใช้จ่าย:</strong> <?= number_format($requestData['cost'] ?? 0, 2) ?> บาท<br>
                    <strong>บันทึกจากช่าง:</strong>
                    <p style="margin-top: 5px; white-space: pre-wrap;"><?= htmlspecialchars($requestData['result']) ?></p>

                    <?php if(!empty($images['after'])): ?>
                        <div style="margin-top: 15px;">
                            <strong>รูปภาพหลังซ่อม:</strong>
                            <div class="image-gallery">
                                <?php foreach($images['after'] as $img): ?>
                                    <img src="<?= htmlspecialchars($img) ?>" alt="After Repair">
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Action Forms (No Print) -->
            <div class="no-print">
                <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 20px 0;">
                
                <!-- Admin Assign Form -->
                <?php if($_SESSION['role'] == 'admin' && $requestData['status'] == 'pending'): ?>
                    <div style="background: #eff6ff; padding: 20px; border-radius: 8px; border: 1px solid #bfdbfe;">
                        <h3 style="margin-bottom: 15px; color: #1e40af;">👨‍🔧 มอบหมายงานช่าง</h3>
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="assign">
                            <div class="form-group">
                                <label>เลือกช่างซ่อม</label>
                                <select name="technician_id" class="form-control" required>
                                    <option value="">-- เลือกช่าง --</option>
                                    <?php while($tech = $technicians->fetch(PDO::FETCH_ASSOC)): ?>
                                        <option value="<?= $tech['id'] ?>"><?= htmlspecialchars($tech['fullname']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>กำหนดเสร็จ (Due Date)</label>
                                <input type="date" name="due_date" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary">มอบหมายงาน</button>
                        </form>
                    </div>
                <?php endif; ?>

                <!-- Technician Actions -->
                <?php if($_SESSION['role'] == 'technician' && $requestData['assigned_to'] == $_SESSION['user_id']): ?>
                    
                    <?php if($requestData['status'] == 'accepted'): ?>
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="start_repair">
                            <button type="submit" class="btn btn-primary" style="width: 100%;">🚀 เริ่มดำเนินการซ่อม</button>
                        </form>
                    <?php endif; ?>

                    <?php if($requestData['status'] == 'in_progress'): ?>
                        <div style="background: #fdf2f8; padding: 20px; border-radius: 8px; border: 1px solid #fbcfe8;">
                            <h3 style="margin-bottom: 15px; color: #9d174d;">✅ บันทึกผลการซ่อมแซม</h3>
                            <form method="POST" action="" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="complete_repair">
                                <div class="form-group">
                                    <label>รายละเอียดการซ่อม</label>
                                    <textarea name="result" class="form-control" rows="3" required></textarea>
                                </div>
                                <div class="form-group">
                                    <label>ค่าใช้จ่าย (บาท)</label>
                                    <input type="number" step="0.01" name="cost" class="form-control" value="0" required>
                                </div>
                                <div class="form-group">
                                    <label>อัปโหลดรูปภาพหลังซ่อมเสร็จ</label>
                                    <input type="file" name="after_image" accept="image/png, image/jpeg" class="form-control">
                                </div>
                                <button type="submit" class="btn btn-primary" style="background: #10b981;">บันทึกและปิดงาน</button>
                            </form>
                        </div>
                    <?php endif; ?>
                    
                <?php endif; ?>
            </div>

        </div>
    </div>

    <script>
        <?php if(!empty($message)): ?>
            Swal.fire({
                icon: '<?= $messageType ?>',
                title: '<?= $messageType == "success" ? "สำเร็จ!" : "แจ้งเตือน" ?>',
                text: '<?= $message ?>',
                confirmButtonColor: '#4f46e5',
                confirmButtonText: 'ตกลง'
            });
        <?php endif; ?>
    </script>
</body>
</html>
