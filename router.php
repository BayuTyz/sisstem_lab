<?php

class Router {
    private $routes = [];
    
    public function __construct() {
        $this->initializeRoutes();
    }
    
    private function initializeRoutes() {
        $this->routes = [
            'GET' => [
                '/' => 'tampilkanLogin',
                '/login' => 'tampilkanLogin',
                '/dashboard' => 'tampilkanDashboard',
                '/pasien' => 'tampilkanPasien',
            '/inputpemeriksaan' => 'tampilkanInputPemeriksaan',
            '/validashasil' => 'tampilkanValidasiHasil',
                '/pembayaran' => 'tampilkanPembayaran',
                '/total-biaya' => 'tampilkanTotalBiaya',
                '/logout' => 'logout',
                '/api/statistik' => 'getStatistik',
                '/api/pasien' => 'getDataPasien',
                '/api/pengguna' => 'getDataPengguna',
                '/api/pemeriksaan' => 'getDataPemeriksaan', 
            '/api/examinations' => 'getDataPemeriksaan',
                '/api/pemeriksaan/(\d+)' => 'getDetailPemeriksaan',
                '/api/pembayaran/laporan' => 'getLaporanPembayaran',
                '/api/last-patient-code' => 'getLastPatientCode',
                '/api/pemeriksaan/belum-bayar' => 'getPemeriksaanBelumBayar',
            '/api/pembayaran/recent' => 'getRecentPayments',
            '/api/pembayaran/statistik' => 'getPaymentStatistics',
            '/api/pemeriksaan/(\d+)/detail' => 'getDetailPemeriksaan',
            '/api/pembayaran/(\d+)/receipt' => 'getPaymentReceipt',
            '/api/last-exam-code' => 'getLastExamCode',
            '/api/pending-examinations' => 'getPendingExaminations',
            '/api/completed-payments' => 'getCompletedPayments',
            '/api/payment-statistics' => 'getPaymentStatistics',
            '/api/payment-chart' => 'getPaymentChartData',
            '/api/payment-methods' => 'getPaymentMethodsDistribution',
            '/api/unpaid-examinations' => 'getUnpaidExaminations'
            ],
            
            'POST' => [
                '/login' => 'prosesLogin',
                '/api/login' => 'apiLogin',
                '/api/pasien' => 'apiTambahPasien',
                '/api/pasien/(\d+)' => 'apiUpdatePasien',
                '/api/pasien/hapus/(\d+)' => 'apiHapusPasien',
                '/api/pemeriksaan' => 'apiTambahPemeriksaan',
                '/api/pemeriksaan/validasi/(\d+)' => 'apiValidasiPemeriksaan',
                '/api/pengguna' => 'apiTambahPengguna',
                '/api/pengguna/(\d+)' => 'apiUpdatePengguna',
                '/api/pengguna/hapus/(\d+)' => 'apiHapusPengguna',
                '/api/pembayaran' => 'apiTambahPembayaran','/api/pemeriksaan/hapus/(\d+)' => 'apiHapusPemeriksaan',
            '/api/pemeriksaan/update/(\d+)' => 'apiUpdatePemeriksaan',
            '/api/pasien/update/(\d+)' => 'apiUpdatePasien',
            '/api/pasien/hapus/(\d+)' => 'apiHapusPasien',
            '/api/pembayaran/update/(\d+)' => 'apiUpdatePembayaran',
            '/api/pembayaran/hapus/(\d+)' => 'apiHapusPembayaran',
            '/api/pengguna/create' => 'apiTambahPengguna',
            '/api/pengguna/update/(\d+)' => 'apiUpdatePengguna',
            '/api/pengguna/hapus/(\d+)' => 'apiHapusPengguna'
            ]
        ];
    }
    
    public function dispatch() {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Remove base path jika ada
        $base_path = '/medilab';
        if (strpos($uri, $base_path) === 0) {
            $uri = substr($uri, strlen($base_path));
        }
        
        foreach ($this->routes[$method] as $route => $handler) {
            if (strpos($route, '(') !== false) {
                $pattern = str_replace('/', '\/', $route);
                $pattern = '/^' . str_replace('(\d+)', '(\d+)', $pattern) . '$/';
                
                if (preg_match($pattern, $uri, $matches)) {
                    array_shift($matches);
                    return call_user_func_array([$this, $handler], $matches);
                }
            } elseif ($uri === $route) {
                return $this->$handler();
            }
        }
        
        $this->halamanTidakDitemukan();
    }
    
    // === HANDLER METHODS ===
    
    private function tampilkanLogin() {
        if ($this->sudahLogin()) {
            header('Location: /dashboard');
            exit;
        }
        $this->renderView('login.html');
    }
    
    private function prosesLogin() {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $_SESSION['error'] = 'Username dan password harus diisi';
            header('Location: /login');
            exit;
        }
        
        $penggunaModel = new Pengguna();
        $pengguna = $penggunaModel->login($username, $password);
        
        if ($pengguna) {
            $_SESSION['user_id'] = $pengguna['id'];
            $_SESSION['username'] = $pengguna['username'];
            $_SESSION['nama_lengkap'] = $pengguna['nama_lengkap'];
            $_SESSION['peran'] = $pengguna['peran'];
            $_SESSION['logged_in'] = true;
            
            header('Location: /dashboard');
            exit;
        } else {
            $_SESSION['error'] = 'Username atau password salah';
            header('Location: /login');
            exit;
        }
    }
    
    private function tampilkanDashboard() {
        $this->requireAuth();
        
        $pasienModel = new Pasien();
        $statistik = $pasienModel->getStatistikPasien();
        
        $pemeriksaanModel = new Pemeriksaan();
        $pemeriksaan = $pemeriksaanModel->getAllPemeriksaan();
        $pemeriksaan = array_slice($pemeriksaan, 0, 5);
        
        $pasien = $pasienModel->getAllPasien();
        $pasien = array_slice($pasien, 0, 5);
        
        $viewPath = __DIR__ . '/views/dashboard.html';
        if (file_exists($viewPath)) {
            extract([
                'statistik' => $statistik,
                'pasien' => $pasien,
                'pemeriksaan' => $pemeriksaan
            ]);
            include $viewPath;
        } else {
            echo "Dashboard tidak ditemukan";
        }
    }
    
    private function tampilkanPasien() {
        $this->requireAuth();
        $this->renderView('pasien.html');
    }
    
    private function tampilkanInputPemeriksaan() {
        $this->requireAuth();
        $this->renderView('input_pemeriksaan.html');
    }
    
    private function tampilkanValidasiHasil() {
        $this->requireAuth();
        $this->renderView('validasi_hasil.html');
    }
    
    private function tampilkanPembayaran() {
        $this->requireAuth();
        $this->renderView('pembayaran.html');
    }
    
    private function tampilkanTotalBiaya() {
        $this->requireAuth();
        $this->renderView('total_biaya.html');
    }
    
    private function getStatistik() {
        $this->requireAuth();
        
        $pasienModel = new Pasien();
        $statistik = $pasienModel->getStatistikPasien();
        
        $pemeriksaanModel = new Pemeriksaan();
        $pemeriksaan = $pemeriksaanModel->getAllPemeriksaan();
        
        $statistik['total_pemeriksaan'] = count($pemeriksaan);
        $statistik['pending_validasi'] = 0;
        $statistik['total_pendapatan'] = '12.5M';
        
        $this->jsonResponse($statistik);
    }
    private function getPemeriksaanBelumBayar() {
    $this->requireAuth();
    
    $pembayaranModel = new Pembayaran();
    $examinations = $pembayaranModel->getPemeriksaanBelumBayar();
    
    $this->jsonResponse([
        'success' => true,
        'data' => $examinations
    ]);
}

private function getRecentPayments() {
    $this->requireAuth();
    
    $pembayaranModel = new Pembayaran();
    $payments = $pembayaranModel->getAllPembayaran(null, null, 10);
    
    $this->jsonResponse([
        'success' => true,
        'data' => $payments
    ]);
}

private function getPaymentStatistics() {
    $this->requireAuth();
    
    $pembayaranModel = new Pembayaran();
    $statistics = $pembayaranModel->getStatistikPembayaran();
    
    $this->jsonResponse([
        'success' => true,
        'data' => $statistics
    ]);
}

private function getPaymentReceipt($id) {
    $this->requireAuth();
    
    $pembayaranModel = new Pembayaran();
    $payment = $pembayaranModel->getPembayaranById($id);
    
    if ($payment) {
        $this->jsonResponse([
            'success' => true,
            'data' => $payment
        ]);
    } else {
        $this->jsonResponse([
            'success' => false,
            'message' => 'Pembayaran tidak ditemukan'
        ], 404);
    }
}
    private function getDataPasien() {
        $this->requireAuth();
        
        $pasienModel = new Pasien();
        $pasien = $pasienModel->getAllPasien();
        
        $this->jsonResponse($pasien);
    }
    
    private function getDataPemeriksaan() {
        $this->requireAuth();
        
        $pemeriksaanModel = new Pemeriksaan();
        $pemeriksaan = $pemeriksaanModel->getAllPemeriksaan();
        
        $this->jsonResponse($pemeriksaan);
    }
    private function apiTambahPasien() {
    $this->requireAuth();
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $pasienModel = new Pasien();
    $result = $pasienModel->createPasien($data);
    
    $this->jsonResponse([
        'sukses' => $result,
        'pesan' => $result ? 'Pasien berhasil ditambahkan' : 'Gagal menambahkan pasien',
        'data' => $data
    ]);
}
private function apiTambahPembayaran() {
    $this->requireAuth();
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $pembayaranModel = new Pembayaran();
    $result = $pembayaranModel->createPembayaran($data);
    
    if ($result) {
        // Update status pemeriksaan menjadi 'terbayar'
        $pemeriksaanModel = new Pemeriksaan();
        $pemeriksaanModel->updateStatusPemeriksaan($data['pemeriksaan_id'], 'terbayar');
    }
    
    $this->jsonResponse([
        'sukses' => $result,
        'pesan' => $result ? 'Pembayaran berhasil disimpan' : 'Gagal menyimpan pembayaran'
    ]);
}

private function getPendingExaminations() {
    $this->requireAuth();
    
    $pemeriksaanModel = new Pemeriksaan();
    $examinations = $pemeriksaanModel->getAllPemeriksaan();
    
    // Filter hanya yang belum dibayar
    $pending = array_filter($examinations, function($exam) {
        return $exam['status'] === 'tervalidasi' || $exam['status'] === 'selesai';
    });
    
    $this->jsonResponse(array_values($pending));
}

private function getCompletedPayments() {
    $this->requireAuth();
    
    $pembayaranModel = new Pembayaran();
    $payments = $pembayaranModel->getAllPembayaran();
    
    $this->jsonResponse($payments);
}

private function getPaymentChartData() {
    $this->requireAuth();
    
    $startDate = $_GET['start_date'] ?? date('Y-m-01');
    $endDate = $_GET['end_date'] ?? date('Y-m-d');
    
    $pembayaranModel = new Pembayaran();
    $chartData = $pembayaranModel->getDataChart($startDate, $endDate);
    
    $this->jsonResponse($chartData);
}

private function getPaymentMethodsDistribution() {
    $this->requireAuth();
    
    $startDate = $_GET['start_date'] ?? date('Y-m-01');
    $endDate = $_GET['end_date'] ?? date('Y-m-d');
    
    $pembayaranModel = new Pembayaran();
    $distribution = $pembayaranModel->getDistribusiMetodePembayaran($startDate, $endDate);
    
    $this->jsonResponse($distribution);
}

private function getUnpaidExaminations() {
    $this->requireAuth();
    
    $pemeriksaanModel = new Pemeriksaan();
    $examinations = $pemeriksaanModel->getAllPemeriksaan();
    
    // Filter yang belum dibayar
    $unpaid = array_filter($examinations, function($exam) {
        return $exam['status'] !== 'terbayar';
    });
    
    $this->jsonResponse(array_values($unpaid));
}
private function apiUpdatePasien($id) {
    $this->requireAuth();
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $pasienModel = new Pasien();
    $result = $pasienModel->updatePasien($id, $data);
    
    $this->jsonResponse([
        'sukses' => $result,
        'pesan' => $result ? 'Pasien berhasil diperbarui' : 'Gagal memperbarui pasien'
    ]);
}

private function apiHapusPasien($id) {
    $this->requireAuth();
    
    $pasienModel = new Pasien();
    $result = $pasienModel->deletePasien($id);
    
    $this->jsonResponse([
        'sukses' => $result,
        'pesan' => $result ? 'Pasien berhasil dihapus' : 'Gagal menghapus pasien'
    ]);
}
    private function getDetailPemeriksaan($id) {
        $this->requireAuth();
        
        $pemeriksaanModel = new Pemeriksaan();
        $pemeriksaan = $pemeriksaanModel->getPemeriksaanWithResults($id);
        
        $this->jsonResponse($pemeriksaan);
    }
private function apiTambahPemeriksaan() {
    $this->requireAuth();
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validasi data yang diperlukan
    if (!$this->validatePemeriksaanData($data)) {
        $this->jsonResponse([
            'sukses' => false,
            'pesan' => 'Data pemeriksaan tidak lengkap. Semua field wajib diisi.'
        ], 400);
        return;
    }
    
    // Cek apakah pasien sudah ada berdasarkan kode_pasien
    $pasienModel = new Pasien();
    $pasien = $pasienModel->getPasienByKode($data['kode_pasien']);
    
    if (!$pasien) {
        // Jika pasien belum ada, buat baru
        $pasienData = [
            'kode_pasien' => $data['kode_pasien'],
            'nama_lengkap' => $data['nama_lengkap'],
            'jenis_kelamin' => $data['jenis_kelamin'],
            'tanggal_lahir' => $data['tanggal_lahir'],
            'alamat' => $data['alamat'],
            'telepon' => $data['telepon'],
            'email' => $data['email'] ?? ''
        ];
        
        // Validasi data pasien sebelum insert
        if (!$this->validatePasienData($pasienData)) {
            $this->jsonResponse([
                'sukses' => false,
                'pesan' => 'Data pasien tidak valid. Pastikan semua field diisi dengan benar.'
            ], 400);
            return;
        }
        
        // Simpan pasien baru
        $pasienCreated = $pasienModel->createPasien($pasienData);
        
        if (!$pasienCreated) {
            $this->jsonResponse([
                'sukses' => false,
                'pesan' => 'Gagal menyimpan data pasien baru.'
            ], 500);
            return;
        }
        
        // Ambil data pasien yang baru dibuat
        $pasien = $pasienModel->getPasienByKode($data['kode_pasien']);
        
        if (!$pasien) {
            $this->jsonResponse([
                'sukses' => false,
                'pesan' => 'Gagal mengambil data pasien setelah penyimpanan.'
            ], 500);
            return;
        }
    } else {
        // Jika pasien sudah ada, update data jika diperlukan
        $pasienData = [
            'nama_lengkap' => $data['nama_lengkap'],
            'jenis_kelamin' => $data['jenis_kelamin'],
            'tanggal_lahir' => $data['tanggal_lahir'],
            'alamat' => $data['alamat'],
            'telepon' => $data['telepon'],
            'email' => $data['email'] ?? ''
        ];
        
        $pasienModel->updatePasien($pasien['id'], $pasienData);
    }
    
    // Cek apakah kode pemeriksaan sudah ada
    if (!$this->checkKodePemeriksaan($data['kode_pemeriksaan'])) {
        $this->jsonResponse([
            'sukses' => false,
            'pesan' => 'Kode pemeriksaan sudah digunakan. Silakan gunakan kode lain.'
        ], 400);
        return;
    }
    
    // Simpan pemeriksaan
    $pemeriksaanModel = new Pemeriksaan();
    $result = $pemeriksaanModel->createPemeriksaan([
        'pasien_id' => $pasien['id'],
        'kode_pemeriksaan' => $data['kode_pemeriksaan'],
        'jenis_pemeriksaan' => $data['jenis_pemeriksaan'],
        'nama_dokter' => $data['nama_dokter'],
        'tanggal_pemeriksaan' => $data['tanggal_pemeriksaan'],
        'catatan' => $data['catatan'] ?? '',
        'status' => $data['status'] ?? 'menunggu'
    ]);
    
    if ($result) {
        $this->jsonResponse([
            'sukses' => true,
            'pesan' => 'Data pemeriksaan dan pasien berhasil disimpan.',
            'data' => [
                'pasien_id' => $pasien['id'],
                'kode_pasien' => $pasien['kode_pasien'],
                'kode_pemeriksaan' => $data['kode_pemeriksaan']
            ]
        ]);
    } else {
        $this->jsonResponse([
            'sukses' => false,
            'pesan' => 'Gagal menyimpan data pemeriksaan.'
        ], 500);
    }
}

// Tambahkan method helper untuk validasi
private function validatePemeriksaanData($data) {
    $requiredFields = [
        'kode_pasien',
        'nama_lengkap',
        'jenis_kelamin',
        'tanggal_lahir',
        'alamat',
        'telepon',
        'kode_pemeriksaan',
        'jenis_pemeriksaan',
        'nama_dokter',
        'tanggal_pemeriksaan'
    ];
    
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            return false;
        }
    }
    
    return true;
}

private function validatePasienData($data) {
    $requiredFields = [
        'kode_pasien',
        'nama_lengkap',
        'jenis_kelamin',
        'tanggal_lahir',
        'alamat',
        'telepon'
    ];
    
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            return false;
        }
    }
    
    // Validasi format telepon
    if (!preg_match('/^[0-9]{10,13}$/', preg_replace('/\D/', '', $data['telepon']))) {
        return false;
    }
    
    return true;
}

private function checkKodePemeriksaan($kode) {
    $conn = Database::getInstance();
    $sql = "SELECT COUNT(*) as count FROM pemeriksaan WHERE kode_pemeriksaan = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$kode]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result['count'] == 0;
}
    private function apiValidasiPemeriksaan($id) {
        $this->requireAuth();
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        $conn = Database::getInstance();
        
        try {
            $conn->beginTransaction();
            
            $sql = "UPDATE pemeriksaan SET status = 'tervalidasi' WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$id]);
            
            if (isset($data['hasil'])) {
                foreach ($data['hasil'] as $hasil) {
                    $sql = "INSERT INTO hasil_pemeriksaan 
                            (pemeriksaan_id, nama_parameter, nilai_hasil, nilai_normal, satuan, status) 
                            VALUES (?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([
                        $id,
                        $hasil['nama_parameter'],
                        $hasil['nilai_hasil'],
                        $hasil['nilai_normal'],
                        $hasil['satuan'] ?? '',
                        $hasil['status']
                    ]);
                }
            }
            
            $conn->commit();
            
            $this->jsonResponse([
                'sukses' => true,
                'pesan' => 'Pemeriksaan berhasil divalidasi'
            ]);
        } catch(Exception $e) {
            $conn->rollBack();
            $this->jsonResponse([
                'sukses' => false,
                'pesan' => 'Gagal validasi: ' . $e->getMessage()
            ], 500);
        }
    }
    
    private function logout() {
        session_destroy();
        header('Location: /login');
        exit;
    }
    
    // === HELPER METHODS ===
    
    private function renderView($viewFile) {
        $viewPath = __DIR__ . '/views/' . $viewFile;
        
        if (file_exists($viewPath)) {
            include $viewPath;
        } else {
            echo "<h1>View tidak ditemukan: $viewFile</h1>";
            $this->halamanTidakDitemukan();
        }
    }
    
    private function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    private function requireAuth() {
        if (!$this->sudahLogin()) {
            header('Location: /login');
            exit;
        }
    }
    
    private function sudahLogin() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    private function halamanTidakDitemukan() {
        http_response_code(404);
        echo '<!DOCTYPE html>
        <html>
        <head><title>404 Tidak Ditemukan</title></head>
        <body style="font-family: Arial; text-align: center; padding: 50px;">
            <h1>404 - Halaman tidak ditemukan</h1>
            <p>URL: ' . $_SERVER['REQUEST_URI'] . '</p>
            <p>Method: ' . $_SERVER['REQUEST_METHOD'] . '</p>
            <a href="/login">Kembali ke Login</a>
        </body>
        </html>';
        exit;
    }
}
?>