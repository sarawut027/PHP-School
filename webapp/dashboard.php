<?php
session_start();
require_once 'config/database.php';
require_once 'classes/RepairRequest.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();
$repair = new RepairRequest($conn);

$stats = $repair->getStats();
$requests = $repair->readAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Dashboard - ระบบแจ้งซ่อมโรงเรียน</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <a href="dashboard.php" class="nav-brand">🔧 RepairSystem</a>
        <div class="nav-user">
            <?= htmlspecialchars($_SESSION['fullname']) ?> (<?= $_SESSION['role'] ?>)
            <a href="logout.php" class="nav-logout">ออกระบบ</a>
        </div>
    </nav>

    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2>ภาพรวมงานซ่อม (Dashboard)</h2>
            <?php if($_SESSION['role'] == 'teacher' || $_SESSION['role'] == 'admin'): ?>
                <a href="new_request.php" class="btn btn-primary" style="width: auto; padding: 0.5rem 1rem;">+ แจ้งซ่อมใหม่</a>
            <?php endif; ?>
        </div>

        <div class="stats-grid">
            <div class="stat-card primary">
                <div class="stat-value"><?= $stats['total'] ?></div>
                <div class="stat-label">งานทั้งหมด</div>
            </div>
            <div class="stat-card warning">
                <div class="stat-value"><?= $stats['pending'] ?></div>
                <div class="stat-label">รอดำเนินการ</div>
            </div>
            <div class="stat-card info">
                <div class="stat-value"><?= $stats['in_progress'] ?></div>
                <div class="stat-label">กำลังซ่อม</div>
            </div>
            <div class="stat-card success">
                <div class="stat-value"><?= $stats['completed'] ?></div>
                <div class="stat-label">เสร็จสิ้นแล้ว</div>
            </div>
        </div>

        <h3>รายการแจ้งซ่อมล่าสุด</h3>
        <br>
        <div class="card-list">
            <?php if($requests->rowCount() > 0): ?>
                <?php while ($row = $requests->fetch(PDO::FETCH_ASSOC)): ?>
                    <div class="list-item">
                        <div class="item-desc">
                            <div class="item-header">
                                <span class="req-no"><?= htmlspecialchars($row['request_no']) ?></span>
                                <span class="badge <?= $row['status'] ?>">
                                    <?= strtoupper($row['status']) ?>
                                </span>
                            </div>
                            <div style="margin-top: 5px;">
                                <strong>ปัญหา:</strong> <?= htmlspecialchars($row['description']) ?>
                            </div>
                            <div style="font-size: 0.85rem; margin-top: 5px;">
                                📍 อาคาร <?= htmlspecialchars($row['building']) ?> ห้อง <?= htmlspecialchars($row['room']) ?> 
                                | 👤 ผู้แจ้ง: <?= htmlspecialchars($row['requester_name']) ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="list-item" style="text-align: center; color: var(--text-muted);">
                    ยังไม่มีรายการแจ้งซ่อมในระบบ
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
