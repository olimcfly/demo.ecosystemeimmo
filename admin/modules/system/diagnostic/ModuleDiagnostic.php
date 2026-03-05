<?php
/**
 * ModuleDiagnostic.php
 * Diagnostic automatisé de tous les modules du CRM/CMS EcosystemeImmo
 * 
 * Place dans : admin/modules/diagnostic/
 */

class ModuleDiagnostic
{
    private $db;
    private $basePath;
    private $results = [];
    private $summary = ['total' => 0, 'ok' => 0, 'warning' => 0, 'error' => 0];

    // =========================================================================
    // CONFIGURATION : Définition de tous les modules et leurs exigences
    // =========================================================================
    private $moduleDefinitions = [

        // --- CRM & LEADS ---
        'leads' => [
            'label'       => 'Gestion des Leads',
            'category'    => 'CRM',
            'icon'        => 'fas fa-users',
            'files'       => ['index.php', 'api.php'],
            'tables'      => ['leads'],
            'api_endpoints' => ['api.php'],
            'depends_on'  => [],
        ],
        'crm' => [
            'label'       => 'CRM Dashboard',
            'category'    => 'CRM',
            'icon'        => 'fas fa-address-book',
            'files'       => ['index.php'],
            'tables'      => ['leads'],
            'api_endpoints' => [],
            'depends_on'  => ['leads'],
        ],
        'crm-pipeline' => [
            'label'       => 'Pipeline CRM',
            'category'    => 'CRM',
            'icon'        => 'fas fa-filter',
            'files'       => ['index.php', 'api.php'],
            'tables'      => ['leads'],
            'api_endpoints' => ['api.php'],
            'depends_on'  => ['leads'],
        ],
        'scoring' => [
            'label'       => 'Lead Scoring',
            'category'    => 'CRM',
            'icon'        => 'fas fa-star-half-alt',
            'files'       => ['index.php', 'api.php'],
            'tables'      => ['leads'],
            'api_endpoints' => ['api.php'],
            'depends_on'  => ['leads'],
        ],
        'contact' => [
            'label'       => 'Formulaire Contact',
            'category'    => 'CRM',
            'icon'        => 'fas fa-envelope',
            'files'       => ['index.php', 'api.php'],
            'tables'      => ['leads'],
            'api_endpoints' => ['api.php'],
            'depends_on'  => [],
        ],
        'rdv' => [
            'label'       => 'Rendez-vous',
            'category'    => 'CRM',
            'icon'        => 'fas fa-calendar-check',
            'files'       => ['index.php', 'api.php'],
            'tables'      => [],
            'api_endpoints' => ['api.php'],
            'depends_on'  => ['leads'],
        ],

        // --- CONTENU & CMS ---
        'pages' => [
            'label'       => 'Gestion des Pages',
            'category'    => 'CMS',
            'icon'        => 'fas fa-file-alt',
            'files'       => ['index.php'],
            'optional_files' => ['action.php', 'PageController.php', 'api/pages.php', 'api/check-slug.php'],
            'tables'      => ['builder_pages'],
            'api_endpoints' => ['api/pages.php'],
            'depends_on'  => [],
        ],
        'pages-capture' => [
            'label'       => 'Pages de Capture',
            'category'    => 'CMS',
            'icon'        => 'fas fa-magnet',
            'files'       => ['index.php'],
            'optional_files' => ['create.php', 'edit.php', 'delete.php', 'form.php', 'save.php',
                                 'diagnostic.php', 'diagnostic-builder.php', 'import.php', 'PageController.php'],
            'tables'      => ['capture_pages'],
            'api_endpoints' => [],
            'depends_on'  => [],
        ],
        'builder' => [
            'label'       => 'Builder Pro',
            'category'    => 'CMS',
            'icon'        => 'fas fa-cubes',
            'files'       => ['index.php', 'editor.php', 'config.php'],
            'optional_files' => [
                'create.php', 'delete.php', 'save.php', 'set-default.php',
                'headers.php', 'footers.php', 'templates.php', 'globals.php',
                'edit-header.php', 'edit-footer.php', 'fix-inject.php', 'test-headers.php',
                'api/save.php', 'api/pages.php', 'api/variables.php', 'api/delete.php',
                'api/list-pages.php', 'api/list-templates.php', 'api/load-template.php',
                'api/save-template.php', 'api/delete-template.php', 'api/rename-template.php',
                'api/set-default.php', 'api/apply-template.php',
                'api/preview.php', 'api/preview-template.php', 'api/blog-preview.php',
                'api/ai-generate.php', 'api/generate-content.php', 'api/health-check.php',
                'includes/VariablesResolver.php',
                'assets/js/builder.js', 'assets/js/templates.js', 'assets/js/templates-manager.js',
                'assets/js/blog-connector.js', 'assets/js/page-link-selector.js',
                'assets/css/builder-pages.css', 'assets/css/menu-builder-v2.css', 'assets/css/templates.css',
            ],
            'tables'      => ['builder_pages', 'builder_pages_revisions', 'builder_sections', 'builder_templates'],
            'api_endpoints' => ['api/save.php', 'api/pages.php', 'api/health-check.php'],
            'depends_on'  => [],
        ],
        'articles' => [
            'label'       => 'Articles / Blog',
            'category'    => 'CMS',
            'icon'        => 'fas fa-newspaper',
            'files'       => ['index.php', 'ArticleController.php'],
            'optional_files' => ['AIWritingController.php', '_ai_content_modal.php',
                                 'api/ai-content-generate.php', 'api/ai-field-assist.php', 'api/ai-image-generate.php'],
            'tables'      => ['articles'],
            'api_endpoints' => ['api/ai-content-generate.php'],
            'depends_on'  => [],
        ],
        'blog' => [
            'label'       => 'Blog Frontend',
            'category'    => 'CMS',
            'icon'        => 'fas fa-blog',
            'files'       => ['index.php'],
            'tables'      => ['articles'],
            'api_endpoints' => [],
            'depends_on'  => ['articles'],
        ],
        'secteurs' => [
            'label'       => 'Secteurs / Quartiers',
            'category'    => 'CMS',
            'icon'        => 'fas fa-map-marker-alt',
            'files'       => ['index.php', 'edit.php'],
            'optional_files' => ['diagnostic.php', 'api/save.php', 'api/bulk.php', 'assets/css/secteurs.css'],
            'tables'      => ['secteurs'],
            'api_endpoints' => ['api/save.php'],
            'depends_on'  => [],
        ],
        'sections' => [
            'label'       => 'Sections réutilisables',
            'category'    => 'CMS',
            'icon'        => 'fas fa-puzzle-piece',
            'files'       => ['index.php'],
            'optional_files' => ['SectionController.php'],
            'tables'      => ['builder_sections'],
            'api_endpoints' => [],
            'depends_on'  => ['builder'],
        ],
        'menus' => [
            'label'       => 'Gestion des Menus',
            'category'    => 'CMS',
            'icon'        => 'fas fa-bars',
            'files'       => ['index.php'],
            'tables'      => [],
            'api_endpoints' => [],
            'depends_on'  => [],
        ],
        'templates' => [
            'label'       => 'Templates',
            'category'    => 'CMS',
            'icon'        => 'fas fa-palette',
            'files'       => ['index.php'],
            'optional_files' => ['api/create-page.php'],
            'tables'      => ['builder_templates'],
            'api_endpoints' => [],
            'depends_on'  => ['builder'],
        ],
        'maintenance' => [
            'label'       => 'Page Maintenance',
            'category'    => 'CMS',
            'icon'        => 'fas fa-tools',
            'files'       => ['index.php'],
            'optional_files' => ['api/save.php', 'assets/public-page.php',
                                 'assets/css/maintenance.css', 'assets/js/maintenance.js'],
            'tables'      => [],
            'api_endpoints' => ['api/save.php'],
            'depends_on'  => [],
        ],

        // --- IMMOBILIER ---
        'biens' => [
            'label'       => 'Biens Immobiliers',
            'category'    => 'Immobilier',
            'icon'        => 'fas fa-home',
            'files'       => ['index.php'],
            'tables'      => ['properties'],
            'api_endpoints' => [],
            'depends_on'  => [],
        ],
        'estimation' => [
            'label'       => 'Estimations',
            'category'    => 'Immobilier',
            'icon'        => 'fas fa-calculator',
            'files'       => ['index.php'],
            'optional_files' => ['EstimationService.php', 'avisdevaleur.php',
                                 'estimation-gratuite.php', 'public.php', 'api/estimation-submit.php'],
            'tables'      => [],
            'api_endpoints' => ['api/estimation-submit.php'],
            'depends_on'  => [],
        ],
        'financement' => [
            'label'       => 'Financement',
            'category'    => 'Immobilier',
            'icon'        => 'fas fa-euro-sign',
            'files'       => ['index.php'],
            'optional_files' => ['courtiers.php', 'api/courtiers.php', 'api/leads.php'],
            'tables'      => [],
            'api_endpoints' => ['api/courtiers.php'],
            'depends_on'  => [],
        ],

        // --- SEO & ANALYTICS ---
        'seo' => [
            'label'       => 'SEO Tools',
            'category'    => 'SEO',
            'icon'        => 'fas fa-search',
            'files'       => ['index.php'],
            'optional_files' => ['api.php', 'articles.php'],
            'tables'      => [],
            'api_endpoints' => ['api.php'],
            'depends_on'  => [],
        ],
        'seo-semantic' => [
            'label'       => 'Analyse Sémantique',
            'category'    => 'SEO',
            'icon'        => 'fas fa-brain',
            'files'       => ['index.php'],
            'optional_files' => ['api.php',
                                 'tabs/overview.php', 'tabs/pages-seo.php', 'tabs/pages-serp.php',
                                 'tabs/articles-seo.php', 'tabs/articles-serp.php', 'tabs/pages-indexed.php'],
            'tables'      => [],
            'api_endpoints' => ['api.php'],
            'depends_on'  => ['articles', 'pages'],
        ],
        'local-seo' => [
            'label'       => 'SEO Local / GMB',
            'category'    => 'SEO',
            'icon'        => 'fas fa-map-pin',
            'files'       => ['index.php'],
            'optional_files' => ['tabs/guide.php', 'tabs/partners.php', 'tabs/reviews.php',
                                 'tabs/publications.php', 'tabs/questions.php'],
            'tables'      => [],
            'api_endpoints' => [],
            'depends_on'  => [],
        ],
        'analytics' => [
            'label'       => 'Analytics',
            'category'    => 'SEO',
            'icon'        => 'fas fa-chart-line',
            'files'       => [],
            'tables'      => [],
            'api_endpoints' => [],
            'depends_on'  => [],
        ],

        // --- MARKETING & SOCIAL ---
        'facebook' => [
            'label'       => 'Facebook Marketing',
            'category'    => 'Marketing',
            'icon'        => 'fab fa-facebook',
            'files'       => ['index.php'],
            'optional_files' => ['tabs/rediger.php', 'tabs/idees.php', 'tabs/journal.php', 'tabs/strategie.php'],
            'tables'      => [],
            'api_endpoints' => [],
            'depends_on'  => [],
        ],
        'tiktok' => [
            'label'       => 'TikTok Marketing',
            'category'    => 'Marketing',
            'icon'        => 'fab fa-tiktok',
            'files'       => ['index.php'],
            'optional_files' => ['tabs/scripts.php', 'tabs/idees.php', 'tabs/clonage.php',
                                 'api/save-script.php', 'api/update-status.php'],
            'tables'      => [],
            'api_endpoints' => ['api/save-script.php'],
            'depends_on'  => [],
        ],
        'ads-launch' => [
            'label'       => 'Lanceur de Pubs',
            'category'    => 'Marketing',
            'icon'        => 'fas fa-rocket',
            'files'       => ['index.php', 'AdsLaunchService.php'],
            'optional_files' => ['api/campaigns.php', 'api/audiences.php', 'api/accounts.php',
                                 'api/performance.php', 'api/prerequisites.php'],
            'tables'      => [],
            'api_endpoints' => ['api/campaigns.php'],
            'depends_on'  => [],
        ],
        'emails' => [
            'label'       => 'Email Marketing',
            'category'    => 'Marketing',
            'icon'        => 'fas fa-envelope-open-text',
            'files'       => ['index.php'],
            'tables'      => [],
            'api_endpoints' => [],
            'depends_on'  => ['leads'],
        ],
        'gmb' => [
            'label'       => 'GMB Outreach',
            'category'    => 'Marketing',
            'icon'        => 'fas fa-store',
            'files'       => ['index.php'],
            'optional_files' => [
                'contacts.php', 'sequences.php',
                'ContactController.php', 'SequenceController.php',
                'GmbScraperController.php', 'GmbEmailController.php', 'EmailValidator.php',
                'api/contacts.php', 'api/sequences.php', 'api/gmb-scraper.php',
                'api/gmb-tracking.php', 'api/email-validator.php',
                'cron/gmb-email-processor.php', 'cron/sequence-sender.php',
            ],
            'tables'      => ['gmb_contacts', 'gmb_sequences', 'gmb_sequence_emails'],
            'api_endpoints' => ['api/contacts.php', 'api/sequences.php'],
            'depends_on'  => [],
        ],
        'scraper-gmb' => [
            'label'       => 'Scraper GMB',
            'category'    => 'Marketing',
            'icon'        => 'fas fa-spider',
            'files'       => ['index.php', 'api.php'],
            'tables'      => [],
            'api_endpoints' => ['api.php'],
            'depends_on'  => [],
        ],
        'newsletters' => [
            'label'       => 'Newsletters',
            'category'    => 'Marketing',
            'icon'        => 'fas fa-mail-bulk',
            'files'       => [],
            'tables'      => [],
            'api_endpoints' => [],
            'depends_on'  => ['leads'],
        ],

        // --- IA & STRATÉGIE ---
        'ai' => [
            'label'       => 'Hub IA',
            'category'    => 'IA',
            'icon'        => 'fas fa-robot',
            'files'       => ['index.php'],
            'tables'      => [],
            'api_endpoints' => [],
            'depends_on'  => [],
        ],
        'agents' => [
            'label'       => 'Agents IA',
            'category'    => 'IA',
            'icon'        => 'fas fa-user-cog',
            'files'       => ['index.php', 'api.php'],
            'optional_files' => ['agents.json'],
            'tables'      => [],
            'api_endpoints' => ['api.php'],
            'depends_on'  => [],
        ],
        'neuropersona' => [
            'label'       => 'NeuroPersona',
            'category'    => 'IA',
            'icon'        => 'fas fa-user-astronaut',
            'files'       => ['index.php'],
            'tables'      => [],
            'api_endpoints' => [],
            'depends_on'  => [],
        ],
        'strategy' => [
            'label'       => 'Stratégie Marketing',
            'category'    => 'IA',
            'icon'        => 'fas fa-chess',
            'files'       => ['index.php', 'StrategyService.php'],
            'optional_files' => [
                'api/personas.php', 'api/sujets.php', 'api/offres.php',
                'api/communications.php', 'api/mapping.php', 'api/structures.php',
            ],
            'tables'      => [],
            'api_endpoints' => ['api/personas.php'],
            'depends_on'  => [],
        ],
        'launchpad' => [
            'label'       => 'Launchpad',
            'category'    => 'IA',
            'icon'        => 'fas fa-space-shuttle',
            'files'       => ['index.php', 'LaunchpadManager.php'],
            'optional_files' => ['LaunchpadAI.php', 'generate-offre.php', 'save-step.php', 'steps.php'],
            'tables'      => [],
            'api_endpoints' => [],
            'depends_on'  => [],
        ],

        // --- SYSTÈME ---
        'settings' => [
            'label'       => 'Paramètres',
            'category'    => 'Système',
            'icon'        => 'fas fa-cog',
            'files'       => ['index.php'],
            'optional_files' => ['site-identity.php', 'api/api-keys.php', 'api/ApiKeyManager.php',
                                 'assets/js/api-key-checker.js'],
            'tables'      => ['settings', 'api_keys'],
            'api_endpoints' => ['api/api-keys.php'],
            'depends_on'  => [],
        ],
        'design' => [
            'label'       => 'Design / Thème',
            'category'    => 'Système',
            'icon'        => 'fas fa-paint-brush',
            'files'       => ['index.php'],
            'tables'      => [],
            'api_endpoints' => [],
            'depends_on'  => [],
        ],
        'websites' => [
            'label'       => 'Multi-sites',
            'category'    => 'Système',
            'icon'        => 'fas fa-globe',
            'files'       => ['index.php'],
            'optional_files' => ['api/website.php'],
            'tables'      => [],
            'api_endpoints' => ['api/website.php'],
            'depends_on'  => [],
        ],
        'ressources' => [
            'label'       => 'Ressources / Guides',
            'category'    => 'Système',
            'icon'        => 'fas fa-book',
            'files'       => ['index.php'],
            'tables'      => [],
            'api_endpoints' => [],
            'depends_on'  => [],
        ],
    ];

    // =========================================================================
    // CONSTRUCTEUR
    // =========================================================================
    public function __construct(PDO $db, string $modulesBasePath)
    {
        $this->db = $db;
        $this->basePath = rtrim($modulesBasePath, '/');
    }

    // =========================================================================
    // LANCER LE DIAGNOSTIC COMPLET
    // =========================================================================
    public function runFullDiagnostic(): array
    {
        $this->results = [];
        $this->summary = ['total' => 0, 'ok' => 0, 'warning' => 0, 'error' => 0];

        $existingDirs = $this->scanModuleDirectories();

        foreach ($this->moduleDefinitions as $slug => $def) {
            $this->results[$slug] = $this->diagnoseModule($slug, $def, $existingDirs);
            $this->summary['total']++;

            $status = $this->results[$slug]['status'];
            if ($status === 'ok') $this->summary['ok']++;
            elseif ($status === 'warning') $this->summary['warning']++;
            else $this->summary['error']++;
        }

        // Modules orphelins
        $defined = array_keys($this->moduleDefinitions);
        $orphans = array_diff($existingDirs, $defined);
        foreach ($orphans as $orphan) {
            // Compter les fichiers dans le dossier orphelin
            $orphanPath = $this->basePath . '/' . $orphan;
            $fileCount = $this->countFiles($orphanPath);
            $hasIndex = file_exists($orphanPath . '/index.php');
            $this->results[$orphan] = [
                'label'      => ucfirst(str_replace('-', ' ', $orphan)),
                'category'   => 'Non référencé',
                'icon'       => 'fas fa-question-circle',
                'status'     => $fileCount === 0 ? 'error' : 'warning',
                'file_count' => $fileCount,
                'checks'     => [
                    [
                        'type'    => 'orphan',
                        'message' => "Dossier modules/{$orphan}/ existe mais n'est pas référencé dans le diagnostic",
                        'status'  => 'warning',
                    ],
                    [
                        'type'    => 'file_required',
                        'message' => $hasIndex ? 'index.php présent' : 'index.php ABSENT',
                        'status'  => $hasIndex ? 'ok' : 'error',
                    ],
                    [
                        'type'    => 'size',
                        'message' => "{$fileCount} fichier(s) dans le dossier",
                        'status'  => $fileCount > 0 ? 'ok' : 'error',
                    ],
                ],
            ];
            $this->summary['total']++;
            if ($fileCount === 0) $this->summary['error']++;
            else $this->summary['warning']++;
        }

        $dbHealth = $this->checkDatabaseHealth();

        return [
            'timestamp' => date('Y-m-d H:i:s'),
            'summary'   => $this->summary,
            'db_health' => $dbHealth,
            'modules'   => $this->results,
        ];
    }

    // =========================================================================
    // DIAGNOSTIC D'UN MODULE
    // =========================================================================
    private function diagnoseModule(string $slug, array $def, array $existingDirs): array
    {
        $checks = [];
        $hasError = false;
        $hasWarning = false;
        $modulePath = $this->basePath . '/' . $slug;

        // Check 1 : Dossier
        $dirExists = in_array($slug, $existingDirs);
        $checks[] = [
            'type'    => 'directory',
            'message' => $dirExists ? "Dossier modules/{$slug}/ trouvé" : "Dossier modules/{$slug}/ MANQUANT",
            'status'  => $dirExists ? 'ok' : 'error',
        ];
        if (!$dirExists) {
            return [
                'label'      => $def['label'],
                'category'   => $def['category'],
                'icon'       => $def['icon'] ?? 'fas fa-folder',
                'status'     => 'error',
                'file_count' => 0,
                'checks'     => $checks,
            ];
        }

        // Check 2 : Fichiers obligatoires
        foreach ($def['files'] as $file) {
            $filePath = $modulePath . '/' . $file;
            $exists = file_exists($filePath);
            $checks[] = [
                'type'    => 'file_required',
                'message' => $exists ? "{$file}" : "{$file} MANQUANT (requis)",
                'status'  => $exists ? 'ok' : 'error',
            ];
            if (!$exists) {
                $hasError = true;
                continue;
            }
            if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $syntax = $this->checkPhpSyntax($filePath);
                if (!$syntax['valid']) {
                    $checks[] = [
                        'type'    => 'syntax',
                        'message' => "Erreur syntaxe {$file}: " . $syntax['error'],
                        'status'  => 'error',
                    ];
                    $hasError = true;
                }
            }
        }

        // Check 3 : Fichiers optionnels
        $optionalFiles = $def['optional_files'] ?? [];
        $optionalPresent = 0;
        $optionalTotal = count($optionalFiles);
        $optionalMissing = [];
        foreach ($optionalFiles as $file) {
            $filePath = $modulePath . '/' . $file;
            if (file_exists($filePath)) {
                $optionalPresent++;
                if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                    $syntax = $this->checkPhpSyntax($filePath);
                    if (!$syntax['valid']) {
                        $checks[] = [
                            'type'    => 'syntax',
                            'message' => "Erreur syntaxe {$file}: " . $syntax['error'],
                            'status'  => 'error',
                        ];
                        $hasError = true;
                    }
                }
            } else {
                $optionalMissing[] = $file;
            }
        }
        if ($optionalTotal > 0) {
            $pct = round(($optionalPresent / $optionalTotal) * 100);
            $statusOpt = $pct >= 80 ? 'ok' : ($pct >= 50 ? 'warning' : 'error');
            $checks[] = [
                'type'    => 'files_optional',
                'message' => "Fichiers optionnels : {$optionalPresent}/{$optionalTotal} ({$pct}%)",
                'status'  => $statusOpt,
            ];
            if ($statusOpt === 'warning') $hasWarning = true;
            if ($statusOpt === 'error') $hasWarning = true; // warning not error for optional
        }

        // Check 4 : Tables DB
        foreach ($def['tables'] as $table) {
            $tableExists = $this->tableExists($table);
            $rowCount = $tableExists ? $this->tableRowCount($table) : 0;
            $checks[] = [
                'type'    => 'table',
                'message' => $tableExists
                    ? "Table `{$table}` ({$rowCount} lignes)"
                    : "Table `{$table}` MANQUANTE",
                'status'  => $tableExists ? 'ok' : 'error',
            ];
            if (!$tableExists) $hasError = true;
        }

        // Check 5 : Endpoints API
        foreach ($def['api_endpoints'] as $endpoint) {
            $apiPath = $modulePath . '/' . $endpoint;
            $exists = file_exists($apiPath);
            $checks[] = [
                'type'    => 'api',
                'message' => $exists ? "API {$endpoint}" : "API {$endpoint} MANQUANT",
                'status'  => $exists ? 'ok' : 'warning',
            ];
            if (!$exists) $hasWarning = true;
        }

        // Check 6 : Dépendances
        foreach ($def['depends_on'] as $dep) {
            $depPath = $this->basePath . '/' . $dep;
            $depOk = is_dir($depPath) && file_exists($depPath . '/index.php');
            $checks[] = [
                'type'    => 'dependency',
                'message' => $depOk ? "Dépendance `{$dep}` OK" : "Dépendance `{$dep}` NON FONCTIONNELLE",
                'status'  => $depOk ? 'ok' : 'warning',
            ];
            if (!$depOk) $hasWarning = true;
        }

        // Check 7 : Nombre de fichiers
        $fileCount = $this->countFiles($modulePath);
        $checks[] = [
            'type'    => 'size',
            'message' => "{$fileCount} fichier(s) au total",
            'status'  => $fileCount > 0 ? 'ok' : 'warning',
        ];

        $status = 'ok';
        if ($hasWarning) $status = 'warning';
        if ($hasError) $status = 'error';

        return [
            'label'      => $def['label'],
            'category'   => $def['category'],
            'icon'       => $def['icon'] ?? 'fas fa-folder',
            'status'     => $status,
            'file_count' => $fileCount,
            'checks'     => $checks,
        ];
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    private function scanModuleDirectories(): array
    {
        $dirs = [];
        if (is_dir($this->basePath)) {
            foreach (scandir($this->basePath) as $item) {
                if ($item[0] !== '.' && is_dir($this->basePath . '/' . $item)) {
                    $dirs[] = $item;
                }
            }
        }
        return $dirs;
    }

    private function checkPhpSyntax(string $filepath): array
    {
        $output = [];
        $returnCode = 0;
        exec("php -l " . escapeshellarg($filepath) . " 2>&1", $output, $returnCode);
        return [
            'valid' => $returnCode === 0,
            'error' => $returnCode !== 0 ? implode(' ', $output) : null,
        ];
    }

    private function tableExists(string $table): bool
    {
        try {
            $stmt = $this->db->query("SHOW TABLES LIKE " . $this->db->quote($table));
            return $stmt->rowCount() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function tableRowCount(string $table): int
    {
        try {
            $stmt = $this->db->query("SELECT COUNT(*) FROM `" . str_replace('`', '', $table) . "`");
            return (int) $stmt->fetchColumn();
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function countFiles(string $dir): int
    {
        if (!is_dir($dir)) return 0;
        $count = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if ($file->isFile()) $count++;
        }
        return $count;
    }

    private function checkDatabaseHealth(): array
    {
        $results = [];
        try {
            $this->db->query("SELECT 1");
            $results[] = ['check' => 'Connexion DB', 'status' => 'ok', 'value' => 'Active'];

            $stmt = $this->db->query("SHOW TABLES");
            $tables = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            $results[] = ['check' => 'Nombre de tables', 'status' => 'ok', 'value' => count($tables)];

            $criticalTables = [
                'leads', 'builder_pages', 'builder_pages_revisions', 'builder_sections',
                'builder_templates', 'properties', 'capture_pages', 'articles',
                'settings', 'admins', 'api_keys', 'secteurs',
            ];
            foreach ($criticalTables as $ct) {
                $exists = in_array($ct, $tables);
                $results[] = [
                    'check'  => "Table `{$ct}`",
                    'status' => $exists ? 'ok' : 'warning',
                    'value'  => $exists ? $this->tableRowCount($ct) . ' lignes' : 'ABSENTE',
                ];
            }

            $dbName = $this->db->query("SELECT DATABASE()")->fetchColumn();
            $stmt = $this->db->query("
                SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
                FROM information_schema.tables
                WHERE table_schema = " . $this->db->quote($dbName)
            );
            $size = $stmt->fetchColumn();
            $results[] = ['check' => 'Taille DB', 'status' => 'ok', 'value' => $size . ' MB'];

        } catch (\Exception $e) {
            $results[] = ['check' => 'Connexion DB', 'status' => 'error', 'value' => $e->getMessage()];
        }

        return $results;
    }
}