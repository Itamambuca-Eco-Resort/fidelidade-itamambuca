<?php
require_once __DIR__ . '/config/secrets.php';
/**
 * Conexão dedicada ao banco de dados externo de Recreação.
 * Host separado do banco principal do app de fidelidade.
 *
 * Uso:  require_once 'db_recreation.php';
 *       $pdoRec = getRecreationDb();
 */

function getRecreationDb(): ?PDO {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    try {
        $pdo = new PDO(
            "mysql:host=" . RECREATION_DB_HOST . ";dbname=" . RECREATION_DB_NAME . ";charset=utf8mb4",
            RECREATION_DB_USER,
            RECREATION_DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
    } catch (PDOException $e) {
        $pdo = null;
        error_log('[RecreationDB] Connection failed: ' . $e->getMessage());
    }

    return $pdo;
}