<?php
/**
 * ÉCOSYSTÈME IMMO LOCAL+ - PageController
 * Contrôleur pour la gestion des pages
 */

require_once __DIR__ . '/../models/Page.php';

class PageController {
    private $page;
    private $response;
    
    public function __construct($pdo) {
        $this->page = new Page($pdo);
        $this->response = [];
    }
    
    /**
     * Récupérer toutes les pages (avec pagination)
     */
    public function index($limit = 20, $offset = 0, $status = null) {
        try {
            $pages = $this->page->getAll($status, $limit, $offset);
            $total = $this->page->count($status);
            
            $this->response = [
                'success' => true,
                'data' => $pages,
                'pagination' => [
                    'total' => $total,
                    'limit' => $limit,
                    'offset' => $offset,
                    'pages' => ceil($total / $limit)
                ]
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
     * Récupérer une page spécifique
     */
    public function show($id) {
        try {
            $page = $this->page->getById($id);
            
            if (!$page) {
                throw new Exception("Page non trouvée");
            }
            
            // Récupérer les stats
            $stats = $this->page->getStats($id);
            
            $this->response = [
                'success' => true,
                'data' => $page,
                'stats' => $stats
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
     * Créer une nouvelle page
     */
    public function store($data) {
        try {
            // Valider les données
            $this->validatePageData($data, 'create');
            
            // Générer le slug si vide
            if (empty($data['slug'])) {
                $data['slug'] = $this->page->generateSlug($data['title']);
            }
            
            // Vérifier l'unicité du slug
            if ($this->page->slugExists($data['slug'])) {
                throw new Exception("Ce slug existe déjà");
            }
            
            // Créer la page
            $pageId = $this->page->create($data);
            
            if (!$pageId) {
                throw new Exception("Erreur lors de la création");
            }
            
            // Récupérer la page créée
            $page = $this->page->getById($pageId);
            
            $this->response = [
                'success' => true,
                'message' => 'Page créée avec succès',
                'data' => $page,
                'id' => $pageId
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
     * Mettre à jour une page
     */
    public function update($id, $data) {
        try {
            // Vérifier que la page existe
            $page = $this->page->getById($id);
            if (!$page) {
                throw new Exception("Page non trouvée");
            }
            
            // Valider les données
            $this->validatePageData($data, 'update');
            
            // Vérifier l'unicité du slug
            if (isset($data['slug']) && $this->page->slugExists($data['slug'], $id)) {
                throw new Exception("Ce slug existe déjà");
            }
            
            // Mettre à jour
            $this->page->update($id, $data);
            
            // Récupérer la page mise à jour
            $updatedPage = $this->page->getById($id);
            
            $this->response = [
                'success' => true,
                'message' => 'Page mise à jour avec succès',
                'data' => $updatedPage
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
     * Publier une page
     */
    public function publish($id) {
        try {
            $page = $this->page->getById($id);
            if (!$page) {
                throw new Exception("Page non trouvée");
            }
            
            $this->page->publish($id);
            
            $updatedPage = $this->page->getById($id);
            
            $this->response = [
                'success' => true,
                'message' => 'Page publiée avec succès',
                'data' => $updatedPage
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
     * Dépublier une page
     */
    public function unpublish($id) {
        try {
            $page = $this->page->getById($id);
            if (!$page) {
                throw new Exception("Page non trouvée");
            }
            
            $this->page->unpublish($id);
            
            $updatedPage = $this->page->getById($id);
            
            $this->response = [
                'success' => true,
                'message' => 'Page dépubliée avec succès',
                'data' => $updatedPage
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
     * Supprimer une page
     */
    public function destroy($id) {
        try {
            $page = $this->page->getById($id);
            if (!$page) {
                throw new Exception("Page non trouvée");
            }
            
            $this->page->delete($id);
            
            $this->response = [
                'success' => true,
                'message' => 'Page supprimée avec succès'
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
     * Valider les données d'une page
     */
    private function validatePageData($data, $action = 'create') {
        // Valider le titre
        if (empty($data['title'])) {
            throw new Exception("Le titre est obligatoire");
        }
        
        if (strlen($data['title']) < 3 || strlen($data['title']) > 255) {
            throw new Exception("Le titre doit avoir entre 3 et 255 caractères");
        }
        
        // Valider la description
        if (empty($data['description'])) {
            throw new Exception("La description est obligatoire");
        }
        
        if (strlen($data['description']) < 10) {
            throw new Exception("La description doit avoir au moins 10 caractères");
        }
        
        // Valider le contenu
        if (empty($data['content'])) {
            throw new Exception("Le contenu est obligatoire");
        }
        
        // Valider le slug si fourni
        if (!empty($data['slug'])) {
            if (!preg_match('/^[a-z0-9-]+$/', $data['slug'])) {
                throw new Exception("Le slug ne peut contenir que des lettres, chiffres et tirets");
            }
        }
        
        // Valider le statut
        if (!empty($data['status'])) {
            if (!in_array($data['status'], ['draft', 'published', 'archived'])) {
                throw new Exception("Statut invalide");
            }
        }
        
        // Valider la visibilité
        if (!empty($data['visibility'])) {
            if (!in_array($data['visibility'], ['public', 'private', 'password'])) {
                throw new Exception("Visibilité invalide");
            }
        }
    }
    
    /**
     * Obtenir la réponse
     */
    public function getResponse() {
        return $this->response;
    }
    
    /**
     * Envoyer une réponse JSON
     */
    public function sendJson($statusCode = 200) {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($this->response);
        exit;
    }
}