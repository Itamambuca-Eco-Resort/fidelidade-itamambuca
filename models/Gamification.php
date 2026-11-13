<?php
require_once __DIR__ . '/../config/database.php';

class Gamification {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Verifica se o usuário já recebeu o bônus de boas-vindas
     */
    public function hasWelcomeBonus($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT id FROM point_transactions
                WHERE user_id = ? AND action_type = 'welcome_bonus'
                LIMIT 1
            ");
            $stmt->execute([$userId]);
            return (bool) $stmt->fetch();
        } catch (Exception $e) {
            error_log("hasWelcomeBonus error: " . $e->getMessage());
            return false; // em caso de erro, permite tentar conceder
        }
    }

    /**
     * Adicionar pontos ao usuário
     */
    public function addPoints($userId, $points, $actionType, $description = '', $referenceId = null) {
        // 1. Verificar multiplicadores de campanha (apenas para adição de pontos)
        if ($points > 0) {
            try {
                $stmtC = $this->db->prepare("
                    SELECT multiplier FROM point_campaigns 
                    WHERE is_active = 1 AND CURDATE() BETWEEN start_date AND end_date
                    LIMIT 1
                ");
                $stmtC->execute();
                $multiplier = $stmtC->fetchColumn();
                if ($multiplier && $multiplier > 0) {
                    $originalPoints = $points;
                    $points = round($points * $multiplier);
                    if ($points != $originalPoints) {
                        $description .= " (Multiplicador x{$multiplier} ativo!)";
                    }
                }
            } catch (Exception $e) { /* Tabela pode não existir ainda */ }
        }

        $isNested = $this->db->inTransaction();
        try {
            if (!$isNested) $this->db->beginTransaction();
            
            // Registrar transação
            $stmt = $this->db->prepare("
                INSERT INTO point_transactions (user_id, points, action_type, description, reference_id)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $points, $actionType, $description, $referenceId]);
            $transactionId = $this->db->lastInsertId();
            
            // Atualizar pontos totais (cria a linha se não existir)
            $stmt = $this->db->prepare("
                INSERT INTO user_points (user_id, total_points, current_level)
                VALUES (?, ?, 1)
                ON DUPLICATE KEY UPDATE total_points = total_points + ?
            ");
            $stmt->execute([$userId, $points, $points]);
            
            // Tratamento das Expirações
            if ($points > 0) {
                // Se ganhou pontos, registra a validade de 2 anos
                $expiresAt = date('Y-m-d H:i:s', strtotime('+2 years'));
                $stmt = $this->db->prepare("
                    INSERT INTO point_expirations (user_id, transaction_id, points_amount, points_remaining, expires_at)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$userId, $transactionId, $points, $points, $expiresAt]);
            } else if ($points < 0 && $actionType !== 'expired') {
                // Se gastou pontos, consome das expirações ativas mais antigas
                $pointsToConsume = abs($points);
                $stmt = $this->db->prepare("
                    SELECT id, points_remaining 
                    FROM point_expirations 
                    WHERE user_id = ? AND points_remaining > 0 AND expires_at > NOW()
                    ORDER BY expires_at ASC
                ");
                $stmt->execute([$userId]);
                $expirations = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($expirations as $exp) {
                    if ($pointsToConsume <= 0) break;
                    $consume = min($exp['points_remaining'], $pointsToConsume);
                    $pointsToConsume -= $consume;
                    
                    $this->db->prepare("UPDATE point_expirations SET points_remaining = points_remaining - ? WHERE id = ?")
                             ->execute([$consume, $exp['id']]);
                }
            }
            
            // Verificar se deve atualizar o nível (sobe ou desce)
            $this->updateLevel($userId);
            
            // Verificar badges
            $this->checkBadges($userId, $actionType);
            
            if (!$isNested) $this->db->commit();
            return true;
        } catch (Exception $e) {
            if (!$isNested) $this->db->rollBack();
            error_log("Erro ao adicionar pontos: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Atualizar nível do usuário (sobe e desce de acordo com o saldo)
     */
    private function updateLevel($userId) {
        try {
            // Pegar o saldo atual
            $stmt = $this->db->prepare("SELECT total_points, current_level FROM user_points WHERE user_id = ?");
            $stmt->execute([$userId]);
            $userPoints = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$userPoints) return false;
            
            $currentTotal = (int) $userPoints['total_points'];
            $currentLevelId = (int) $userPoints['current_level'];

            // Encontrar o nível correto para o saldo atual
            $stmt = $this->db->prepare("
                SELECT level_number, name 
                FROM levels 
                WHERE min_points <= ? AND max_points >= ?
                ORDER BY level_number DESC LIMIT 1
            ");
            $stmt->execute([$currentTotal, $currentTotal]);
            $newLevelData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($newLevelData) {
                $newLevelId = (int) $newLevelData['level_number'];
                
                // Atualizar no banco caso o nível esteja diferente
                if ($newLevelId !== $currentLevelId) {
                    $stmt = $this->db->prepare("UPDATE user_points SET current_level = ? WHERE user_id = ?");
                    $stmt->execute([$newLevelId, $userId]);
                    
                    // Se subiu de nível, podemos dar os parabéns (sem pontos extras no momento para não bugar o fluxo dinâmico)
                    if ($newLevelId > $currentLevelId) {
                        $this->db->prepare("INSERT INTO point_transactions (user_id, points, action_type, description) VALUES (?, 0, 'level_up', ?)")
                                 ->execute([$userId, "Nível atualizado para: {$newLevelData['name']}"]);
                    }
                    return $newLevelId;
                }
            }
            return false;
        } catch (Exception $e) {
            error_log("Skipping updateLevel (table missing or error): " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verificar e desbloquear badges
     */
    private function checkBadges($userId, $actionType) {
        try {
            // Buscar badges relacionadas à ação
            $stmt = $this->db->prepare("
                SELECT b.* FROM badges b
                WHERE b.is_active = 1
                AND b.id NOT IN (SELECT badge_id FROM user_badges WHERE user_id = ?)
            ");
            $stmt->execute([$userId]);
            $badges = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($badges as $badge) {
            $earned = false;
            
            switch ($badge['requirement_type']) {
                case 'count':
                    $earned = $this->checkCountRequirement($userId, $badge);
                    break;
                case 'unique':
                    $earned = $this->checkUniqueRequirement($userId, $badge);
                    break;
                case 'special':
                    $earned = $this->checkSpecialRequirement($userId, $badge);
                    break;
            }
            
            if ($earned) {
                $this->awardBadge($userId, $badge['id'], $badge['points_reward']);
            }
        }
        } catch (Exception $e) {
            error_log("Skipping checkBadges (table missing or error): " . $e->getMessage());
        }
    }
    
    /**
     * Verificar requisito de contagem
     */
    private function checkCountRequirement($userId, $badge) {
        $count = 0;
        
        switch ($badge['category']) {
            case 'checkin':
                $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM check_ins WHERE user_id = ?");
                $stmt->execute([$userId]);
                $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                break;
                
            case 'social':
                if (strpos($badge['name'], 'Compartilhe') !== false) {
                    $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM shares WHERE user_id = ?");
                    $stmt->execute([$userId]);
                    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                } elseif (strpos($badge['name'], 'avaliações') !== false) {
                    $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM reviews WHERE user_id = ?");
                    $stmt->execute([$userId]);
                    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                } elseif (strpos($badge['name'], 'Indique') !== false) {
                    $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM referrals WHERE referrer_user_id = ? AND status = 'completed'");
                    $stmt->execute([$userId]);
                    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                }
                break;
        }
        
        return $count >= $badge['requirement_value'];
    }
    
    /**
     * Verificar requisito único (ex: visitar todas categorias)
     */
    private function checkUniqueRequirement($userId, $badge) {
        if ($badge['category'] === 'exploration') {
            $stmt = $this->db->prepare("
                SELECT COUNT(DISTINCT p.category_id) as count
                FROM check_ins ci
                JOIN partners p ON ci.partner_id = p.id
                WHERE ci.user_id = ?
            ");
            $stmt->execute([$userId]);
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            return $count >= $badge['requirement_value'];
        }
        
        return false;
    }
    
    /**
     * Verificar requisitos especiais
     */
    private function checkSpecialRequirement($userId, $badge) {
        // Implementar lógica específica para cada badge especial
        return false;
    }
    
    /**
     * Conceder badge ao usuário
     */
    private function awardBadge($userId, $badgeId, $pointsReward) {
        try {
            // Registrar badge
            $stmt = $this->db->prepare("
                INSERT INTO user_badges (user_id, badge_id)
                VALUES (?, ?)
            ");
            $stmt->execute([$userId, $badgeId]);
            
            // Adicionar pontos de recompensa
            if ($pointsReward > 0) {
                $stmt = $this->db->prepare("
                    INSERT INTO point_transactions (user_id, points, action_type, description, reference_id)
                    VALUES (?, ?, 'badge_earned', 'Conquistou uma badge!', ?)
                ");
                $stmt->execute([$userId, $pointsReward, $badgeId]);
                
                $stmt = $this->db->prepare("
                    UPDATE user_points 
                    SET total_points = total_points + ?
                    WHERE user_id = ?
                ");
                $stmt->execute([$pointsReward, $userId]);
            }
            
            // TODO: Enviar notificação de badge conquistada
            
            return true;
        } catch (Exception $e) {
            error_log("Erro ao conceder badge: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Limpar os pontos expirados do usuário e gerar LOG
     */
    public function cleanExpiredPoints($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT id, points_remaining FROM point_expirations 
                WHERE user_id = ? AND points_remaining > 0 AND expires_at <= NOW()
            ");
            $stmt->execute([$userId]);
            $expired = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $totalExpired = 0;
            foreach ($expired as $exp) {
                $totalExpired += $exp['points_remaining'];
                $this->db->prepare("UPDATE point_expirations SET points_remaining = 0 WHERE id = ?")
                         ->execute([$exp['id']]);
            }
            
            if ($totalExpired > 0) {
                // Executa em uma addPoints separada para debitar do saldo e cair o nível
                $this->addPoints($userId, -$totalExpired, 'expired', 'Pontos expirados (após 2 anos sem uso)');
            }
        } catch (Exception $e) { /* silent fail for missing table */ }
    }

    /**
     * Obter pontos e nível do usuário
     */
    public function getUserPoints($userId) {
        $this->cleanExpiredPoints($userId); // Limpa expirações antes de retornar saldo atual

        try {
            // Query simples sem colunas opcionais da tabela levels
            $stmt = $this->db->prepare("
                SELECT up.total_points, up.current_level, up.user_id
                FROM user_points up
                WHERE up.user_id = ?
            ");
            $stmt->execute([$userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                return [
                    'total_points' => 0,
                    'current_level' => 1,
                    'level_name' => 'Explorador',
                    'level_icon' => '🌱',
                    'level_color' => '#6366f1',
                    'points_to_next_level' => 1000
                ];
            }

            // Buscar info do nível separadamente (mais seguro)
            try {
                $stmt = $this->db->prepare("SELECT name, color, max_points, description, icon FROM levels WHERE level_number = ?");
                $stmt->execute([$row['current_level']]);
                $level = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($level) {
                    $row['level_name']  = $level['name'];
                    $row['level_color'] = $level['color'];
                    $row['level_description'] = $level['description'];
                    $row['level_icon'] = $level['icon'];
                    $row['points_to_next_level'] = max(0, $level['max_points'] - $row['total_points']);
                }
            } catch (Exception $e) {
                // levels table might have different schema
            }

            // Fallbacks se a tabela levels falhar
            if (!isset($row['level_name'])) $row['level_name'] = 'Essencial';
            if (!isset($row['level_icon'])) $row['level_icon'] = '🌱';
            if (!isset($row['level_color'])) $row['level_color'] = '#1e4344';
            if (!isset($row['points_to_next_level'])) $row['points_to_next_level'] = 500;

            return $row;
        } catch (Exception $e) {
            error_log("getUserPoints error: " . $e->getMessage());
            return [
                'total_points' => 0,
                'current_level' => 1,
                'level_name' => 'Explorador',
                'level_icon' => '🌱',
                'level_color' => '#6366f1',
                'points_to_next_level' => 1000
            ];
        }
    }
    
    /**
     * Obter histórico de pontos
     */
    public function getPointHistory($userId, $limit = 50) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM point_transactions
                WHERE user_id = ?
                ORDER BY created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$userId, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            error_log("getPointHistory error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter badges do usuário
     */
    public function getUserBadges($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT b.*, ub.earned_at
                FROM user_badges ub
                JOIN badges b ON ub.badge_id = b.id
                WHERE ub.user_id = ?
                ORDER BY ub.earned_at DESC
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            error_log("getUserBadges error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter todas as badges disponíveis
     */
    public function getAllBadges() {
        $stmt = $this->db->prepare("
            SELECT * FROM badges
            WHERE is_active = 1
            ORDER BY rarity DESC, category, requirement_value
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obter progresso do usuário em badges
     */
    public function getBadgeProgress($userId, $badgeId) {
        $stmt = $this->db->prepare("
            SELECT progress FROM user_badges
            WHERE user_id = ? AND badge_id = ?
        ");
        $stmt->execute([$userId, $badgeId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['progress'] : 0;
    }
    
    /**
     * Obter ranking geral
     */
    public function getLeaderboard($limit = 100, $period = 'all') {
        try {
            $query = "
                SELECT 
                    u.id, u.name,
                    up.total_points,
                    up.current_level,
                    l.name as level_name,
                    l.icon as level_icon,
                    (SELECT COUNT(*) FROM user_badges ub WHERE ub.user_id = u.id) as total_badges,
                    (SELECT COUNT(*) FROM check_ins ci WHERE ci.user_id = u.id) as total_checkins
                FROM users u
                LEFT JOIN user_points up ON u.id = up.user_id
                LEFT JOIN levels l ON up.current_level = l.level_number
                ORDER BY up.total_points DESC
                LIMIT ?
            ";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            error_log("getLeaderboard error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter posição do usuário no ranking
     */
    public function getUserRanking($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT ranking_position
                FROM ranking_geral
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['ranking_position'] : '-';
        } catch (Exception $e) {
            error_log("getUserRanking error: " . $e->getMessage());
            return '-';
        }
    }
    
    /**
     * Obter estatísticas do usuário
     */
    public function getUserStats($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM user_stats
                WHERE user_id = ?
            ");
            $stmt->execute([$userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$row) {
                return [
                    'total_checkins' => 0,
                    'total_points_earned' => 0
                ];
            }
            
            return $row;
        } catch (Exception $e) {
            error_log("getUserStats error: " . $e->getMessage());
            return ['total_checkins' => 0, 'total_points_earned' => 0];
        }
    }
    
    /**
     * Registrar check-in
     * Limite: 2 check-ins por atividade por dia (calendário) por usuário
     */
    public function registerCheckin($userId, $partnerId, $verified = false, $latitude = null, $longitude = null, $points = 100) {
        try {
            // 1. Verificar se o parceiro existe e é oficial
            $stmt = $this->db->prepare("SELECT id, name, qr_code FROM partners WHERE id = ? AND is_active = 1");
            $stmt->execute([$partnerId]);
            $partner = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$partner) {
                return ['success' => false, 'message' => 'Atividade não encontrada ou inativa.'];
            }

            // 2. Verificar limite diário (máx 2 check-ins por parceiro)
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM check_ins WHERE user_id = ? AND partner_id = ? AND DATE(created_at) = CURDATE()");
            $stmt->execute([$userId, $partnerId]);
            $dailyCount = (int)$stmt->fetchColumn();

            if ($dailyCount >= 2) {
                return ['success' => false, 'message' => 'Você já atingiu o limite de 2 check-ins diários nesta atividade.'];
            }

            $this->db->beginTransaction();

            // 3. Registrar check-in
            $stmt = $this->db->prepare("
                INSERT INTO check_ins (user_id, partner_id, points_earned, verified, latitude, longitude)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $partnerId, $points, $verified ? 1 : 0, $latitude, $longitude]);
            $checkinId = $this->db->lastInsertId();

            // 4. Adicionar pontos
            $description = ($verified ? "Check-in verificado: " : "Check-in manual: ") . $partner['name'];
            if ($dailyCount === 1) $description .= " (último de hoje)";
            
            $this->addPoints($userId, $points, 'checkin', $description, $partnerId);

            $this->db->commit();

            return [
                'success'       => true,
                'checkin_id'    => $checkinId,
                'points_earned' => $points,
                'partner_name'  => $partner['name']
            ];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log("Erro ao registrar check-in: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao registrar check-in.'];
        }
    }
    
    /**
     * Obter check-ins do usuário
     */
    public function getUserCheckins($userId, $limit = 50) {
        try {
            $stmt = $this->db->prepare("
                SELECT ci.*, p.name as partner_name, p.slug, c.name as category_name
                FROM check_ins ci
                JOIN partners p ON ci.partner_id = p.id
                JOIN categories c ON p.category_id = c.id
                WHERE ci.user_id = ?
                ORDER BY ci.created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$userId, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            error_log("getUserCheckins error: " . $e->getMessage());
            return [];
        }
    }
    /**
     * Obter prêmios de um parceiro
     */
    public function getPartnerRewards($partnerId) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM partner_rewards WHERE partner_id = ? AND is_active = 1");
            $stmt->execute([$partnerId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            error_log("getPartnerRewards error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Resgatar um prêmio
     */
    public function redeemReward($userId, $rewardId) {
        try {
            $this->db->beginTransaction();
            
            // 1. Obter detalhes do prêmio
            $stmt = $this->db->prepare("SELECT * FROM partner_rewards WHERE id = ?");
            $stmt->execute([$rewardId]);
            $reward = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$reward) throw new Exception("Prêmio não encontrado.");
            
            // 2. Verificar pontos do usuário
            $userPoints = $this->getUserPoints($userId);
            if ($userPoints['total_points'] < $reward['points_cost']) {
                throw new Exception("Pontos insuficientes.");
            }
            
            // 3. Gerar código único
            $redemptionCode = strtoupper(substr(uniqid(), -8));
            
            // 4. Registrar resgate
            $stmt = $this->db->prepare("
                INSERT INTO reward_redemptions (user_id, reward_id, redemption_code, points_spent)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $rewardId, $redemptionCode, $reward['points_cost']]);
            
            // 5. Debitar pontos
            $this->addPoints($userId, -$reward['points_cost'], 'reward_redeem', "Resgate de: " . $reward['title'], $rewardId);
            
            // 6. O `addPoints` já chama `updateLevel()` internamente que vai recalcular o nível com os pontos reduzidos.

            $this->db->commit();
            return ['success' => true, 'code' => $redemptionCode];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Verificar código de resgate (Para o Parceiro)
     */
    public function verifyRedemption($code, $partnerId) {
        $stmt = $this->db->prepare("
            SELECT rr.*, r.title, u.name as user_name
            FROM reward_redemptions rr
            JOIN partner_rewards r ON rr.reward_id = r.id
            JOIN users u ON rr.user_id = u.id
            WHERE rr.redemption_code = ? AND r.partner_id = ? AND rr.status = 'pending'
        ");
        $stmt->execute([$code, $partnerId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
