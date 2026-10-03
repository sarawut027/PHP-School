<?php
class Database {
    private $host = "localhost";
    private $db_name = "school_repair";
    private $username = "root";
    private $password = "";
    public $conn;

    // การเชื่อมต่อฐานข้อมูลโดยใช้ PDO
    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4", 
                $this->username, 
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // ป้องกันการจำลอง Prepare Statement (Security)
            $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}
?>
