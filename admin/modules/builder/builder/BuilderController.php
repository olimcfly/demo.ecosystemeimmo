<?php
/**
 * ══════════════════════════════════════════════════════════════
 * /admin/modules/builder/BuilderController.php
 * Contrôleur Builder Pro — Gestion des contenus visuels
 * 
 * Opère sur les tables :
 *   builder_content, builder_layouts, builder_templates,
 *   builder_block_types, builder_saved_blocks, builder_revisions
 * 
 * Contextes : article, capture, landing, secteur
 * ══════════════════════════════════════════════════════════════
 */

class BuilderController
{
    /** Contextes autorisés (utilisé par editor.php pour valider) */
    const CONTEXTS = ['article', 'capture', 'landing', 'secteur', 'header', 'footer'];

    /** Catégories de blocs → labels et icônes */
    const CATEGORY_META = [
        'structure'  => ['label' => 'Structure',  'icon' => 'fa-layer-group'],
        'contenu'    => ['label' => 'Contenu',    'icon' => 'fa-paragraph'],
        'media'      => ['label' => 'Média',      'icon' => 'fa-image'],
        'marketing'  => ['label' => 'Marketing',  'icon' => 'fa-bullhorn'],
        'immobilier' => ['label' => 'Immobilier', 'icon' => 'fa-home'],
        'seo'        => ['label' => 'SEO',        'icon' => 'fa-search'],
    ];

    private PDO $db;

    public function __construct(?PDO $pdo = null)
    {
        if ($pdo) {
            $this->db = $pdo;
        } elseif (function_exists('getDB')) {
            $this->db = getDB();
        } else {
            // Fallback : charger config.php
            require_once dirname(dirname(dirname(__DIR__))) . '/config/config.php';
            $this->db = getDB();
        }
    }

    // ══════════════════════════════════════════════════
    // LAYOUTS
    // ══════════════════════════════════════════════════

    /**
     * Récupère les layouts actifs pour un contexte donné
     */
    public function getLayouts(string $context): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT id, name, slug, description, thumbnail,
                       header_config, footer_config, page_config,
                       css_class, is_default
                FROM builder_layouts
                WHERE context = ? AND is_active = 1
                ORDER BY is_default DESC, name ASC
            ");
            $stmt->execute([$context]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as &$r) {
                $r['header_config'] = json_decode($r['header_config'] ?? '{}', true) ?: [];
                $r['footer_config'] = json_decode($r['footer_config'] ?? '{}', true) ?: [];
                $r['page_config']   = json_decode($r['page_config']   ?? '{"maxWidth":"1200px"}', true) ?: ['maxWidth' => '1200px'];
            }

            // Si aucun layout, retourner un layout par défaut
            if (empty($rows)) {
                $rows = [[
                    'id'            => 0,
                    'name'          => 'Par défaut',
                    'slug'          => 'default',
                    'description'   => 'Layout par défaut',
                    'thumbnail'     => null,
                    'header_config' => ['type' => 'site'],
                    'footer_config' => ['type' => 'site'],
                    'page_config'   => ['maxWidth' => '1200px'],
                    'css_class'     => '',
                    'is_default'    => 1,
                ]];
            }

            return $rows;
        } catch (PDOException $e) {
            $this->log('getLayouts error: ' . $e->getMessage());
            return [[
                'id' => 0, 'name' => 'Par défaut', 'slug' => 'default',
                'header_config' => ['type' => 'site'],
                'footer_config' => ['type' => 'site'],
                'page_config' => ['maxWidth' => '1200px'],
                'is_default' => 1, 'css_class' => '', 'description' => '', 'thumbnail' => null
            ]];
        }
    }

    // ══════════════════════════════════════════════════
    // TEMPLATES
    // ══════════════════════════════════════════════════

    /**
     * Récupère les templates pour un contexte
     */
    public function getTemplates(string $context): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT t.id, t.name, t.slug, t.description, t.thumbnail,
                       t.blocks_data, t.category, t.layout_id,
                       l.name AS layout_name
                FROM builder_templates t
                LEFT JOIN builder_layouts l ON t.layout_id = l.id
                WHERE (t.context = ? OR t.context = 'all')
                  AND (t.status = 'active' OR t.is_active = 1)
                ORDER BY t.is_default DESC, t.category ASC, t.name ASC
            ");
            $stmt->execute([$context]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as &$r) {
                $r['blocks_data']  = json_decode($r['blocks_data'] ?? '[]', true) ?: [];
                $r['layout_name']  = $r['layout_name'] ?? 'Par défaut';
            }

            return $rows;
        } catch (PDOException $e) {
            $this->log('getTemplates error: ' . $e->getMessage());
            return [];
        }
    }

    // ══════════════════════════════════════════════════
    // BLOCK TYPES
    // ══════════════════════════════════════════════════

    /**
     * Récupère les types de blocs disponibles, groupés par catégorie
     */
    public function getBlockTypes(string $context): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT slug, name, icon, category, description, default_config, contexts
                FROM builder_block_types
                WHERE is_active = 1
                ORDER BY category ASC, sort_order ASC, name ASC
            ");
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $grouped = [];
            foreach ($rows as $r) {
                // Filtrer par contexte
                $ctxList = json_decode($r['contexts'] ?? '[]', true) ?: self::CONTEXTS;
                if (!in_array($context, $ctxList)) continue;

                $cat = $r['category'];
                if (!isset($grouped[$cat])) {
                    $meta = self::CATEGORY_META[$cat] ?? ['label' => ucfirst($cat), 'icon' => 'fa-puzzle-piece'];
                    $grouped[$cat] = [
                        'label'  => $meta['label'],
                        'icon'   => $meta['icon'],
                        'blocks' => [],
                    ];
                }

                $grouped[$cat]['blocks'][] = [
                    'slug'           => $r['slug'],
                    'name'           => $r['name'],
                    'icon'           => $r['icon'],
                    'description'    => $r['description'] ?? '',
                    'default_config' => json_decode($r['default_config'] ?? '{}', true) ?: [],
                ];
            }

            return $grouped;
        } catch (PDOException $e) {
            $this->log('getBlockTypes error: ' . $e->getMessage());
            return [];
        }
    }

    // ══════════════════════════════════════════════════
    // CONTENU (LOAD / SAVE)
    // ══════════════════════════════════════════════════

    /**
     * Charge le contenu builder pour un contexte + entity_id
     */
    public function loadContent(string $context, int $entityId): ?array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT id, context, entity_id, layout_id, blocks_data, version, status, created_at, updated_at
                FROM builder_content
                WHERE context = ? AND entity_id = ?
                ORDER BY version DESC
                LIMIT 1
            ");
            $stmt->execute([$context, $entityId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                $row['blocks_data'] = json_decode($row['blocks_data'] ?? '[]', true) ?: [];
            }

            return $row ?: null;
        } catch (PDOException $e) {
            $this->log('loadContent error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Sauvegarde le contenu builder
     */
    public function saveContent(string $context, int $entityId, array $blocksData, int $layoutId = 0, string $status = 'draft'): bool
    {
        try {
            $jsonData = json_encode($blocksData, JSON_UNESCAPED_UNICODE);

            // Vérifier si un contenu existe déjà
            $existing = $this->loadContent($context, $entityId);

            if ($existing) {
                // Sauvegarder la révision avant mise à jour
                $this->saveRevision($existing['id'], $existing['blocks_data'], $existing['version']);

                // Mettre à jour
                $stmt = $this->db->prepare("
                    UPDATE builder_content
                    SET blocks_data = ?, layout_id = ?, version = version + 1,
                        status = ?, updated_at = NOW()
                    WHERE context = ? AND entity_id = ?
                    ORDER BY version DESC LIMIT 1
                ");
                return $stmt->execute([$jsonData, $layoutId, $status, $context, $entityId]);
            } else {
                // Créer
                $stmt = $this->db->prepare("
                    INSERT INTO builder_content (context, entity_id, layout_id, blocks_data, version, status, created_at)
                    VALUES (?, ?, ?, ?, 1, ?, NOW())
                ");
                return $stmt->execute([$context, $entityId, $layoutId, $jsonData, $status]);
            }
        } catch (PDOException $e) {
            $this->log('saveContent error: ' . $e->getMessage());
            return false;
        }
    }

    // ══════════════════════════════════════════════════
    // RÉVISIONS
    // ══════════════════════════════════════════════════

    private function saveRevision(int $contentId, array $blocksData, int $version): void
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO builder_revisions (content_id, blocks_data, version, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$contentId, json_encode($blocksData, JSON_UNESCAPED_UNICODE), $version]);
        } catch (PDOException $e) {
            $this->log('saveRevision error: ' . $e->getMessage());
        }
    }

    /**
     * Récupère l'historique des révisions
     */
    public function getRevisions(string $context, int $entityId, int $limit = 20): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT r.id, r.version, r.created_at
                FROM builder_revisions r
                JOIN builder_content c ON r.content_id = c.id
                WHERE c.context = ? AND c.entity_id = ?
                ORDER BY r.version DESC
                LIMIT ?
            ");
            $stmt->execute([$context, $entityId, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->log('getRevisions error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Restaurer une révision
     */
    public function restoreRevision(int $revisionId): ?array
    {
        try {
            $stmt = $this->db->prepare("SELECT blocks_data, version FROM builder_revisions WHERE id = ?");
            $stmt->execute([$revisionId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $row['blocks_data'] = json_decode($row['blocks_data'] ?? '[]', true) ?: [];
            }
            return $row;
        } catch (PDOException $e) {
            $this->log('restoreRevision error: ' . $e->getMessage());
            return null;
        }
    }

    // ══════════════════════════════════════════════════
    // BLOCS SAUVEGARDÉS
    // ══════════════════════════════════════════════════

    /**
     * Récupère les blocs sauvegardés pour un contexte
     */
    public function getSavedBlocks(string $context): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT id, name, block_type, block_data, context
                FROM builder_saved_blocks
                WHERE context IN (?, 'global')
                ORDER BY created_at DESC
            ");
            $stmt->execute([$context]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as &$r) {
                $r['block_data'] = json_decode($r['block_data'] ?? '{}', true) ?: [];
            }

            return $rows;
        } catch (PDOException $e) {
            $this->log('getSavedBlocks error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Sauvegarder un bloc réutilisable
     */
    public function saveBlock(string $name, string $blockType, array $blockData, string $context = 'global'): bool
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO builder_saved_blocks (name, block_type, block_data, context, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            return $stmt->execute([$name, $blockType, json_encode($blockData, JSON_UNESCAPED_UNICODE), $context]);
        } catch (PDOException $e) {
            $this->log('saveBlock error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprimer un bloc sauvegardé
     */
    public function deleteSavedBlock(int $id): bool
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM builder_saved_blocks WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            $this->log('deleteSavedBlock error: ' . $e->getMessage());
            return false;
        }
    }

    // ══════════════════════════════════════════════════
    // TEMPLATE — Charger dans l'éditeur
    // ══════════════════════════════════════════════════

    /**
     * Charge un template complet (blocs + layout)
     */
    public function loadTemplate(int $templateId): ?array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT id, name, blocks_data, layout_id
                FROM builder_templates
                WHERE id = ? AND (status = 'active' OR is_active = 1)
            ");
            $stmt->execute([$templateId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                $row['blocks_data'] = json_decode($row['blocks_data'] ?? '[]', true) ?: [];

                // Incrémenter le compteur d'utilisation
                $this->db->prepare("UPDATE builder_templates SET usage_count = usage_count + 1 WHERE id = ?")->execute([$templateId]);
            }

            return $row;
        } catch (PDOException $e) {
            $this->log('loadTemplate error: ' . $e->getMessage());
            return null;
        }
    }

    // ══════════════════════════════════════════════════
    // UTILITAIRES
    // ══════════════════════════════════════════════════

    /**
     * Récupère le titre de l'entité (page, article, secteur, capture)
     */
    public function getEntityTitle(string $context, int $entityId): string
    {
        $tables = [
            'article' => ['articles', 'blog_articles'],
            'landing' => ['pages'],
            'secteur' => ['secteurs'],
            'capture' => ['captures', 'capture_pages'],
            'header'  => ['headers'],
            'footer'  => ['footers'],
            'header'  => ['headers'],
            'footer'  => ['footers'],
        ];

        $candidates = $tables[$context] ?? [];
        foreach ($candidates as $table) {
            try {
                $col = ($table === 'captures' || $table === 'capture_pages') ? 'name' : 'title';
                $stmt = $this->db->prepare("SELECT `$col` FROM `$table` WHERE id = ?");
                $stmt->execute([$entityId]);
                $title = $stmt->fetchColumn();
                if ($title) return $title;
            } catch (PDOException $e) {
                continue;
            }
        }

        return "#{$entityId}";
    }

    private function log(string $message): void
    {
        if (function_exists('writeLog')) {
            writeLog('[BuilderController] ' . $message, 'ERROR');
        }
        error_log('[BuilderController] ' . $message);
    }
}