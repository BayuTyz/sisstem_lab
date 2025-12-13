<?php

class Antrian {
    private $conn;
    
    public function __construct() {
        $this->conn = Database::getInstance();
    }
    
    public function getAllAntrian($tanggal = null) {
        $sql = "SELECT a.*, p.nama_lengkap, p.kode_pasien 
                FROM antrian a 
                JOIN pasien p ON a.pasien_id = p.id";
        
        $params = [];
        if ($tanggal) {
            $sql .= " WHERE a.tanggal_antrian = ?";
            $params[] = $tanggal;
        }
        
        $sql .= " ORDER BY a.nomor_antrian";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getAntrianSaatIni() {
        $today = date('Y-m-d');
        $sql = "SELECT a.*, p.nama_lengkap 
                FROM antrian a 
                JOIN pasien p ON a.pasien_id = p.id
                WHERE a.tanggal_antrian = ? 
                AND a.status = 'diproses'
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$today]);
        return $stmt->fetch();
    }
    
    public function createAntrian($data) {
        $sql = "INSERT INTO antrian (pasien_id, nomor_antrian, tanggal_antrian, status) 
                VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['pasien_id'],
            $data['nomor_antrian'],
            $data['tanggal_antrian'],
            $data['status'] ?? 'menunggu'
        ]);
    }
    
    public function updateStatusAntrian($id, $status) {
        $sql = "UPDATE antrian SET status = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$status, $id]);
    }
}
?>