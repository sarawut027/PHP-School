<?php
class RepairRequest {
    private $conn;
    private $table_name = "repair_requests";

    public function __construct($db) {
        $this->conn = $db;
    }

    // สร้างใบแจ้งซ่อมใหม่
    public function create($user_id, $building, $room, $problem_type, $description, $urgency) {
        $request_no = $this->generateRequestNo();
        
        $query = "INSERT INTO " . $this->table_name . " 
                  (request_no, user_id, building, room, problem_type, description, urgency, status) 
                  VALUES (:request_no, :user_id, :building, :room, :problem_type, :description, :urgency, 'pending')";
                  
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':request_no', $request_no);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':building', $building);
        $stmt->bindParam(':room', $room);
        $stmt->bindParam(':problem_type', $problem_type);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':urgency', $urgency);
        
        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    // อ่านรายการใบแจ้งซ่อมทั้งหมด (สำหรับ Dashboard)
    public function readAll() {
        $query = "SELECT r.*, u.fullname as requester_name, t.fullname as technician_name 
                  FROM " . $this->table_name . " r
                  LEFT JOIN users u ON r.user_id = u.id
                  LEFT JOIN users t ON r.assigned_to = t.id
                  ORDER BY r.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // อ่านรายละเอียด 1 ใบแจ้งซ่อม
    public function readOne($id) {
        $query = "SELECT r.*, u.fullname as requester_name, t.fullname as technician_name
                  FROM " . $this->table_name . " r
                  LEFT JOIN users u ON r.user_id = u.id
                  LEFT JOIN users t ON r.assigned_to = t.id
                  WHERE r.id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // สถิติสำหรับ Dashboard
    public function getStats() {
        $query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
                  FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ฟังก์ชันสร้างรหัสแจ้งซ่อม RP-YYYYMM-XXX
    private function generateRequestNo() {
        $yearMonth = date("Ym");
        $prefix = "RP-" . $yearMonth . "-";
        
        $query = "SELECT request_no FROM " . $this->table_name . " 
                  WHERE request_no LIKE :prefix 
                  ORDER BY request_no DESC LIMIT 1";
                  
        $stmt = $this->conn->prepare($query);
        $searchPrefix = $prefix . "%";
        $stmt->bindParam(':prefix', $searchPrefix);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $last_no = $row['request_no'];
            // แยกสตริงเอา 3 หลักท้าย
            $parts = explode("-", $last_no);
            $last_num = (int)end($parts);
            $new_num = $last_num + 1;
            return sprintf("%s%03d", $prefix, $new_num);
        } else {
            return sprintf("%s%03d", $prefix, 1);
        }
    }

    // มอบหมายงานให้ช่าง
    public function assignTo($id, $technician_id, $due_date) {
        $query = "UPDATE " . $this->table_name . " 
                  SET assigned_to = :technician_id, due_date = :due_date, status = 'accepted' 
                  WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':technician_id', $technician_id);
        $stmt->bindParam(':due_date', $due_date);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    // อัปเดตสถานะงานซ่อม
    public function updateStatus($id, $status, $cost = null, $result = null) {
        $query = "UPDATE " . $this->table_name . " SET status = :status";
        
        if ($status === 'completed') {
            $query .= ", cost = :cost, result = :result, completion_date = CURRENT_DATE";
        }
        
        $query .= " WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $id);
        
        if ($status === 'completed') {
            $stmt->bindParam(':cost', $cost);
            $stmt->bindParam(':result', $result);
        }
        
        return $stmt->execute();
    }

    // ดึงรายชื่อช่างซ่อมทั้งหมด
    public function getTechnicians() {
        $query = "SELECT id, fullname FROM users WHERE role = 'technician'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // ดึงรูปภาพของใบแจ้งซ่อม
    public function getImages($repair_id) {
        $query = "SELECT * FROM repair_images WHERE repair_id = :repair_id ORDER BY uploaded_at ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':repair_id', $repair_id);
        $stmt->execute();
        return $stmt;
    }
}
?>
