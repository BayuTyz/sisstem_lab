<?php
// models/User.php

class User {
    private $conn;
    
    public function __construct() {
        $this->conn = Database::getInstance();
    }
    
    public function login($username, $password) {
        $sql = "SELECT * FROM users WHERE username = ? AND password = ? AND status = 'active'";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$username, $password]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getAllUsers() {
        $sql = "SELECT * FROM users ORDER BY created_at DESC";
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll();
    }
    
    public function createUser($data) {
        $sql = "INSERT INTO users (username, password, full_name, email, role) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['username'],
            $data['password'],
            $data['full_name'],
            $data['email'],
            $data['role']
        ]);
    }
    
    public function updateUser($id, $data) {
        $sql = "UPDATE users SET username = ?, full_name = ?, email = ?, role = ? 
                WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['username'],
            $data['full_name'],
            $data['email'],
            $data['role'],
            $id
        ]);
    }
    
    public function deleteUser($id) {
        $sql = "DELETE FROM users WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$id]);
    }
}
?>