<?php

class Pengguna {
    private $conn;
    
    public function __construct() {
        $this->conn = Database::getInstance();
    }
    
    public function login($username, $password) {
        $sql = "SELECT * FROM pengguna WHERE username = ? AND password = ? AND status = 'aktif'";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$username, $password]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getAllPengguna() {
        $sql = "SELECT * FROM pengguna ORDER BY dibuat_pada DESC";
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll();
    }
    
    public function createPengguna($data) {
        $sql = "INSERT INTO pengguna (username, password, nama_lengkap, email, peran) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['username'],
            $data['password'],
            $data['nama_lengkap'],
            $data['email'],
            $data['peran']
        ]);
    }
    
    public function updatePengguna($id, $data) {
        $sql = "UPDATE pengguna SET username = ?, nama_lengkap = ?, email = ?, peran = ? 
                WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['username'],
            $data['nama_lengkap'],
            $data['email'],
            $data['peran'],
            $id
        ]);
    }
    
    public function deletePengguna($id) {
        $sql = "DELETE FROM pengguna WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$id]);
    }
}
?>