<?php
/**
 * Model Favorite
 * Gerenciamento de favoritos
 */

class Favorite {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function add($userId, $partnerId) {
        $sql = "INSERT IGNORE INTO favorites (user_id, partner_id) VALUES (:user_id, :partner_id)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':user_id' => $userId,
            ':partner_id' => $partnerId
        ]);
    }
    
    public function remove($userId, $partnerId) {
        $sql = "DELETE FROM favorites WHERE user_id = :user_id AND partner_id = :partner_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':user_id' => $userId,
            ':partner_id' => $partnerId
        ]);
    }
    
    public function isFavorite($userId, $partnerId) {
        $sql = "SELECT COUNT(*) FROM favorites WHERE user_id = :user_id AND partner_id = :partner_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':partner_id' => $partnerId
        ]);
        return $stmt->fetchColumn() > 0;
    }
    
    public function findByUser($userId) {
        $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug,
                (SELECT url FROM partner_photos WHERE partner_id = p.id AND is_main_photo = 1 LIMIT 1) as main_photo
                FROM favorites f
                INNER JOIN partners p ON f.partner_id = p.id
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE f.user_id = :user_id AND p.is_active = 1
                ORDER BY f.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }
}
