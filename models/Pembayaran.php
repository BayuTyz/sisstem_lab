<?php

class Pembayaran {
    private $conn;
    
    public function __construct() {
        $this->conn = Database::getInstance();
    }
    
    public function getAllPembayaran($startDate = null, $endDate = null, $limit = null) {
        $sql = "SELECT pb.*, p.kode_pemeriksaan, p.jenis_pemeriksaan, ps.nama_lengkap as nama_pasien, ps.kode_pasien
                FROM pembayaran pb
                JOIN pemeriksaan p ON pb.pemeriksaan_id = p.id
                JOIN pasien ps ON p.pasien_id = ps.id";
        
        $params = [];
        if ($startDate && $endDate) {
            $sql .= " WHERE pb.tanggal_pembayaran BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
        }
        
        $sql .= " ORDER BY pb.tanggal_pembayaran DESC, pb.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT ?";
            $params[] = $limit;
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getPemeriksaanBelumBayar() {
        $sql = "SELECT p.*, ps.nama_lengkap, ps.kode_pasien
                FROM pemeriksaan p
                JOIN pasien ps ON p.pasien_id = ps.id
                WHERE p.status IN ('tervalidasi', 'selesai')
                ORDER BY p.tanggal_pemeriksaan DESC";
        
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll();
    }
    
    public function getStatistikPembayaran() {
        $today = date('Y-m-d');
        
        $sql = "SELECT 
                COALESCE(SUM(CASE WHEN status_pembayaran = 'lunas' THEN total_biaya ELSE 0 END), 0) as total_pendapatan,
                COUNT(CASE WHEN status_pembayaran = 'lunas' THEN 1 END) as jumlah_lunas,
                COUNT(CASE WHEN status_pembayaran = 'belum_lunas' THEN 1 END) as jumlah_belum_lunas,
                COALESCE(AVG(total_biaya), 0) as rata_rata_transaksi,
                COUNT(CASE WHEN DATE(tanggal_pembayaran) = ? AND status_pembayaran = 'lunas' THEN 1 END) as hari_ini
                FROM pembayaran";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$today]);
        return $stmt->fetch();
    }
    
    public function createPembayaran($data) {
        // Generate no transaksi
        $noTransaksi = 'TRX' . date('Ymd') . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        
        $sql = "INSERT INTO pembayaran 
                (pemeriksaan_id, no_transaksi, total_biaya, metode_pembayaran, 
                 uang_diterima, kembalian, tanggal_pembayaran, waktu_pembayaran, 
                 status_pembayaran, catatan, kasir) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $kembalian = $data['uang_diterima'] - $data['total_biaya'];
        
        $stmt = $this->conn->prepare($sql);
        $success = $stmt->execute([
            $data['pemeriksaan_id'],
            $noTransaksi,
            $data['total_biaya'],
            $data['metode_pembayaran'],
            $data['uang_diterima'],
            $kembalian,
            $data['tanggal_pembayaran'],
            $data['waktu_pembayaran'],
            $data['status_pembayaran'] ?? 'lunas',
            $data['catatan'] ?? '',
            $data['kasir'] ?? 'Admin'
        ]);
        
        if ($success) {
            // Update status pemeriksaan
            $this->updateStatusPemeriksaan($data['pemeriksaan_id']);
            
            // Return payment data
            return $this->getPembayaranById($this->conn->lastInsertId());
        }
        
        return false;
    }
    
    public function getPembayaranById($id) {
        $sql = "SELECT pb.*, p.kode_pemeriksaan, p.jenis_pemeriksaan, 
                ps.nama_lengkap as nama_pasien, ps.kode_pasien
                FROM pembayaran pb
                JOIN pemeriksaan p ON pb.pemeriksaan_id = p.id
                JOIN pasien ps ON p.pasien_id = ps.id
                WHERE pb.id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    private function updateStatusPemeriksaan($pemeriksaanId) {
        $sql = "UPDATE pemeriksaan SET status = 'terbayar' WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$pemeriksaanId]);
    }
    
    public function getDataChart($startDate, $endDate) {
        $sql = "SELECT 
                DATE(tanggal_pembayaran) as tanggal,
                SUM(total_biaya) as pendapatan
                FROM pembayaran
                WHERE tanggal_pembayaran BETWEEN ? AND ?
                AND status_pembayaran = 'lunas'
                GROUP BY DATE(tanggal_pembayaran)
                ORDER BY tanggal_pembayaran";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$startDate, $endDate]);
        return $stmt->fetchAll();
    }
    
    public function getDistribusiMetodePembayaran($startDate, $endDate) {
        $sql = "SELECT 
                metode_pembayaran,
                COUNT(*) as jumlah,
                SUM(total_biaya) as total
                FROM pembayaran
                WHERE tanggal_pembayaran BETWEEN ? AND ?
                GROUP BY metode_pembayaran";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$startDate, $endDate]);
        return $stmt->fetchAll();
    }
}
?>