-- ฐานข้อมูลระบบแจ้งซ่อมโรงเรียน (School Repair System)
CREATE DATABASE IF NOT EXISTS school_repair CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE school_repair;

-- 1. ตารางผู้ใช้งาน
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    fullname VARCHAR(100) NOT NULL,
    role ENUM('admin', 'teacher', 'technician') NOT NULL,
    email VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. ตารางใบแจ้งซ่อม
CREATE TABLE IF NOT EXISTS repair_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_no VARCHAR(20) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    building VARCHAR(100) NOT NULL,
    room VARCHAR(50) NOT NULL,
    problem_type ENUM('electrical', 'furniture', 'computer', 'aircon', 'other') NOT NULL,
    description TEXT NOT NULL,
    urgency ENUM('low', 'medium', 'high') DEFAULT 'medium',
    status ENUM('pending', 'accepted', 'in_progress', 'completed') DEFAULT 'pending',
    assigned_to INT NULL,
    due_date DATE NULL,
    completion_date DATE NULL,
    cost DECIMAL(10, 2) NULL,
    result TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
);

-- 3. ตารางรูปภาพ
CREATE TABLE IF NOT EXISTS repair_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    repair_id INT NOT NULL,
    image_type ENUM('before', 'after') NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (repair_id) REFERENCES repair_requests(id) ON DELETE CASCADE
);

-- 4. ตารางความคิดเห็น
CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    repair_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (repair_id) REFERENCES repair_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ข้อมูลจำลอง (Mock Data)
-- รหัสผ่านคือ 1234 (hashed by password_hash)
INSERT INTO users (username, password, fullname, role, email) VALUES
('admin', '$2y$10$w/V2uJ0nF.0hR3fFjRzUfeEw4eUuIqySxwN/w1yM8rJvO3z4T/bYW', 'สมชาย ผู้ดูแล', 'admin', 'admin@school.ac.th'),
('teacher1', '$2y$10$w/V2uJ0nF.0hR3fFjRzUfeEw4eUuIqySxwN/w1yM8rJvO3z4T/bYW', 'สมหญิง ครูประจำชั้น', 'teacher', 'teacher1@school.ac.th'),
('tech1', '$2y$10$w/V2uJ0nF.0hR3fFjRzUfeEw4eUuIqySxwN/w1yM8rJvO3z4T/bYW', 'สมหมาย ช่างซ่อม', 'technician', 'tech1@school.ac.th')
ON DUPLICATE KEY UPDATE username=username;
