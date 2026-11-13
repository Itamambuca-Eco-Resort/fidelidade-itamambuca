<?php
/**
 * Model Category
 * Gerenciamento de categorias
 */

class Category {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function findAll() {
        $sql = "SELECT c.*, COUNT(p.id) as partners_count
                FROM categories c
                LEFT JOIN partners p ON c.id = p.category_id AND p.is_active = 1
                GROUP BY c.id
                ORDER BY c.name ASC";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    
    public function findById($id) {
        $sql = "SELECT * FROM categories WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }
    
    public function findBySlug($slug) {
        $sql = "SELECT * FROM categories WHERE slug = :slug";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':slug' => $slug]);
        return $stmt->fetch();
    }
}
