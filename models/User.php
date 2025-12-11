<?php

require_once __DIR__ . '/BaseModel.php';

class User extends BaseModel {
    protected $table = 'users';
    
    public function __construct() {
        parent::__construct();
    }
    public function login($username, $password) {
        $user = $this->findOne('username = :username AND status = "active"', [
            ':username' => $username
        ]);
        
        if ($user && password_verify($password, $user['password'])) {

            $this->update($user['id'], ['last_login' => date('Y-m-d H:i:s')]);
            return $user;
        }
        
        return false;
    }
    
    public function register($data) {

        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['status'] = $data['status'] ?? 'active';
        
        return $this->create($data);
    }
    
    public function getActiveUsers() {
        return $this->getAll('status = "active"');
    }
    
    public function search($keyword) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE username LIKE :keyword 
                OR email LIKE :keyword";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':keyword' => "%$keyword%"]);
        
        return $stmt->fetchAll();
    }
    
}