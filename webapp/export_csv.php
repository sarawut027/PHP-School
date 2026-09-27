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

$requests = $repair->readAll();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=repair_requests_' . date('Ymd') . '.csv');

// ป้องกันปัญหาภาษาไทยใน Excel (BOM)
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');
fputcsv($output, ['รหัสแจ้งซ่อม', 'อาคาร', 'ห้อง', 'ประเภทปัญหา', 'รายละเอียด', 'ความเร่งด่วน', 'สถานะ', 'ผู้แจ้ง', 'วันที่แจ้ง', 'ช่างที่รับผิดชอบ', 'วันที่เสร็จสิ้น', 'ค่าใช้จ่าย (บาท)', 'ผลการซ่อม']);

while ($row = $requests->fetch(PDO::FETCH_ASSOC)) {
    // แปลงสถานะเป็นภาษาไทย
    $status_th = [
        'pending' => 'รอดำเนินการ',
        'accepted' => 'รับเรื่องแล้ว',
        'in_progress' => 'กำลังดำเนินการ',
        'completed' => 'เสร็จสิ้น'
    ][$row['status']] ?? $row['status'];
    
    // แปลงความเร่งด่วนเป็นภาษาไทย
    $urgency_th = [
        'low' => 'ทั่วไป',
        'medium' => 'ปานกลาง',
        'high' => 'ด่วนมาก'
    ][$row['urgency']] ?? $row['urgency'];

    // แปลงประเภทเป็นภาษาไทย
    $problem_th = [
        'electrical' => 'ไฟฟ้า',
        'furniture' => 'เฟอร์นิเจอร์',
        'computer' => 'คอมพิวเตอร์',
        'aircon' => 'เครื่องปรับอากาศ',
        'other' => 'อื่นๆ'
    ][$row['problem_type']] ?? $row['problem_type'];

    fputcsv($output, [
        $row['request_no'],
        $row['building'],
        $row['room'],
        $problem_th,
        $row['description'],
        $urgency_th,
        $status_th,
        $row['requester_name'],
        $row['created_at'],
        !empty($row['technician_name']) ? $row['technician_name'] : 'ยังไม่ระบุ',
        !empty($row['completion_date']) ? $row['completion_date'] : '-',
        isset($row['cost']) && $row['cost'] !== null ? number_format($row['cost'], 2) : '-',
        !empty($row['result']) ? $row['result'] : '-'
    ]);
}
fclose($output);
exit();
?>
