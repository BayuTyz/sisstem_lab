<?php

class HasilPemeriksaan {
    private $conn;
    
    public function __construct() {
        $this->conn = Database::getInstance();
    }
    
    public function addHasilPemeriksaan($data) {
        $sql = "INSERT INTO hasil_pemeriksaan 
                (pemeriksaan_id, nama_parameter, nilai_hasil, nilai_normal, satuan, status) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['pemeriksaan_id'],
            $data['nama_parameter'],
            $data['nilai_hasil'],
            $data['nilai_normal'],
            $data['satuan'],
            $data['status']
        ]);
    }
    
    public function getHasilByPemeriksaanId($pemeriksaan_id) {
        $sql = "SELECT * FROM hasil_pemeriksaan WHERE pemeriksaan_id = ? ORDER BY id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$pemeriksaan_id]);
        return $stmt->fetchAll();
    }
    
    public function deleteHasil($id) {
        $sql = "DELETE FROM hasil_pemeriksaan WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$id]);
    }
}
?>