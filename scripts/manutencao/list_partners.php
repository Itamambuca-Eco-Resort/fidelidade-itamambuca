<?php
require_once __DIR__ . '/../../config/config.php';
$db = Database::getInstance()->getConnection();
$stmt = $db->query("SELECT id, name, user_id, is_active, qr_code FROM partners");
echo "<pre>";
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
echo "</pre>";
