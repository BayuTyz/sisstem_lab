<?php
// models/Patient.php

class Patient {
    private $conn;
    
    public function __construct() {
        $this->conn = Database::getInstance();
    }
    
    public function getAllPatients() {
        $sql = "SELECT * FROM patients ORDER BY created_at DESC";
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll();
    }
    
    public function getPatientById($id) {
        $sql = "SELECT * FROM patients WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function createPatient($data) {
        $sql = "INSERT INTO patients (patient_id, full_name, gender, birth_date, address, phone, email) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['patient_id'],
            $data['full_name'],
            $data['gender'],
            $data['birth_date'],
            $data['address'],
            $data['phone'],
            $data['email']
        ]);
    }
    
    public function updatePatient($id, $data) {
        $sql = "UPDATE patients SET 
                full_name = ?, gender = ?, birth_date = ?, address = ?, phone = ?, email = ? 
                WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['full_name'],
            $data['gender'],
            $data['birth_date'],
            $data['address'],
            $data['phone'],
            $data['email'],
            $id
        ]);
    }
    
    public function deletePatient($id) {
        $sql = "DELETE FROM patients WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$id]);
    }
    
    public function getPatientStats() {
        $sql = "SELECT 
                COUNT(*) as total_pasien,
                (SELECT COUNT(*) FROM patients WHERE DATE(created_at) = CURDATE()) as pasien_baru_hari_ini,
                (SELECT COUNT(*) FROM examinations WHERE DATE(examination_date) = CURDATE()) as pemeriksaan_hari_ini
                FROM patients";
        $stmt = $this->conn->query($sql);
        return $stmt->fetch();
    }
}
?>