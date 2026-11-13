<?php
/**
 * Model PartnerPhoto
 * Gerenciamento de fotos dos parceiros
 */

class PartnerPhoto {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function create($data) {
        $sql = "INSERT INTO partner_photos (partner_id, url, caption, is_main_photo, display_order)
                VALUES (:partner_id, :url, :caption, :is_main_photo, :display_order)";
        
        $stmt = $this->db->prepare($sql);
        
        $stmt->execute([
            ':partner_id' => $data['partner_id'],
            ':url' => $data['url'],
            ':caption' => $data['caption'] ?? null,
            ':is_main_photo' => $data['is_main_photo'] ?? 0,
            ':display_order' => $data['display_order'] ?? 0
        ]);
        
        return $this->db->lastInsertId();
    }
    
    public function findByPartner($partnerId) {
        $sql = "SELECT * FROM partner_photos 
                WHERE partner_id = :partner_id 
                ORDER BY is_main_photo DESC, display_order ASC, created_at ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':partner_id' => $partnerId]);
        return $stmt->fetchAll();
    }
    
    public function findMainPhoto($partnerId) {
        $sql = "SELECT * FROM partner_photos 
                WHERE partner_id = :partner_id AND is_main_photo = 1 
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':partner_id' => $partnerId]);
        return $stmt->fetch();
    }
    
    public function setMainPhoto($photoId, $partnerId) {
        // Remove main photo flag from all photos
        $sql = "UPDATE partner_photos SET is_main_photo = 0 WHERE partner_id = :partner_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':partner_id' => $partnerId]);
        
        // Set new main photo
        $sql = "UPDATE partner_photos SET is_main_photo = 1 WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $photoId]);
    }
    
    public function delete($id) {
        $sql = "DELETE FROM partner_photos WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
    
    public function updateOrder($photoId, $order) {
        $sql = "UPDATE partner_photos SET display_order = :order WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $photoId,
            ':order' => $order
        ]);
    }
}
