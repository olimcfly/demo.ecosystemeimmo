<?php
// admin/modules/properties/PropertyController.php
// Controller CRUD pour la gestion des biens immobiliers

class PropertyController {
    
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    // ========================================================
    // LISTE avec filtres, recherche et pagination
    // ========================================================
    public function getAll($filters = []) {
        $where = ['1=1'];
        $params = [];
        
        // Filtre par statut
        if (!empty($filters['status'])) {
            $where[] = 'p.status = :status';
            $params[':status'] = $filters['status'];
        }
        
        // Filtre par type de bien
        if (!empty($filters['type'])) {
            $where[] = 'p.type = :type';
            $params[':type'] = $filters['type'];
        }
        
        // Filtre par transaction (vente/location)
        if (!empty($filters['transaction'])) {
            $where[] = 'p.transaction = :transaction';
            $params[':transaction'] = $filters['transaction'];
        }
        
        // Filtre par ville
        if (!empty($filters['city'])) {
            $where[] = 'p.city LIKE :city';
            $params[':city'] = '%' . $filters['city'] . '%';
        }
        
        // Filtre par prix min/max
        if (!empty($filters['price_min'])) {
            $where[] = 'p.price >= :price_min';
            $params[':price_min'] = $filters['price_min'];
        }
        if (!empty($filters['price_max'])) {
            $where[] = 'p.price <= :price_max';
            $params[':price_max'] = $filters['price_max'];
        }
        
        // Filtre par surface min
        if (!empty($filters['surface_min'])) {
            $where[] = 'p.surface >= :surface_min';
            $params[':surface_min'] = $filters['surface_min'];
        }
        
        // Filtre par nombre de pièces min
        if (!empty($filters['rooms_min'])) {
            $where[] = 'p.rooms >= :rooms_min';
            $params[':rooms_min'] = $filters['rooms_min'];
        }
        
        // Filtre "mis en avant"
        if (isset($filters['featured']) && $filters['featured'] !== '') {
            $where[] = 'p.featured = :featured';
            $params[':featured'] = (int)$filters['featured'];
        }
        
        // Recherche textuelle
        if (!empty($filters['search'])) {
            $where[] = '(p.title LIKE :search OR p.reference LIKE :search2 OR p.city LIKE :search3 OR p.address LIKE :search4)';
            $params[':search'] = '%' . $filters['search'] . '%';
            $params[':search2'] = '%' . $filters['search'] . '%';
            $params[':search3'] = '%' . $filters['search'] . '%';
            $params[':search4'] = '%' . $filters['search'] . '%';
        }
        
        $whereClause = implode(' AND ', $where);
        
        // Tri
        $allowedSort = ['title', 'price', 'surface', 'city', 'status', 'created_at', 'views', 'reference'];
        $sort = in_array($filters['sort'] ?? '', $allowedSort) ? $filters['sort'] : 'created_at';
        $order = ($filters['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
        
        // Comptage total
        $countSql = "SELECT COUNT(*) FROM properties p WHERE {$whereClause}";
        $countStmt = $this->pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();
        
        // Pagination
        $perPage = (int)($filters['per_page'] ?? 20);
        $page = max(1, (int)($filters['page'] ?? 1));
        $offset = ($page - 1) * $perPage;
        
        // Requête principale
        $sql = "SELECT p.* 
                FROM properties p 
                WHERE {$whereClause} 
                ORDER BY p.{$sort} {$order} 
                LIMIT {$perPage} OFFSET {$offset}";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Décoder JSON pour chaque bien
        foreach ($properties as &$prop) {
            $prop['features'] = json_decode($prop['features'] ?? '[]', true) ?: [];
            $prop['images'] = json_decode($prop['images'] ?? '[]', true) ?: [];
        }
        
        return [
            'data' => $properties,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }
    
    // ========================================================
    // RÉCUPÉRER UN BIEN par ID
    // ========================================================
    public function getById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM properties WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $property = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($property) {
            $property['features'] = json_decode($property['features'] ?? '[]', true) ?: [];
            $property['images'] = json_decode($property['images'] ?? '[]', true) ?: [];
        }
        
        return $property;
    }
    
    // ========================================================
    // RÉCUPÉRER UN BIEN par slug
    // ========================================================
    public function getBySlug($slug) {
        $stmt = $this->pdo->prepare("SELECT * FROM properties WHERE slug = :slug");
        $stmt->execute([':slug' => $slug]);
        $property = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($property) {
            $property['features'] = json_decode($property['features'] ?? '[]', true) ?: [];
            $property['images'] = json_decode($property['images'] ?? '[]', true) ?: [];
        }
        
        return $property;
    }
    
    // ========================================================
    // CRÉER UN BIEN
    // ========================================================
    public function create($data) {
        $errors = $this->validate($data);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Générer le slug
        $slug = $this->generateSlug($data['title'], $data['city'] ?? '');
        
        // Générer la référence si absente
        $reference = !empty($data['reference']) ? $data['reference'] : $this->generateReference($data['type'] ?? 'autre');
        
        $sql = "INSERT INTO properties (
                    reference, title, slug, type, transaction,
                    price, charges, surface, land_surface, rooms, bedrooms, bathrooms,
                    floor, total_floors, construction_year,
                    energy_class, ges_class,
                    description, address, city, postal_code, neighborhood,
                    latitude, longitude,
                    features, images, virtual_tour_url,
                    status, featured
                ) VALUES (
                    :reference, :title, :slug, :type, :transaction,
                    :price, :charges, :surface, :land_surface, :rooms, :bedrooms, :bathrooms,
                    :floor, :total_floors, :construction_year,
                    :energy_class, :ges_class,
                    :description, :address, :city, :postal_code, :neighborhood,
                    :latitude, :longitude,
                    :features, :images, :virtual_tour_url,
                    :status, :featured
                )";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':reference'         => $reference,
            ':title'             => $data['title'],
            ':slug'              => $slug,
            ':type'              => $data['type'] ?? 'appartement',
            ':transaction'       => $data['transaction'] ?? 'vente',
            ':price'             => !empty($data['price']) ? $data['price'] : null,
            ':charges'           => !empty($data['charges']) ? $data['charges'] : null,
            ':surface'           => !empty($data['surface']) ? (int)$data['surface'] : null,
            ':land_surface'      => !empty($data['land_surface']) ? (int)$data['land_surface'] : null,
            ':rooms'             => !empty($data['rooms']) ? (int)$data['rooms'] : null,
            ':bedrooms'          => !empty($data['bedrooms']) ? (int)$data['bedrooms'] : null,
            ':bathrooms'         => !empty($data['bathrooms']) ? (int)$data['bathrooms'] : null,
            ':floor'             => !empty($data['floor']) ? (int)$data['floor'] : null,
            ':total_floors'      => !empty($data['total_floors']) ? (int)$data['total_floors'] : null,
            ':construction_year' => !empty($data['construction_year']) ? (int)$data['construction_year'] : null,
            ':energy_class'      => !empty($data['energy_class']) ? $data['energy_class'] : null,
            ':ges_class'         => !empty($data['ges_class']) ? $data['ges_class'] : null,
            ':description'       => $data['description'] ?? '',
            ':address'           => $data['address'] ?? '',
            ':city'              => $data['city'] ?? '',
            ':postal_code'       => $data['postal_code'] ?? '',
            ':neighborhood'      => $data['neighborhood'] ?? '',
            ':latitude'          => !empty($data['latitude']) ? $data['latitude'] : null,
            ':longitude'         => !empty($data['longitude']) ? $data['longitude'] : null,
            ':features'          => json_encode($data['features'] ?? [], JSON_UNESCAPED_UNICODE),
            ':images'            => json_encode($data['images'] ?? [], JSON_UNESCAPED_UNICODE),
            ':virtual_tour_url'  => $data['virtual_tour_url'] ?? null,
            ':status'            => $data['status'] ?? 'draft',
            ':featured'          => !empty($data['featured']) ? 1 : 0,
        ]);
        
        $newId = $this->pdo->lastInsertId();
        
        return ['success' => true, 'id' => $newId, 'slug' => $slug, 'reference' => $reference];
    }
    
    // ========================================================
    // METTRE À JOUR UN BIEN
    // ========================================================
    public function update($id, $data) {
        $existing = $this->getById($id);
        if (!$existing) {
            return ['success' => false, 'errors' => ['Bien introuvable.']];
        }
        
        $errors = $this->validate($data, $id);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Regénérer le slug si le titre change
        $slug = $existing['slug'];
        if ($data['title'] !== $existing['title'] || ($data['city'] ?? '') !== ($existing['city'] ?? '')) {
            $slug = $this->generateSlug($data['title'], $data['city'] ?? '', $id);
        }
        
        $sql = "UPDATE properties SET
                    reference = :reference, title = :title, slug = :slug,
                    type = :type, transaction = :transaction,
                    price = :price, charges = :charges,
                    surface = :surface, land_surface = :land_surface,
                    rooms = :rooms, bedrooms = :bedrooms, bathrooms = :bathrooms,
                    floor = :floor, total_floors = :total_floors, construction_year = :construction_year,
                    energy_class = :energy_class, ges_class = :ges_class,
                    description = :description,
                    address = :address, city = :city, postal_code = :postal_code, neighborhood = :neighborhood,
                    latitude = :latitude, longitude = :longitude,
                    features = :features, images = :images, virtual_tour_url = :virtual_tour_url,
                    status = :status, featured = :featured
                WHERE id = :id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id'                => $id,
            ':reference'         => $data['reference'] ?? $existing['reference'],
            ':title'             => $data['title'],
            ':slug'              => $slug,
            ':type'              => $data['type'] ?? 'appartement',
            ':transaction'       => $data['transaction'] ?? 'vente',
            ':price'             => !empty($data['price']) ? $data['price'] : null,
            ':charges'           => !empty($data['charges']) ? $data['charges'] : null,
            ':surface'           => !empty($data['surface']) ? (int)$data['surface'] : null,
            ':land_surface'      => !empty($data['land_surface']) ? (int)$data['land_surface'] : null,
            ':rooms'             => !empty($data['rooms']) ? (int)$data['rooms'] : null,
            ':bedrooms'          => !empty($data['bedrooms']) ? (int)$data['bedrooms'] : null,
            ':bathrooms'         => !empty($data['bathrooms']) ? (int)$data['bathrooms'] : null,
            ':floor'             => !empty($data['floor']) ? (int)$data['floor'] : null,
            ':total_floors'      => !empty($data['total_floors']) ? (int)$data['total_floors'] : null,
            ':construction_year' => !empty($data['construction_year']) ? (int)$data['construction_year'] : null,
            ':energy_class'      => !empty($data['energy_class']) ? $data['energy_class'] : null,
            ':ges_class'         => !empty($data['ges_class']) ? $data['ges_class'] : null,
            ':description'       => $data['description'] ?? '',
            ':address'           => $data['address'] ?? '',
            ':city'              => $data['city'] ?? '',
            ':postal_code'       => $data['postal_code'] ?? '',
            ':neighborhood'      => $data['neighborhood'] ?? '',
            ':latitude'          => !empty($data['latitude']) ? $data['latitude'] : null,
            ':longitude'         => !empty($data['longitude']) ? $data['longitude'] : null,
            ':features'          => json_encode($data['features'] ?? [], JSON_UNESCAPED_UNICODE),
            ':images'            => json_encode($data['images'] ?? [], JSON_UNESCAPED_UNICODE),
            ':virtual_tour_url'  => $data['virtual_tour_url'] ?? null,
            ':status'            => $data['status'] ?? 'draft',
            ':featured'          => !empty($data['featured']) ? 1 : 0,
        ]);
        
        return ['success' => true, 'id' => $id, 'slug' => $slug];
    }
    
    // ========================================================
    // SUPPRIMER UN BIEN
    // ========================================================
    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM properties WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }
    
    // ========================================================
    // CHANGER LE STATUT
    // ========================================================
    public function updateStatus($id, $status) {
        $allowed = ['draft', 'available', 'under_offer', 'sold', 'rented'];
        if (!in_array($status, $allowed)) {
            return false;
        }
        $stmt = $this->pdo->prepare("UPDATE properties SET status = :status WHERE id = :id");
        $stmt->execute([':status' => $status, ':id' => $id]);
        return $stmt->rowCount() > 0;
    }
    
    // ========================================================
    // TOGGLE FEATURED
    // ========================================================
    public function toggleFeatured($id) {
        $stmt = $this->pdo->prepare("UPDATE properties SET featured = IF(featured = 1, 0, 1) WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }
    
    // ========================================================
    // INCRÉMENTER LES VUES
    // ========================================================
    public function incrementViews($id) {
        $stmt = $this->pdo->prepare("UPDATE properties SET views = views + 1 WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }
    
    // ========================================================
    // STATISTIQUES DASHBOARD
    // ========================================================
    public function getStats() {
        $stats = [];
        
        // Total par statut
        $stmt = $this->pdo->query("SELECT status, COUNT(*) as count FROM properties GROUP BY status");
        $statusCounts = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $statusCounts[$row['status']] = (int)$row['count'];
        }
        $stats['by_status'] = $statusCounts;
        $stats['total'] = array_sum($statusCounts);
        
        // Total par type
        $stmt = $this->pdo->query("SELECT type, COUNT(*) as count FROM properties GROUP BY type");
        $typeCounts = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $typeCounts[$row['type']] = (int)$row['count'];
        }
        $stats['by_type'] = $typeCounts;
        
        // Prix moyen par type de transaction
        $stmt = $this->pdo->query("
            SELECT transaction, 
                   ROUND(AVG(price)) as avg_price, 
                   MIN(price) as min_price, 
                   MAX(price) as max_price,
                   COUNT(*) as count
            FROM properties 
            WHERE price > 0 AND status IN ('available', 'under_offer')
            GROUP BY transaction
        ");
        $stats['prices'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Vues totales
        $stmt = $this->pdo->query("SELECT COALESCE(SUM(views), 0) as total_views FROM properties");
        $stats['total_views'] = (int)$stmt->fetchColumn();
        
        // Derniers biens ajoutés
        $stmt = $this->pdo->query("SELECT id, title, reference, status, created_at FROM properties ORDER BY created_at DESC LIMIT 5");
        $stats['recent'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $stats;
    }
    
    // ========================================================
    // VALIDATION
    // ========================================================
    private function validate($data, $excludeId = null) {
        $errors = [];
        
        if (empty($data['title'])) {
            $errors[] = 'Le titre est obligatoire.';
        }
        
        if (!empty($data['price']) && !is_numeric($data['price'])) {
            $errors[] = 'Le prix doit être un nombre.';
        }
        
        if (!empty($data['surface']) && !is_numeric($data['surface'])) {
            $errors[] = 'La surface doit être un nombre.';
        }
        
        $validTypes = ['appartement', 'maison', 'terrain', 'commerce', 'autre'];
        if (!empty($data['type']) && !in_array($data['type'], $validTypes)) {
            $errors[] = 'Type de bien invalide.';
        }
        
        $validTransactions = ['vente', 'location'];
        if (!empty($data['transaction']) && !in_array($data['transaction'], $validTransactions)) {
            $errors[] = 'Type de transaction invalide.';
        }
        
        $validEnergy = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];
        if (!empty($data['energy_class']) && !in_array(strtoupper($data['energy_class']), $validEnergy)) {
            $errors[] = 'Classe énergie invalide (A-G).';
        }
        if (!empty($data['ges_class']) && !in_array(strtoupper($data['ges_class']), $validEnergy)) {
            $errors[] = 'Classe GES invalide (A-G).';
        }
        
        // Vérifier unicité de la référence
        if (!empty($data['reference'])) {
            $sql = "SELECT id FROM properties WHERE reference = :ref";
            $params = [':ref' => $data['reference']];
            if ($excludeId) {
                $sql .= " AND id != :id";
                $params[':id'] = $excludeId;
            }
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            if ($stmt->fetch()) {
                $errors[] = 'Cette référence existe déjà.';
            }
        }
        
        return $errors;
    }
    
    // ========================================================
    // GÉNÉRER UN SLUG UNIQUE
    // ========================================================
    private function generateSlug($title, $city = '', $excludeId = null) {
        $text = $title . ($city ? '-' . $city : '');
        $slug = $this->slugify($text);
        
        $sql = "SELECT COUNT(*) FROM properties WHERE slug = :slug";
        $params = [':slug' => $slug];
        if ($excludeId) {
            $sql .= " AND id != :id";
            $params[':id'] = $excludeId;
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        if ((int)$stmt->fetchColumn() > 0) {
            $i = 2;
            do {
                $newSlug = $slug . '-' . $i;
                $params[':slug'] = $newSlug;
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);
                $i++;
            } while ((int)$stmt->fetchColumn() > 0);
            $slug = $newSlug;
        }
        
        return $slug;
    }
    
    // ========================================================
    // GÉNÉRER UNE RÉFÉRENCE UNIQUE (EDS-APP-001)
    // ========================================================
    private function generateReference($type) {
        $prefixes = [
            'appartement' => 'APP',
            'maison'      => 'MAI',
            'terrain'     => 'TER',
            'commerce'    => 'COM',
            'autre'       => 'AUT',
        ];
        $prefix = 'EDS-' . ($prefixes[$type] ?? 'AUT');
        
        $stmt = $this->pdo->prepare("SELECT reference FROM properties WHERE reference LIKE :prefix ORDER BY id DESC LIMIT 1");
        $stmt->execute([':prefix' => $prefix . '-%']);
        $last = $stmt->fetchColumn();
        
        $num = 1;
        if ($last && preg_match('/-(\d+)$/', $last, $m)) {
            $num = (int)$m[1] + 1;
        }
        
        return $prefix . '-' . str_pad($num, 3, '0', STR_PAD_LEFT);
    }
    
    // ========================================================
    // SLUGIFY
    // ========================================================
    private function slugify($text) {
        $text = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $text);
        $text = preg_replace('/[^a-z0-9\-]/', '-', $text);
        $text = preg_replace('/-+/', '-', $text);
        return trim($text, '-');
    }
    
    // ========================================================
    // LISTE DES VILLES DISTINCTES (pour filtres)
    // ========================================================
    public function getCities() {
        $stmt = $this->pdo->query("SELECT DISTINCT city FROM properties WHERE city != '' ORDER BY city");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    // ========================================================
    // LISTE DES QUARTIERS DISTINCTS (pour filtres)
    // ========================================================
    public function getNeighborhoods($city = null) {
        $sql = "SELECT DISTINCT neighborhood FROM properties WHERE neighborhood != ''";
        $params = [];
        if ($city) {
            $sql .= " AND city = :city";
            $params[':city'] = $city;
        }
        $sql .= " ORDER BY neighborhood";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}