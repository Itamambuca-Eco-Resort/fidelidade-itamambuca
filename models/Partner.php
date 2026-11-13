<?php
/**
 * Model Partner
 * Gerenciamento de parceiros
 */

class Partner {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function create($data) {
        $sql = "INSERT INTO partners (user_id, category_id, name, slug, short_description, long_description, 
                is_featured, is_active, qr_code) 
                VALUES (:user_id, :category_id, :name, :slug, :short_description, :long_description,
                :is_featured, :is_active, :qr_code)";
        
        $stmt = $this->db->prepare($sql);
        
        $stmt->execute([
            ':user_id' => $data['user_id'],
            ':category_id' => $data['category_id'],
            ':name' => $data['name'],
            ':slug' => $data['slug'],
            ':short_description' => $data['short_description'] ?? '',
            ':long_description' => $data['long_description'] ?? null,
            ':is_featured' => $data['is_featured'] ?? 0,
            ':is_active' => $data['is_active'] ?? 1,
            ':qr_code' => $data['qr_code'] ?? null
        ]);
        
        return $this->db->lastInsertId();
    }
    
    public function findAll($filters = []) {
        $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug,
                (SELECT url FROM partner_photos WHERE partner_id = p.id AND is_main_photo = 1 LIMIT 1) as main_photo
                FROM partners p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.is_active = 1";
        
        $params = [];
        
        if (isset($filters['category_id'])) {
            $sql .= " AND p.category_id = :category_id";
            $params[':category_id'] = $filters['category_id'];
        }
        
        if (isset($filters['search'])) {
            $sql .= " AND (p.name LIKE :search OR p.short_description LIKE :search OR p.city LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        if (isset($filters['featured'])) {
            $sql .= " AND p.is_featured = :featured";
            $params[':featured'] = $filters['featured'];
        }
        
        $sql .= " ORDER BY p.is_featured DESC, p.name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function findById($id) {
        $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug
                FROM partners p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }
    
    public function findBySlug($slug) {
        $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug
                FROM partners p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.slug = :slug AND p.is_active = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':slug' => $slug]);
        return $stmt->fetch();
    }
    
    public function findByUserId($userId) {
        $sql = "SELECT p.*, c.name as category_name
                FROM partners p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.user_id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }
    
    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];
        
        $allowedFields = ['category_id', 'name', 'slug', 'short_description', 'long_description',
                         'is_featured', 'is_active', 'qr_code'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE partners SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    public function delete($id) {
        $sql = "DELETE FROM partners WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
    
    public function recordView($partnerId, $userId = null, $ipAddress = null, $referrer = null) {
        $sql = "INSERT INTO partner_views (partner_id, user_id, ip_address, referrer) 
                VALUES (:partner_id, :user_id, :ip_address, :referrer)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':partner_id' => $partnerId,
            ':user_id' => $userId,
            ':ip_address' => $ipAddress,
            ':referrer' => $referrer
        ]);
    }
    
    public function getViewsCount($partnerId, $days = 30) {
        $sql = "SELECT COUNT(*) FROM partner_views 
                WHERE partner_id = :partner_id 
                AND viewed_at >= DATE_SUB(NOW(), INTERVAL :days DAY)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':partner_id' => $partnerId,
            ':days' => $days
        ]);
        return $stmt->fetchColumn();
    }
    
    public function getViewsStats($partnerId, $days = 30) {
        $sql = "SELECT DATE(viewed_at) as date, COUNT(*) as count
                FROM partner_views
                WHERE partner_id = :partner_id
                AND viewed_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
                GROUP BY DATE(viewed_at)
                ORDER BY date ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':partner_id' => $partnerId,
            ':days' => $days
        ]);
        return $stmt->fetchAll();
    }
}
