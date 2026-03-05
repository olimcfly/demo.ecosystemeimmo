<?php
// admin/modules/strategie/StrategyService.php

class StrategyService {
    
    private $db;
    private $userId;
    
    public function __construct($database, $userId) {
        $this->db = $database;
        $this->userId = $userId;
    }
    
    // ========== PERSONAS ==========
    
    public function createPersona($data) {
        $sql = "INSERT INTO personas 
                (user_id, type, nom, description, age_moyen, situation_familiale, 
                 revenus, motivations, objections, problemes_clefs, aspirations, 
                 canaux_preferes, couleur, icone) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $motivations = json_encode($data['motivations'] ?? []);
        $objections = json_encode($data['objections'] ?? []);
        $problemes = json_encode($data['problemes_clefs'] ?? []);
        $aspirations = json_encode($data['aspirations'] ?? []);
        $canaux = json_encode($data['canaux_preferes'] ?? []);
        
        return $stmt->execute([
            $this->userId,
            $data['type'],
            $data['nom'],
            $data['description'] ?? null,
            $data['age_moyen'] ?? null,
            $data['situation_familiale'] ?? null,
            $data['revenus'] ?? null,
            $motivations,
            $objections,
            $problemes,
            $aspirations,
            $canaux,
            $data['couleur'] ?? '#6366f1',
            $data['icone'] ?? 'user'
        ]);
    }
    
    public function updatePersona($personaId, $data) {
        $sql = "UPDATE personas SET 
                type = ?, nom = ?, description = ?, age_moyen = ?, 
                situation_familiale = ?, revenus = ?, motivations = ?, 
                objections = ?, problemes_clefs = ?, aspirations = ?, 
                canaux_preferes = ?, couleur = ?, icone = ?
                WHERE id = ? AND user_id = ?";
        
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            $data['type'],
            $data['nom'],
            $data['description'] ?? null,
            $data['age_moyen'] ?? null,
            $data['situation_familiale'] ?? null,
            $data['revenus'] ?? null,
            json_encode($data['motivations'] ?? []),
            json_encode($data['objections'] ?? []),
            json_encode($data['problemes_clefs'] ?? []),
            json_encode($data['aspirations'] ?? []),
            json_encode($data['canaux_preferes'] ?? []),
            $data['couleur'] ?? '#6366f1',
            $data['icone'] ?? 'user',
            $personaId,
            $this->userId
        ]);
    }
    
    public function getPersonas($filters = []) {
        $sql = "SELECT * FROM personas WHERE user_id = ?";
        $params = [$this->userId];
        
        if (!empty($filters['type'])) {
            $sql .= " AND type = ?";
            $params[] = $filters['type'];
        }
        
        $sql .= " ORDER BY type, nom";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Décoder les JSON
        return array_map(function($persona) {
            $persona['motivations'] = json_decode($persona['motivations'], true) ?? [];
            $persona['objections'] = json_decode($persona['objections'], true) ?? [];
            $persona['problemes_clefs'] = json_decode($persona['problemes_clefs'], true) ?? [];
            $persona['aspirations'] = json_decode($persona['aspirations'], true) ?? [];
            $persona['canaux_preferes'] = json_decode($persona['canaux_preferes'], true) ?? [];
            return $persona;
        }, $results);
    }
    
    public function getPersonaById($personaId) {
        $sql = "SELECT * FROM personas WHERE id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$personaId, $this->userId]);
        $persona = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($persona) {
            $persona['motivations'] = json_decode($persona['motivations'], true) ?? [];
            $persona['objections'] = json_decode($persona['objections'], true) ?? [];
            $persona['problemes_clefs'] = json_decode($persona['problemes_clefs'], true) ?? [];
            $persona['aspirations'] = json_decode($persona['aspirations'], true) ?? [];
            $persona['canaux_preferes'] = json_decode($persona['canaux_preferes'], true) ?? [];
        }
        
        return $persona;
    }
    
    public function deletePersona($personaId) {
        $sql = "DELETE FROM personas WHERE id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$personaId, $this->userId]);
    }
    
    // ========== SUJETS ==========
    
    public function createSujet($data) {
        $sql = "INSERT INTO strategy_sujets 
                (user_id, persona_id, titre, description, pertinence, intent, 
                 mots_cles, questions_cibles, position_actuelle, position_cible, 
                 volume_recherche, competitivite) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            $this->userId,
            $data['persona_id'],
            $data['titre'],
            $data['description'] ?? null,
            $data['pertinence'] ?? 'moyenne',
            $data['intent'] ?? 'informatif',
            json_encode($data['mots_cles'] ?? []),
            json_encode($data['questions_cibles'] ?? []),
            $data['position_actuelle'] ?? null,
            $data['position_cible'] ?? null,
            $data['volume_recherche'] ?? null,
            $data['competitivite'] ?? null
        ]);
    }
    
    public function updateSujet($sujetId, $data) {
        $sql = "UPDATE strategy_sujets SET 
                titre = ?, description = ?, pertinence = ?, intent = ?, 
                mots_cles = ?, questions_cibles = ?, position_actuelle = ?, 
                position_cible = ?, volume_recherche = ?, competitivite = ?
                WHERE id = ? AND user_id = ?";
        
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            $data['titre'],
            $data['description'] ?? null,
            $data['pertinence'] ?? 'moyenne',
            $data['intent'] ?? 'informatif',
            json_encode($data['mots_cles'] ?? []),
            json_encode($data['questions_cibles'] ?? []),
            $data['position_actuelle'] ?? null,
            $data['position_cible'] ?? null,
            $data['volume_recherche'] ?? null,
            $data['competitivite'] ?? null,
            $sujetId,
            $this->userId
        ]);
    }
    
    public function getSujetsByPersona($personaId) {
        $sql = "SELECT * FROM strategy_sujets 
                WHERE persona_id = ? AND user_id = ? 
                ORDER BY pertinence DESC, titre";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$personaId, $this->userId]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return array_map(function($sujet) {
            $sujet['mots_cles'] = json_decode($sujet['mots_cles'], true) ?? [];
            $sujet['questions_cibles'] = json_decode($sujet['questions_cibles'], true) ?? [];
            return $sujet;
        }, $results);
    }
    
    public function deleteSujet($sujetId) {
        $sql = "DELETE FROM strategy_sujets WHERE id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$sujetId, $this->userId]);
    }
    
    // ========== OFFRES ==========
    
    public function createOffre($data) {
        $sql = "INSERT INTO strategy_offres 
                (user_id, persona_id, titre, description, valeur_principale, 
                 avantages, diferenciateurs, prix_ou_cout, conditions, urgence, garanties) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            $this->userId,
            $data['persona_id'],
            $data['titre'],
            $data['description'] ?? null,
            $data['valeur_principale'] ?? null,
            json_encode($data['avantages'] ?? []),
            json_encode($data['diferenciateurs'] ?? []),
            $data['prix_ou_cout'] ?? null,
            $data['conditions'] ?? null,
            $data['urgence'] ?? null,
            json_encode($data['garanties'] ?? [])
        ]);
    }
    
    public function updateOffre($offreId, $data) {
        $sql = "UPDATE strategy_offres SET 
                titre = ?, description = ?, valeur_principale = ?, 
                avantages = ?, diferenciateurs = ?, prix_ou_cout = ?, 
                conditions = ?, urgence = ?, garanties = ?
                WHERE id = ? AND user_id = ?";
        
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            $data['titre'],
            $data['description'] ?? null,
            $data['valeur_principale'] ?? null,
            json_encode($data['avantages'] ?? []),
            json_encode($data['diferenciateurs'] ?? []),
            $data['prix_ou_cout'] ?? null,
            $data['conditions'] ?? null,
            $data['urgence'] ?? null,
            json_encode($data['garanties'] ?? []),
            $offreId,
            $this->userId
        ]);
    }
    
    public function getOffresByPersona($personaId) {
        $sql = "SELECT * FROM strategy_offres 
                WHERE persona_id = ? AND user_id = ? 
                ORDER BY titre";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$personaId, $this->userId]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return array_map(function($offre) {
            $offre['avantages'] = json_decode($offre['avantages'], true) ?? [];
            $offre['diferenciateurs'] = json_decode($offre['diferenciateurs'], true) ?? [];
            $offre['garanties'] = json_decode($offre['garanties'], true) ?? [];
            return $offre;
        }, $results);
    }
    
    public function deleteOffre($offreId) {
        $sql = "DELETE FROM strategy_offres WHERE id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$offreId, $this->userId]);
    }
    
    // ========== COMMUNICATIONS ==========
    
    public function createCommunication($data) {
        $sql = "INSERT INTO strategy_communications 
                (user_id, persona_id, sujet_id, nom, description, canal, 
                 type_action, contexte, audience_specifique, message_principal, 
                 appel_action, frequence, budget_estime, kpis, statut) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            $this->userId,
            $data['persona_id'],
            $data['sujet_id'] ?? null,
            $data['nom'],
            $data['description'] ?? null,
            $data['canal'] ?? null,
            $data['type_action'] ?? 'organique',
            $data['contexte'] ?? 'discovery',
            $data['audience_specifique'] ?? null,
            $data['message_principal'] ?? null,
            $data['appel_action'] ?? null,
            $data['frequence'] ?? null,
            $data['budget_estime'] ?? null,
            json_encode($data['kpis'] ?? []),
            $data['statut'] ?? 'draft'
        ]);
    }
    
    public function updateCommunication($communicationId, $data) {
        $sql = "UPDATE strategy_communications SET 
                persona_id = ?, sujet_id = ?, nom = ?, description = ?, canal = ?, 
                type_action = ?, contexte = ?, audience_specifique = ?, 
                message_principal = ?, appel_action = ?, frequence = ?, 
                budget_estime = ?, kpis = ?, statut = ?
                WHERE id = ? AND user_id = ?";
        
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            $data['persona_id'],
            $data['sujet_id'] ?? null,
            $data['nom'],
            $data['description'] ?? null,
            $data['canal'] ?? null,
            $data['type_action'] ?? 'organique',
            $data['contexte'] ?? 'discovery',
            $data['audience_specifique'] ?? null,
            $data['message_principal'] ?? null,
            $data['appel_action'] ?? null,
            $data['frequence'] ?? null,
            $data['budget_estime'] ?? null,
            json_encode($data['kpis'] ?? []),
            $data['statut'] ?? 'draft',
            $communicationId,
            $this->userId
        ]);
    }
    
    public function getCommunicationsByPersona($personaId) {
        $sql = "SELECT sc.*, p.nom as persona_nom, s.titre as sujet_titre
                FROM strategy_communications sc
                LEFT JOIN personas p ON sc.persona_id = p.id
                LEFT JOIN strategy_sujets s ON sc.sujet_id = s.id
                WHERE sc.persona_id = ? AND sc.user_id = ? 
                ORDER BY sc.contexte, sc.nom";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$personaId, $this->userId]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return array_map(function($comm) {
            $comm['kpis'] = json_decode($comm['kpis'], true) ?? [];
            return $comm;
        }, $results);
    }
    
    public function deleteCommunication($communicationId) {
        $sql = "DELETE FROM strategy_communications WHERE id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$communicationId, $this->userId]);
    }
    
    // ========== STRUCTURES MARKETING ==========
    
    public function createStructure($data) {
        $sql = "INSERT INTO strategy_structures 
                (user_id, communication_id, nom, description, type_format, 
                 template_id, structure_json, elements_cles, timing, duree_vie, statut) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            $this->userId,
            $data['communication_id'],
            $data['nom'],
            $data['description'] ?? null,
            $data['type_format'],
            $data['template_id'] ?? null,
            json_encode($data['structure_json'] ?? []),
            json_encode($data['elements_cles'] ?? []),
            $data['timing'] ?? null,
            $data['duree_vie'] ?? null,
            $data['statut'] ?? 'draft'
        ]);
    }
    
    public function updateStructure($structureId, $data) {
        $sql = "UPDATE strategy_structures SET 
                nom = ?, description = ?, type_format = ?, template_id = ?, 
                structure_json = ?, elements_cles = ?, timing = ?, duree_vie = ?, statut = ?
                WHERE id = ? AND user_id = ?";
        
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            $data['nom'],
            $data['description'] ?? null,
            $data['type_format'],
            $data['template_id'] ?? null,
            json_encode($data['structure_json'] ?? []),
            json_encode($data['elements_cles'] ?? []),
            $data['timing'] ?? null,
            $data['duree_vie'] ?? null,
            $data['statut'] ?? 'draft',
            $structureId,
            $this->userId
        ]);
    }
    
    public function getStructuresByCommunication($communicationId) {
        $sql = "SELECT ss.*, sc.nom as communication_nom
                FROM strategy_structures ss
                LEFT JOIN strategy_communications sc ON ss.communication_id = sc.id
                WHERE ss.communication_id = ? AND ss.user_id = ? 
                ORDER BY ss.type_format, ss.nom";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$communicationId, $this->userId]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return array_map(function($struct) {
            $struct['structure_json'] = json_decode($struct['structure_json'], true) ?? [];
            $struct['elements_cles'] = json_decode($struct['elements_cles'], true) ?? [];
            return $struct;
        }, $results);
    }
    
    public function deleteStructure($structureId) {
        $sql = "DELETE FROM strategy_structures WHERE id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$structureId, $this->userId]);
    }
    
    // ========== CARTOGRAPHIE COMPLÈTE ==========
    
    public function getFullMapping($personaId) {
        // Récupère le persona avec tous ses éléments liés
        $persona = $this->getPersonaById($personaId);
        if (!$persona) return null;
        
        $sujets = $this->getSujetsByPersona($personaId);
        $offres = $this->getOffresByPersona($personaId);
        $communications = $this->getCommunicationsByPersona($personaId);
        
        // Pour chaque communication, récupérer les structures
        $communicationsWithStructures = array_map(function($comm) {
            $comm['structures'] = $this->getStructuresByCommunication($comm['id']);
            return $comm;
        }, $communications);
        
        return [
            'persona' => $persona,
            'sujets' => $sujets,
            'offres' => $offres,
            'communications' => $communicationsWithStructures
        ];
    }
    
    public function getAllMappings() {
        $personas = $this->getPersonas();
        
        return array_map(function($persona) {
            return $this->getFullMapping($persona['id']);
        }, $personas);
    }
    
    // ========== STATISTIQUES ==========
    
    public function getStatistics() {
        $stats = [];
        
        // Nombre de personas par type
        $sql = "SELECT type, COUNT(*) as count FROM personas 
                WHERE user_id = ? GROUP BY type";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->userId]);
        $stats['personas_by_type'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Nombre de sujets par persona
        $sql = "SELECT p.nom, COUNT(s.id) as count FROM personas p 
                LEFT JOIN strategy_sujets s ON p.id = s.persona_id 
                WHERE p.user_id = ? GROUP BY p.id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->userId]);
        $stats['sujets_by_persona'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Communications par statut
        $sql = "SELECT statut, COUNT(*) as count FROM strategy_communications 
                WHERE user_id = ? GROUP BY statut";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->userId]);
        $stats['communications_by_status'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Communications par contexte
        $sql = "SELECT contexte, COUNT(*) as count FROM strategy_communications 
                WHERE user_id = ? GROUP BY contexte";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->userId]);
        $stats['communications_by_context'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $stats;
    }
}
?>