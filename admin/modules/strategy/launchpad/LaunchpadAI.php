<?php
/**
 * LaunchpadAI.php
 * Gère les appels à l'API Claude pour la génération IA
 */

class LaunchpadAI {
    
    private $api_key;
    private $api_url = 'https://api.anthropic.com/v1/messages';
    private $model = 'claude-sonnet-4-20250514';
    private $pdo;
    private $launchpad_id;
    
    public function __construct($pdo, $launchpad_id, $api_key) {
        $this->pdo = $pdo;
        $this->launchpad_id = $launchpad_id;
        $this->api_key = $api_key;
    }
    
    /**
     * Génère une promesse basée sur profil + persona
     */
    public function generatePromise($profile, $persona) {
        $prompt = $this->buildPromptForPromise($profile, $persona);
        $response = $this->callClaude($prompt);
        
        if ($response['success']) {
            $this->logAICall(3, $prompt, $response['content']);
            return [
                'success' => true,
                'promise' => trim($response['content'])
            ];
        }
        
        return ['success' => false, 'error' => $response['error']];
    }
    
    /**
     * Génère une offre complète
     */
    public function generateOffer($profile, $persona, $promise) {
        $prompt = $this->buildPromptForOffer($profile, $persona, $promise);
        $response = $this->callClaude($prompt);
        
        if ($response['success']) {
            $this->logAICall(3, $prompt, $response['content']);
            
            preg_match('/```json\n(.*?)\n```/s', $response['content'], $matches);
            $json_str = $matches[1] ?? $response['content'];
            $offer = json_decode($json_str, true);
            
            if ($offer) {
                return ['success' => true, 'offer' => $offer];
            }
        }
        
        return ['success' => false, 'error' => 'Failed to generate valid offer'];
    }
    
    /**
     * Recommande une stratégie trafic avec justification
     */
    public function recommendStrategy($profile, $persona, $offer) {
        $prompt = $this->buildPromptForStrategy($profile, $persona, $offer);
        $response = $this->callClaude($prompt);
        
        if ($response['success']) {
            $this->logAICall(4, $prompt, $response['content']);
            
            preg_match('/```json\n(.*?)\n```/s', $response['content'], $matches);
            $json_str = $matches[1] ?? $response['content'];
            $strategy = json_decode($json_str, true);
            
            if ($strategy) {
                return ['success' => true, 'strategy' => $strategy];
            }
        }
        
        return ['success' => false, 'error' => 'Failed to generate valid strategy'];
    }
    
    /**
     * Génère le cahier stratégique final
     */
    public function generateFinalPlan($summary) {
        $prompt = $this->buildPromptForFinalPlan($summary);
        $response = $this->callClaude($prompt);
        
        if ($response['success']) {
            $this->logAICall(5, $prompt, $response['content']);
            
            preg_match('/```json\n(.*?)\n```/s', $response['content'], $matches);
            $json_str = $matches[1] ?? $response['content'];
            $plan = json_decode($json_str, true);
            
            if ($plan) {
                return ['success' => true, 'plan' => $plan];
            }
        }
        
        return ['success' => false, 'error' => 'Failed to generate valid plan'];
    }
    
    // ============================================
    // CONSTRUCTION DES PROMPTS
    // ============================================
    
    private function buildPromptForPromise($profile, $persona) {
        // Extraire les implode() AVANT le heredoc
        $painPoints = implode(', ', $persona['pain_points'] ?? []);
        $desires = implode(', ', $persona['desires'] ?? []);
        
        return <<<PROMPT
Tu es un expert en stratégie marketing immobilier. Tu dois générer une PROMESSE CLAIRE en format court.

PROFIL DE LA PERSONNE:
- Métier: {$profile['profession']}
- Zone: {$profile['city']}
- Expérience: {$profile['experience_level']}
- Objectif: {$profile['main_objective']}

PERSONA CIBLE:
- Type: {$persona['persona_name']}
- Niveau de conscience: {$persona['consciousness_level']}
- Points douleur: {$painPoints}
- Désirs: {$desires}

CONSIGNES:
Génère UNE SEULE promesse au format:
"J'aide [persona] à [résultat concret] à [lieu] sans [problème principal]"

Exemple bon:
"J'aide les vendeurs pressés à vendre rapidement à Bordeaux sans passer par une agence."

RÉPONSE (juste la promesse, pas d'explication):
PROMPT;
    }
    
    private function buildPromptForOffer($profile, $persona, $promise) {
        return <<<PROMPT
Tu es un expert en stratégie marketing immobilier et copywriting.

PROMISE VALIDÉE:
{$promise}

CONTEXTE:
- Métier: {$profile['profession']}
- Zone: {$profile['city']}
- Persona: {$persona['persona_name']}

TÂCHE: Génère une OFFRE STRUCTURÉE en JSON avec:
1. ce_que_tu_fais: Ce que tu fais concrètement
2. pour_qui: Le persona exact avec détails
3. pourquoi_toi: Ton avantage unique
4. resultat_final: Le résultat que tu garantis
5. offres_complementaires: 2-3 options additionnelles possibles

FORMAT ATTENDU:
```json
{
  "ce_que_tu_fais": "description détaillée",
  "pour_qui": "description du persona",
  "pourquoi_toi": "avantage unique",
  "resultat_final": "résultat garanti",
  "offres_complementaires": [
    {"nom": "Offre 1", "description": "..."},
    {"nom": "Offre 2", "description": "..."}
  ]
}
```

RÉPONDS UNIQUEMENT AVEC LE JSON VALIDE:
PROMPT;
    }
    
    private function buildPromptForStrategy($profile, $persona, $offer) {
        $consciousness = $persona['consciousness_level'] ?? 'medium';
        $consciousnessLabels = [
            'low' => 'faible (ne connaît pas le problème)',
            'medium' => 'moyen (connaît le problème)',
            'high' => 'élevé (cherche activement une solution)'
        ];
        $consciousness_text = $consciousnessLabels[$consciousness] ?? 'moyen';
        $offerDesc = $offer['ce_que_tu_fais'] ?? '';
        
        return <<<PROMPT
Tu es un expert en stratégie marketing immobilier. Tu recommandes le MEILLEUR CANAL DE TRAFIC.

CONTEXTE:
- Profession: {$profile['profession']}
- Zone: {$profile['city']}
- Persona: {$persona['persona_name']}
- Niveau de conscience: {$consciousness_text}

OFFRE:
{$offerDesc}

CANAUX DISPONIBLES:
1. ORGANIC LOCAL (Google Business, SEO local)
   → Recommandé si: zone locale, prospect peu conscient, besoin de confiance
   
2. FACEBOOK ADS
   → Recommandé si: contenu vidéo, émotion, pédagogie, audience large
   
3. GOOGLE ADS
   → Recommandé si: persona très conscient, recherche active (estimation, vendre)
   
4. HYBRID (combinaison)
   → Recommandé si: ressources suffisantes, plusieurs canaux pertinents

TÂCHE: Recommande LE MEILLEUR CANAL avec justification COACH (explique POURQUOI, pas "voici l'outil").

FORMAT ATTENDU:
```json
{
  "canal_recommande": "organic_local|facebook_ads|google_ads|hybrid",
  "justification": "Explication détaillée POURQUOI ce canal pour ce persona",
  "types_contenus": ["type1", "type2", "type3"],
  "themes_locaux": ["theme1", "theme2"],
  "pages_a_creer": ["page1", "page2"],
  "strategie_details": {
    "frequence": "publication par semaine",
    "budget_estime": "montant estimé"
  }
}
```

RÉPONDS UNIQUEMENT AVEC LE JSON VALIDE:
PROMPT;
    }
    
    private function buildPromptForFinalPlan($summary) {
        $profile = $summary['profile'] ?? [];
        $persona = $summary['persona'] ?? [];
        $offer = $summary['offer'] ?? [];
        $strategy = $summary['strategy'] ?? [];
        
        // Extraire implode() avant le heredoc
        $contentTypes = implode(', ', $strategy['content_types'] ?? []);
        $offerPromise = $offer['promise'] ?? '';
        $offerTitle = $offer['offer_title'] ?? '';
        $trafficChoice = $strategy['traffic_choice'] ?? '';
        
        return <<<PROMPT
Tu es un expert en stratégie marketing immobilier. Génère un CAHIER STRATÉGIQUE COMPLET.

RÉSUMÉ VALIDÉ PAR L'UTILISATEUR:

PROFIL:
- Métier: {$profile['profession']}
- Zone: {$profile['city']}
- Expérience: {$profile['experience_level']}

PERSONA:
- Type: {$persona['persona_name']}
- Conscience: {$persona['consciousness_level']}

PROMESSE:
{$offerPromise}

OFFRE:
{$offerTitle}

STRATÉGIE:
- Canal: {$trafficChoice}
- Contenus: {$contentTypes}

TÂCHE: Génère un cahier stratégique structuré avec:
1. résumé_executif: 3-4 lignes
2. profil_et_persona: Qui es-tu, qui cibles-tu
3. promesse_et_offre: La promesse + structure de l'offre
4. strategie_trafic: Canal choisi + pourquoi
5. types_contenus: Contenus à produire (avec exemples)
6. pages_a_creer: Pages prioritaires
7. prochaine_action: L'action CONCRÈTE semaine 1

FORMAT ATTENDU:
```json
{
  "titre": "Cahier Stratégique",
  "resume_executif": "...",
  "profil_et_persona": {"profil": "...", "persona": "..."},
  "promesse_et_offre": {"promesse": "...", "offre": "..."},
  "strategie_trafic": {"canal": "...", "justification": "..."},
  "types_contenus": ["...", "..."],
  "pages_a_creer": ["...", "..."],
  "prochaine_action": "Action concrète #1 pour cette semaine"
}
```

RÉPONDS UNIQUEMENT AVEC LE JSON VALIDE:
PROMPT;
    }
    
    // ============================================
    // APPEL API CLAUDE
    // ============================================
    
    private function callClaude($prompt) {
        try {
            $ch = curl_init($this->api_url);
            
            $headers = [
                'Content-Type: application/json',
                'x-api-key: ' . $this->api_key,
                'anthropic-version: 2023-06-01'
            ];
            
            $body = [
                'model' => $this->model,
                'max_tokens' => 2000,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt]
                ]
            ];
            
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_POSTFIELDS => json_encode($body),
                CURLOPT_TIMEOUT => 30
            ]);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($http_code !== 200) {
                return ['success' => false, 'error' => 'API Error: ' . $http_code];
            }
            
            $decoded = json_decode($response, true);
            
            if (isset($decoded['content'][0]['text'])) {
                return [
                    'success' => true,
                    'content' => $decoded['content'][0]['text']
                ];
            }
            
            return ['success' => false, 'error' => 'Invalid response format'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Log les appels IA pour tracking
     */
    private function logAICall($step, $prompt, $response) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO launchpad_ai_generations 
                (session_id, step_number, prompt_sent, response_received, tokens_used, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $this->launchpad_id,
                $step,
                substr($prompt, 0, 2000),
                substr($response, 0, 5000),
                0
            ]);
        } catch (Exception $e) {
            // Silent fail sur le logging
        }
    }
}
