<?php
// models/Pasien.php

require_once __DIR__ . '/BaseModel.php';

class Pasien extends BaseModel {
    protected $table = 'pasien';
    
    public function __construct() {
        parent::__construct();
        require_once dirname(__DIR__) . '/config/database.php';
        $this->db = Database::getInstance();
    }
    
    // Get total pasien
    public function getTotal() {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['total'] ?? 0;
    }
    
    // Get recent patients
    public function getRecent($limit = 5) {
        $sql = "SELECT * FROM {$this->table} ORDER BY created_at DESC LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}

// Model untuk pemeriksaan
class Pemeriksaan extends BaseModel {
    protected $table = 'pemeriksaan';
    
    public function __construct() {
        parent::__construct();
        require_once dirname(__DIR__) . '/config/database.php';
        $this->db = Database::getInstance();
    }
    
    // Get total pemeriksaan
    public function getTotal() {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['total'] ?? 0;
    }
    
    // Get pending validation
    public function getPending() {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE status = 'pending'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['total'] ?? 0;
    }
}