<?php
/**
 * LaunchpadManager.php
 * Classe principale pour gérer tout le launchpad
 */

class LaunchpadManager {
    
    private $pdo;
    private $user_id;
    private $launchpad_id;
    private $current_step;
    
    public function __construct($pdo, $user_id) {
        $this->pdo = $pdo;
        $this->user_id = $user_id;
        $this->initLaunchpad();
    }
    
    /**
     * Initialise ou charge le launchpad
     */
    private function initLaunchpad() {
        // Chercher un launchpad existant en cours
        $stmt = $this->pdo->prepare("
            SELECT id, current_step FROM launchpad 
            WHERE user_id = ? AND status = 'in_progress'
            LIMIT 1
        ");
        $stmt->execute([$this->user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            $this->launchpad_id = $result['id'];
            $this->current_step = $result['current_step'];
        } else {
            // Créer un nouveau launchpad
            $stmt = $this->pdo->prepare("
                INSERT INTO launchpad (user_id, current_step, status) 
                VALUES (?, 1, 'in_progress')
            ");
            $stmt->execute([$this->user_id]);
            $this->launchpad_id = $this->pdo->lastInsertId();
            $this->current_step = 1;
        }
    }
    
    // ============================================
    // ÉTAPE 1: PROFIL & CONTEXTE
    // ============================================
    
    /**
     * Récupère le profil sauvegardé
     */
    public function getProfile() {
        $stmt = $this->pdo->prepare("SELECT * FROM launchpad_profiles WHERE launchpad_id = ?");
        $stmt->execute([$this->launchpad_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }
    
    /**
     * Sauvegarde le profil (Étape 1)
     */
    public function saveProfile($data) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO launchpad_profiles 
                (launchpad_id, profession, city, zone_type, radius_km, experience_level, main_objective)
                VALUES (?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                profession = VALUES(profession),
                city = VALUES(city),
                zone_type = VALUES(zone_type),
                radius_km = VALUES(radius_km),
                experience_level = VALUES(experience_level),
                main_objective = VALUES(main_objective),
                updated_at = NOW()
            ");
            
            $stmt->execute([
                $this->launchpad_id,
                $data['profession'],
                $data['city'],
                $data['zone_type'],
                $data['radius_km'] ?? null,
                $data['experience_level'],
                $data['main_objective']
            ]);
            
            $this->nextStep();
            return ['success' => true, 'step' => 2];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    // ============================================
    // ÉTAPE 2: NEURO PERSONA
    // ============================================
    
    /**
     * Récupère le persona primaire
     */
    public function getPrimaryPersona() {
        $stmt = $this->pdo->prepare("
            SELECT * FROM launchpad_personas 
            WHERE launchpad_id = ? AND is_primary = TRUE
            LIMIT 1
        ");
        $stmt->execute([$this->launchpad_id]);
        $persona = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($persona) {
            $persona['pain_points'] = json_decode($persona['pain_points'], true) ?? [];
            $persona['desires'] = json_decode($persona['desires'], true) ?? [];
            $persona['triggers'] = json_decode($persona['triggers'], true) ?? [];
        }
        
        return $persona;
    }
    
    /**
     * Récupère tous les personas
     */
    public function getAllPersonas() {
        $stmt = $this->pdo->prepare("
            SELECT * FROM launchpad_personas 
            WHERE launchpad_id = ?
        ");
        $stmt->execute([$this->launchpad_id]);
        $personas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($personas as &$p) {
            $p['pain_points'] = json_decode($p['pain_points'], true) ?? [];
            $p['desires'] = json_decode($p['desires'], true) ?? [];
            $p['triggers'] = json_decode($p['triggers'], true) ?? [];
        }
        
        return $personas;
    }
    
    /**
     * Sauvegarde le persona primaire (Étape 2)
     */
    public function savePrimaryPersona($data) {
        try {
            // D'abord, enlever l'ancien primary
            $stmt = $this->pdo->prepare("
                UPDATE launchpad_personas 
                SET is_primary = FALSE 
                WHERE launchpad_id = ?
            ");
            $stmt->execute([$this->launchpad_id]);
            
            // Insérer ou mettre à jour le persona
            $stmt = $this->pdo->prepare("
                INSERT INTO launchpad_personas 
                (launchpad_id, persona_type, persona_name, consciousness_level, pain_points, desires, triggers, is_primary)
                VALUES (?, ?, ?, ?, ?, ?, ?, TRUE)
                ON DUPLICATE KEY UPDATE
                consciousness_level = VALUES(consciousness_level),
                pain_points = VALUES(pain_points),
                desires = VALUES(desires),
                triggers = VALUES(triggers),
                is_primary = TRUE,
                updated_at = NOW()
            ");
            
            $stmt->execute([
                $this->launchpad_id,
                $data['persona_type'],
                $data['persona_name'],
                $data['consciousness_level'],
                json_encode($data['pain_points']),
                json_encode($data['desires']),
                json_encode($data['triggers'])
            ]);
            
            $this->nextStep();
            return ['success' => true, 'step' => 3];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    // ============================================
    // ÉTAPE 3: PROMESSE & OFFRE
    // ============================================
    
    /**
     * Récupère l'offre sauvegardée
     */
    public function getOffer() {
        $stmt = $this->pdo->prepare("SELECT * FROM launchpad_offers WHERE launchpad_id = ?");
        $stmt->execute([$this->launchpad_id]);
        $offer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($offer) {
            $offer['complementary_offers'] = json_decode($offer['complementary_offers'], true) ?? [];
        }
        
        return $offer;
    }
    
    /**
     * Sauvegarde l'offre (Étape 3)
     */
    public function saveOffer($data) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO launchpad_offers 
                (launchpad_id, promise, offer_title, offer_what, offer_for_whom, offer_why, offer_result, complementary_offers, promise_validated, generated_by_ai)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                promise = VALUES(promise),
                offer_title = VALUES(offer_title),
                offer_what = VALUES(offer_what),
                offer_for_whom = VALUES(offer_for_whom),
                offer_why = VALUES(offer_why),
                offer_result = VALUES(offer_result),
                complementary_offers = VALUES(complementary_offers),
                promise_validated = VALUES(promise_validated),
                updated_at = NOW()
            ");
            
            $stmt->execute([
                $this->launchpad_id,
                $data['promise'],
                $data['offer_title'],
                $data['offer_what'],
                $data['offer_for_whom'],
                $data['offer_why'],
                $data['offer_result'],
                json_encode($data['complementary_offers']),
                true,
                $data['generated_by_ai'] ?? false
            ]);
            
            $this->nextStep();
            return ['success' => true, 'step' => 4];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    // ============================================
    // ÉTAPE 4: STRATÉGIE TRAFIC
    // ============================================
    
    /**
     * Récupère la stratégie sauvegardée
     */
    public function getStrategy() {
        $stmt = $this->pdo->prepare("SELECT * FROM launchpad_strategies WHERE launchpad_id = ?");
        $stmt->execute([$this->launchpad_id]);
        $strategy = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($strategy) {
            $strategy['content_types'] = json_decode($strategy['content_types'], true) ?? [];
            $strategy['local_seo_themes'] = json_decode($strategy['local_seo_themes'], true) ?? [];
            $strategy['pages_to_create'] = json_decode($strategy['pages_to_create'], true) ?? [];
            $strategy['strategy_details'] = json_decode($strategy['strategy_details'], true) ?? [];
        }
        
        return $strategy;
    }
    
    /**
     * Sauvegarde la stratégie (Étape 4)
     */
    public function saveStrategy($data) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO launchpad_strategies 
                (launchpad_id, traffic_choice, traffic_reasoning, content_types, local_seo_themes, pages_to_create, strategy_details)
                VALUES (?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                traffic_choice = VALUES(traffic_choice),
                traffic_reasoning = VALUES(traffic_reasoning),
                content_types = VALUES(content_types),
                local_seo_themes = VALUES(local_seo_themes),
                pages_to_create = VALUES(pages_to_create),
                strategy_details = VALUES(strategy_details),
                updated_at = NOW()
            ");
            
            $stmt->execute([
                $this->launchpad_id,
                $data['traffic_choice'],
                $data['traffic_reasoning'],
                json_encode($data['content_types']),
                json_encode($data['local_seo_themes']),
                json_encode($data['pages_to_create']),
                json_encode($data['strategy_details'])
            ]);
            
            $this->nextStep();
            return ['success' => true, 'step' => 5];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    // ============================================
    // ÉTAPE 5: PLAN FINAL
    // ============================================
    
    /**
     * Récupère le plan final
     */
    public function getFinalPlan() {
        $stmt = $this->pdo->prepare("SELECT * FROM launchpad_final_plans WHERE launchpad_id = ?");
        $stmt->execute([$this->launchpad_id]);
        $plan = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($plan) {
            $plan['plan_content'] = json_decode($plan['plan_content'], true) ?? [];
            $plan['next_actions'] = json_decode($plan['next_actions'], true) ?? [];
        }
        
        return $plan;
    }
    
    /**
     * Sauvegarde le plan final (Étape 5)
     */
    public function saveFinalPlan($data) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO launchpad_final_plans 
                (launchpad_id, plan_content, document_title, document_url, next_actions)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                plan_content = VALUES(plan_content),
                document_title = VALUES(document_title),
                document_url = VALUES(document_url),
                next_actions = VALUES(next_actions),
                updated_at = NOW()
            ");
            
            $stmt->execute([
                $this->launchpad_id,
                json_encode($data['plan_content']),
                $data['document_title'],
                $data['document_url'] ?? null,
                json_encode($data['next_actions'])
            ]);
            
            $this->completeLaunchpad();
            return ['success' => true, 'status' => 'completed'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    // ============================================
    // NAVIGATION
    // ============================================
    
    private function nextStep() {
        if ($this->current_step < 5) {
            $this->current_step++;
            $stmt = $this->pdo->prepare("UPDATE launchpad SET current_step = ? WHERE id = ?");
            $stmt->execute([$this->current_step, $this->launchpad_id]);
        }
    }
    
    public function goToStep($step) {
        if ($step >= 1 && $step <= 5) {
            $this->current_step = $step;
            $stmt = $this->pdo->prepare("UPDATE launchpad SET current_step = ? WHERE id = ?");
            $stmt->execute([$step, $this->launchpad_id]);
        }
    }
    
    private function completeLaunchpad() {
        $stmt = $this->pdo->prepare("UPDATE launchpad SET status = 'completed', current_step = 5 WHERE id = ?");
        $stmt->execute([$this->launchpad_id]);
    }
    
    // ============================================
    // GETTERS
    // ============================================
    
    public function getLaunchpadId() {
        return $this->launchpad_id;
    }
    
    public function getCurrentStep() {
        return $this->current_step;
    }
    
    public function getStatus() {
        $stmt = $this->pdo->prepare("SELECT status FROM launchpad WHERE id = ?");
        $stmt->execute([$this->launchpad_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['status'] ?? 'unknown';
    }
    
    /**
     * Récupère toutes les données pour le résumé
     */
    public function getCompleteSummary() {
        return [
            'profile' => $this->getProfile(),
            'persona' => $this->getPrimaryPersona(),
            'offer' => $this->getOffer(),
            'strategy' => $this->getStrategy(),
            'plan' => $this->getFinalPlan()
        ];
    }
}