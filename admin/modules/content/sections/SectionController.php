<?php
/**
 * SectionController
 * /admin/modules/sections/SectionController.php
 */

require_once INCLUDES_PATH . '/classes/Section.php';

class SectionController {
    private $section;
    private $response = [];
    
    public function __construct($pdo) {
        $this->section = new Section($pdo);
    }
    
    /**
     * Récupérer toutes les sections d'une page
     */
    public function getSectionsForPage($pageId) {
        try {
            $sections = $this->section->getByPageId($pageId);
            
            // Décoder les données JSON
            foreach ($sections as &$section) {
                if (!empty($section['data'])) {
                    $section['data'] = json_decode($section['data'], true);
                }
            }
            
            $this->response = [
                'success' => true,
                'data' => $sections
            ];
        } catch (Exception $e) {
            $this->response = [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
        
        return $this->response;
    }
    
    /**
     * Ajouter une section
     */
    public function addSection($pageId, $type, $data) {
        try {
            if (empty($pageId) || empty($type)) {
                throw new Exception('pageId et type sont obligatoires');
            }
            
            $order = $this->section->countByPageId($pageId);
            $id = $this->section->create($pageId, $type, $data, $order);
            
            $section = $this->section->getById($id);
            if (!empty($section['data'])) {
                $section['data'] = json_decode($section['data'], true);
            }
            
            $this->response = [
                'success' => true,
                'message' => 'Section ajoutée',
                'data' => $section,
                'id' => $id
            ];
        } catch (Exception $e) {
            $this->response = [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
        
        return $this->response;
    }
    
    /**
     * Mettre à jour une section
     */
    public function updateSection($id, $data) {
        try {
            if (!$this->section->getById($id)) {
                throw new Exception('Section non trouvée');
            }
            
            $this->section->update($id, $data);
            
            $section = $this->section->getById($id);
            if (!empty($section['data'])) {
                $section['data'] = json_decode($section['data'], true);
            }
            
            $this->response = [
                'success' => true,
                'message' => 'Section mise à jour',
                'data' => $section
            ];
        } catch (Exception $e) {
            $this->response = [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
        
        return $this->response;
    }
    
    /**
     * Supprimer une section
     */
    public function deleteSection($id) {
        try {
            if (!$this->section->getById($id)) {
                throw new Exception('Section non trouvée');
            }
            
            $this->section->delete($id);
            
            $this->response = [
                'success' => true,
                'message' => 'Section supprimée'
            ];
        } catch (Exception $e) {
            $this->response = [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
        
        return $this->response;
    }
    
    /**
     * Réordonner les sections
     */
    public function reorderSections($orders) {
        try {
            if (empty($orders)) {
                throw new Exception('orders est obligatoire');
            }
            
            $this->section->saveOrder($orders);
            
            $this->response = [
                'success' => true,
                'message' => 'Ordre sauvegardé'
            ];
        } catch (Exception $e) {
            $this->response = [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
        
        return $this->response;
    }
    
    /**
     * Obtenir la réponse
     */
    public function getResponse() {
        return $this->response;
    }
    
    /**
     * Envoyer JSON
     */
    public function sendJson($statusCode = 200) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($statusCode);
        echo json_encode($this->response, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
?>