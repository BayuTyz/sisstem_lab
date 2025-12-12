// Tambahkan di initializeRoutes()
'GET' => [
    // ... routes yang sudah ada
    '/api/examination/(\d+)' => 'getExaminationDetail',
    '/api/payment/report' => 'getPaymentReport',
],

'POST' => [
    // ... routes yang sudah ada
    '/api/examination/validate/(\d+)' => 'validateExamination',
    '/api/payment' => 'createPayment',
],

// Tambahkan handler methods
private function getExaminationDetail($id) {
    $this->requireAuth();
    
    $examModel = new Examination();
    $examination = $examModel->getExaminationWithResults($id);
    
    $this->jsonResponse($examination);
}

private function validateExamination($id) {
    $this->requireAuth();
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Simpan hasil ke database
    $conn = Database::getInstance();
    
    try {
        $conn->beginTransaction();
        
        // Update status pemeriksaan
        $sql = "UPDATE examinations SET status = 'validated' WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$id]);
        
        // Simpan hasil
        if (isset($data['results'])) {
            foreach ($data['results'] as $result) {
                $sql = "INSERT INTO examination_results 
                        (examination_id, parameter_name, result_value, normal_range, unit, status) 
                        VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    $id,
                    $result['parameter_name'],
                    $result['result_value'],
                    $result['normal_range'],
                    $result['unit'] ?? '',
                    $result['status']
                ]);
            }
        }
        
        $conn->commit();
        
        $this->jsonResponse([
            'success' => true,
            'message' => 'Pemeriksaan berhasil divalidasi'
        ]);
    } catch(Exception $e) {
        $conn->rollBack();
        $this->jsonResponse([
            'success' => false,
            'message' => 'Gagal validasi: ' . $e->getMessage()
        ], 500);
    }
}

private function createPayment() {
    $this->requireAuth();
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $conn = Database::getInstance();
    $sql = "INSERT INTO payments 
            (examination_id, total_amount, payment_method, payment_date, payment_status, notes) 
            VALUES (?, ?, ?, ?, 'paid', ?)";
    $stmt = $conn->prepare($sql);
    
    $result = $stmt->execute([
        $data['examination_id'],
        $data['total_amount'],
        $data['payment_method'],
        $data['payment_date'],
        $data['notes'] ?? ''
    ]);
    
    $this->jsonResponse([
        'success' => $result,
        'message' => $result ? 'Pembayaran berhasil diproses' : 'Gagal memproses pembayaran'
    ]);
}

private function getPaymentReport() {
    $this->requireAuth();
    
    $startDate = $_GET['start_date'] ?? date('Y-m-01');
    $endDate = $_GET['end_date'] ?? date('Y-m-d');
    
    $conn = Database::getInstance();
    
    // Get transactions
    $sql = "SELECT p.*, e.examination_code, e.examination_type, pt.full_name as patient_name
            FROM payments p
            JOIN examinations e ON p.examination_id = e.id
            JOIN patients pt ON e.patient_id = pt.id
            WHERE p.payment_date BETWEEN ? AND ?
            ORDER BY p.payment_date DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$startDate, $endDate]);
    $transactions = $stmt->fetchAll();
    
    // Get statistics
    $sql = "SELECT 
            SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END) as total_revenue,
            COUNT(CASE WHEN payment_status = 'paid' THEN 1 END) as paid_count,
            COUNT(CASE WHEN payment_status = 'unpaid' THEN 1 END) as unpaid_count,
            AVG(total_amount) as avg_transaction
            FROM payments
            WHERE payment_date BETWEEN ? AND ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$startDate, $endDate]);
    $stats = $stmt->fetch();
    
    // Get chart data (example: last 7 days)
    $sql = "SELECT 
            payment_date as date,
            SUM(total_amount) as revenue
            FROM payments
            WHERE payment_date BETWEEN DATE_SUB(?, INTERVAL 7 DAY) AND ?
            AND payment_status = 'paid'
            GROUP BY payment_date
            ORDER BY payment_date";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$endDate, $endDate]);
    $chartData = $stmt->fetchAll();
    
    // Get payment method distribution
    $sql = "SELECT 
            payment_method,
            COUNT(*) as count,
            SUM(total_amount) as amount
            FROM payments
            WHERE payment_date BETWEEN ? AND ?
            GROUP BY payment_method";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$startDate, $endDate]);
    $methods = $stmt->fetchAll();
    
    $this->jsonResponse([
        'transactions' => $transactions,
        'stats' => $stats,
        'chart_data' => [
            'labels' => array_column($chartData, 'date'),
            'revenue' => array_column($chartData, 'revenue'),
            'method_labels' => array_column($methods, 'payment_method'),
            'method_data' => array_column($methods, 'count')
        ]
    ]);
}