<?php
/**
 * Model User
 * Gerenciamento de usuários
 */

class User {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function create($data) {
        $sql = "INSERT INTO users (name, email, phone, password, role, google_id, avatar) 
                VALUES (:name, :email, :phone, :password, :role, :google_id, :avatar)";
        $stmt = $this->db->prepare($sql);
        
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        
        $stmt->execute([
            ':name'      => $data['name'],
            ':email'     => $data['email'],
            ':phone'     => $data['phone'] ?? null,
            ':password'  => $hashedPassword,
            ':role'      => $data['role'] ?? 'tourist',
            ':google_id' => $data['google_id'] ?? null,
            ':avatar'    => $data['avatar'] ?? null
        ]);
        
        return $this->db->lastInsertId();
    }
    
    public function findById($id) {
        $sql = "SELECT * FROM users WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }
    
    public function findByEmail($email) {
        $sql = "SELECT * FROM users WHERE email = :email";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);
        return $stmt->fetch();
    }
    
    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];
        
        if (isset($data['name'])) {
            $fields[] = "name = :name";
            $params[':name'] = $data['name'];
        }
        
        if (isset($data['email'])) {
            $fields[] = "email = :email";
            $params[':email'] = $data['email'];
        }

        if (isset($data['phone'])) {
            $fields[] = "phone = :phone";
            $params[':phone'] = $data['phone'];
        }

        
        if (isset($data['password'])) {
            $fields[] = "password = :password";
            $params[':password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        if (isset($data['role'])) {
            $fields[] = "role = :role";
            $params[':role'] = $data['role'];
        }

        if (isset($data['google_id'])) {
            $fields[] = "google_id = :google_id";
            $params[':google_id'] = $data['google_id'];
        }

        if (isset($data['avatar'])) {
            $fields[] = "avatar = :avatar";
            $params[':avatar'] = $data['avatar'];
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    public function updateGoogleId($id, $googleId) {
        $sql = "UPDATE users SET google_id = :google_id WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':google_id' => $googleId, ':id' => $id]);
    }
    
    public function verifyPassword($email, $password) {
        $user = $this->findByEmail($email);
        
        if (!$user) {
            return false;
        }
        
        if (password_verify($password, $user['password'])) {
            return $user;
        }
        
        return false;
    }
    
    public function emailExists($email, $excludeId = null) {
        $sql = "SELECT COUNT(*) FROM users WHERE email = :email";
        
        if ($excludeId) {
            $sql .= " AND id != :id";
        }
        
        $stmt = $this->db->prepare($sql);
        $params = [':email' => $email];
        
        if ($excludeId) {
            $params[':id'] = $excludeId;
        }
        
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    }
}
