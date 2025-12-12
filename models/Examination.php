<?php
// models/Examination.php

class Examination {
    private $conn;
    
    public function __construct() {
        $this->conn = Database::getInstance();
    }
    
    public function getAllExaminations() {
        $sql = "SELECT e.*, p.full_name as patient_name, p.patient_id 
                FROM examinations e 
                JOIN patients p ON e.patient_id = p.id 
                ORDER BY e.examination_date DESC";
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll();
    }
    
    public function createExamination($data) {
        $sql = "INSERT INTO examinations 
                (patient_id, examination_code, examination_type, doctor_name, examination_date, notes) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['patient_id'],
            $data['examination_code'],
            $data['examination_type'],
            $data['doctor_name'],
            $data['examination_date'],
            $data['notes']
        ]);
    }
    
    public function getExaminationWithResults($id) {
        // Data pemeriksaan
        $sql = "SELECT e.*, p.full_name, p.gender, p.birth_date, p.phone 
                FROM examinations e 
                JOIN patients p ON e.patient_id = p.id 
                WHERE e.id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        $examination = $stmt->fetch();
        
        if ($examination) {
            // Hasil pemeriksaan
            $sqlResults = "SELECT * FROM examination_results WHERE examination_id = ?";
            $stmtResults = $this->conn->prepare($sqlResults);
            $stmtResults->execute([$id]);
            $results = $stmtResults->fetchAll();
            
            $examination['results'] = $results;
        }
        
        return $examination;
    }
}
?>