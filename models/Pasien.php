<?php

class Pasien {
    private $conn;
    
    public function __construct() {
        $this->conn = Database::getInstance();
    }
    
    public function getAllPasien() {
        $sql = "SELECT * FROM pasien ORDER BY dibuat_pada DESC";
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll();
    }
    
    public function getPasienById($id) {
        $sql = "SELECT * FROM pasien WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    // TAMBAHKAN METHOD INI ↓↓↓
    public function getPasienByKode($kode_pasien) {
        $sql = "SELECT * FROM pasien WHERE kode_pasien = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$kode_pasien]);
        return $stmt->fetch();
    }
    // TAMBAHKAN METHOD INI ↑↑↑
    
    public function createPasien($data) {
        $sql = "INSERT INTO pasien (kode_pasien, nama_lengkap, jenis_kelamin, tanggal_lahir, alamat, telepon, email) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['kode_pasien'],
            $data['nama_lengkap'],
            $data['jenis_kelamin'],
            $data['tanggal_lahir'],
            $data['alamat'],
            $data['telepon'],
            $data['email']
        ]);
    }
    
    public function updatePasien($id, $data) {
        $sql = "UPDATE pasien SET 
                nama_lengkap = ?, jenis_kelamin = ?, tanggal_lahir = ?, alamat = ?, telepon = ?, email = ? 
                WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['nama_lengkap'],
            $data['jenis_kelamin'],
            $data['tanggal_lahir'],
            $data['alamat'],
            $data['telepon'],
            $data['email'],
            $id
        ]);
    }
    
    public function deletePasien($id) {
        $sql = "DELETE FROM pasien WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$id]);
    }
    
    public function getStatistikPasien() {
        $sql = "SELECT 
                COUNT(*) as total_pasien,
                (SELECT COUNT(*) FROM pasien WHERE DATE(dibuat_pada) = CURDATE()) as pasien_baru_hari_ini,
                (SELECT COUNT(*) FROM pemeriksaan WHERE DATE(tanggal_pemeriksaan) = CURDATE()) as pemeriksaan_hari_ini
                FROM pasien";
        $stmt = $this->conn->query($sql);
        return $stmt->fetch();
    }
}
?>