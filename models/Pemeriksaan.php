<?php

class Pemeriksaan {
    private $conn;
    
    public function __construct() {
        $this->conn = Database::getInstance();
    }
    
    public function getAllPemeriksaan() {
        $sql = "SELECT p.*, ps.nama_lengkap as nama_pasien, ps.kode_pasien 
                FROM pemeriksaan p 
                JOIN pasien ps ON p.pasien_id = ps.id 
                ORDER BY p.tanggal_pemeriksaan DESC";
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll();
    }
    
    public function generateKodePemeriksaan() {
        $sql = "SELECT kode_pemeriksaan FROM pemeriksaan ORDER BY kode_pemeriksaan DESC LIMIT 1";
        $stmt = $this->conn->query($sql);
        $lastCode = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($lastCode && !empty($lastCode['kode_pemeriksaan'])) {
            preg_match('/EX(\d+)/', $lastCode['kode_pemeriksaan'], $matches);
            $nextNumber = isset($matches[1]) ? (int)$matches[1] + 1 : 1;
        } else {
            $nextNumber = 1;
        }
        
        return 'EX' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }
    
    public function createPemeriksaan($data) {
        if (empty($data['kode_pemeriksaan'])) {
            $data['kode_pemeriksaan'] = $this->generateKodePemeriksaan();
        }
        
        $sql = "INSERT INTO pemeriksaan 
                (pasien_id, kode_pemeriksaan, jenis_pemeriksaan, nama_dokter, 
                 tanggal_pemeriksaan, catatan, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['pasien_id'],
            $data['kode_pemeriksaan'],
            $data['jenis_pemeriksaan'],
            $data['nama_dokter'],
            $data['tanggal_pemeriksaan'],
            $data['catatan'] ?? '',
            $data['status'] ?? 'menunggu'
        ]);
    }
    
    public function getPemeriksaanWithResults($id) {
        $sql = "SELECT p.*, ps.nama_lengkap, ps.jenis_kelamin, ps.tanggal_lahir, ps.telepon 
                FROM pemeriksaan p 
                JOIN pasien ps ON p.pasien_id = ps.id 
                WHERE p.id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        $pemeriksaan = $stmt->fetch();
        
        if ($pemeriksaan) {
            // Hasil pemeriksaan
            $sqlResults = "SELECT * FROM hasil_pemeriksaan WHERE pemeriksaan_id = ?";
            $stmtResults = $this->conn->prepare($sqlResults);
            $stmtResults->execute([$id]);
            $results = $stmtResults->fetchAll();
            
            $pemeriksaan['hasil'] = $results;
        }
        
        return $pemeriksaan;
    }
    
    public function updateStatusPemeriksaan($id, $status) {
        $sql = "UPDATE pemeriksaan SET status = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$status, $id]);
    }
}
?>