<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/User.php';

session_start();

class Router {
    private $routes = [];
    
    public function __construct() {
        $this->initializeRoutes();
    }
    
    private function initializeRoutes() {
        $this->routes = [
            'GET' => [
                '/' => 'login',
                '/login' => 'login',
                '/dashboard' => 'dashboard',
                '/pasien' => 'pasien',
                '/input-pemeriksaan' => 'inputPemeriksaan',
                '/validasi-hasil' => 'validasiHasil',
                '/pembayaran' => 'pembayaran',
                '/total-biaya' => 'totalBiaya',
                '/logout' => 'logout'
            ],
            
            'POST' => [
                '/api/login' => 'apiLogin',
                '/api/users' => 'apiCreateUser',
                '/api/users/(\d+)' => 'apiUpdateUser',
                '/api/users/delete/(\d+)' => 'apiDeleteUser'
            ]
        ];
    }
    
    public function dispatch() {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        foreach ($this->routes[$method] as $route => $handler) {
            $pattern = str_replace('/', '\/', $route);
            $pattern = '/^' . str_replace('(\d+)', '(\d+)', $pattern) . '$/';
            
            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches);
                return call_user_func_array([$this, $handler], $matches);
            }
        }
        
        $this->notFound();
    }
    
    
    private function login() {
        if ($this->isLoggedIn()) {
            header('Location: /dashboard');
            exit;
        }
        $this->renderView('login.html');
    }
    
    private function dashboard() {
        $this->requireAuth();
        $this->renderView('dashboard.html');
    }
    
    private function pasien() {
        $this->requireAuth();
        $this->renderView('pasien.html');
    }
    
    private function inputPemeriksaan() {
        $this->requireAuth();
        $this->renderView('inputpemeriksaan.html');
    }
    
    private function validasiHasil() {
        $this->requireAuth();
        $this->renderView('validasihasil.html');
    }
    
    private function pembayaran() {
        $this->requireAuth();
        $this->renderView('pembayaran.html');
    }
    
    private function totalBiaya() {
        $this->requireAuth();
        $this->renderView('totalbiaya.html');
    }
    
    private function logout() {
        session_destroy();
        header('Location: /login');
        exit;
    }
    
    private function apiLogin() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['username']) || empty($input['password'])) {
            $this->jsonResponse(['error' => 'Username dan password harus diisi'], 400);
        }
        
        $userModel = new User();
        $user = $userModel->login($input['username'], $input['password']);
        
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['logged_in'] = true;
            
            $this->jsonResponse([
                'success' => true,
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'full_name' => $user['full_name'],
                    'role' => $user['role']
                ]
            ]);
        } else {
            $this->jsonResponse(['error' => 'Username atau password salah'], 401);
        }
    }
    
    private function apiCreateUser() {
        $this->requireAuth();
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $required = ['username', 'password', 'full_name', 'email'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                $this->jsonResponse(['error' => "Field $field harus diisi"], 400);
            }
        }
        
        $userModel = new User();
        $result = $userModel->register([
            'username' => $input['username'],
            'password' => $input['password'],
            'full_name' => $input['full_name'],
            'email' => $input['email'],
            'role' => $input['role'] ?? 'user',
            'phone' => $input['phone'] ?? null
        ]);
        
        if ($result) {
            $this->jsonResponse(['success' => true, 'message' => 'User berhasil dibuat']);
        } else {
            $this->jsonResponse(['error' => 'Gagal membuat user'], 500);
        }
    }
    
    private function apiUpdateUser($id) {
        $this->requireAuth();
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $userModel = new User();
        
        unset($input['id'], $input['created_at']);
        
        if (isset($input['password']) && empty($input['password'])) {
            unset($input['password']);
        }
        
        $result = $userModel->update($id, $input);
        
        if ($result) {
            $this->jsonResponse(['success' => true, 'message' => 'User berhasil diupdate']);
        } else {
            $this->jsonResponse(['error' => 'Gagal mengupdate user'], 500);
        }
    }
    
    private function apiDeleteUser($id) {
        $this->requireAuth();
        
        $userModel = new User();
        $result = $userModel->delete($id);
        
        if ($result) {
            $this->jsonResponse(['success' => true, 'message' => 'User berhasil dihapus']);
        } else {
            $this->jsonResponse(['error' => 'Gagal menghapus user'], 500);
        }
    }
    
    private function renderView($viewFile) {
        $viewPath = __DIR__ . '/views/' . $viewFile;
        
        if (file_exists($viewPath)) {
            readfile($viewPath);
        } else {
            $this->notFound();
        }
    }
    
    private function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    private function requireAuth() {
        if (!$this->isLoggedIn()) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }
    }
    
    private function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    private function notFound() {
        http_response_code(404);
        echo "404 - Halaman tidak ditemukan";
        exit;
    }
}