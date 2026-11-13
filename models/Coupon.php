<?php
/**
 * Model Coupon
 * Gerenciamento de cupons
 */

class Coupon {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function create($data) {
        $sql = "INSERT INTO coupons (partner_id, code, title, description, discount_type, 
                discount_value, valid_from, valid_until, max_uses, is_active)
                VALUES (:partner_id, :code, :title, :description, :discount_type,
                :discount_value, :valid_from, :valid_until, :max_uses, :is_active)";
        
        $stmt = $this->db->prepare($sql);
        
        $stmt->execute([
            ':partner_id' => $data['partner_id'],
            ':code' => strtoupper($data['code']),
            ':title' => $data['title'],
            ':description' => $data['description'] ?? null,
            ':discount_type' => $data['discount_type'],
            ':discount_value' => $data['discount_value'],
            ':valid_from' => $data['valid_from'],
            ':valid_until' => $data['valid_until'],
            ':max_uses' => $data['max_uses'] ?? null,
            ':is_active' => $data['is_active'] ?? 1
        ]);
        
        return $this->db->lastInsertId();
    }
    
    public function findByPartner($partnerId) {
        $sql = "SELECT * FROM coupons 
                WHERE partner_id = :partner_id 
                ORDER BY created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':partner_id' => $partnerId]);
        return $stmt->fetchAll();
    }
    
    public function findActiveByPartner($partnerId) {
        $sql = "SELECT * FROM coupons 
                WHERE partner_id = :partner_id 
                AND is_active = 1
                AND valid_from <= CURDATE()
                AND valid_until >= CURDATE()
                AND (max_uses IS NULL OR current_uses < max_uses)
                ORDER BY valid_until ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':partner_id' => $partnerId]);
        return $stmt->fetchAll();
    }
    
    public function findById($id) {
        $sql = "SELECT * FROM coupons WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }
    
    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];
        
        $allowedFields = ['code', 'title', 'description', 'discount_type', 'discount_value',
                         'valid_from', 'valid_until', 'max_uses', 'is_active'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                if ($field === 'code') {
                    $fields[] = "$field = :$field";
                    $params[":$field"] = strtoupper($data[$field]);
                } else {
                    $fields[] = "$field = :$field";
                    $params[":$field"] = $data[$field];
                }
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE coupons SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    public function redeem($couponId, $partnerId, $userId = null) {
        // Verificar se o cupom ainda é válido
        $coupon = $this->findById($couponId);
        
        if (!$coupon || !$coupon['is_active']) {
            return false;
        }
        
        if ($coupon['max_uses'] && $coupon['current_uses'] >= $coupon['max_uses']) {
            return false;
        }
        
        // Registrar resgate
        $sql = "INSERT INTO coupon_redemptions (coupon_id, partner_id, user_id) 
                VALUES (:coupon_id, :partner_id, :user_id)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':coupon_id' => $couponId,
            ':partner_id' => $partnerId,
            ':user_id' => $userId
        ]);
        
        // Incrementar contador
        $sql = "UPDATE coupons SET current_uses = current_uses + 1 WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $couponId]);
        
        return true;
    }
    
    public function getRedemptions($partnerId, $limit = 10) {
        $sql = "SELECT cr.*, c.code, c.title, u.name as user_name
                FROM coupon_redemptions cr
                LEFT JOIN coupons c ON cr.coupon_id = c.id
                LEFT JOIN users u ON cr.user_id = u.id
                WHERE cr.partner_id = :partner_id
                ORDER BY cr.redeemed_at DESC
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':partner_id', $partnerId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function delete($id) {
        $sql = "DELETE FROM coupons WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
}
