<?php
/**
 * ============================================================
 *  AiPromptBuilder — Constructeur de prompts centralisé
 *  Fichier : core/ai/AiPromptBuilder.php
 * ============================================================
 *
 *  Centralise :
 *    → Les contextes système par module (persona Eduardo)
 *    → Les templates de schemas JSON attendus
 *    → Les helpers partagés (extractJson, slug, withMarketData)
 *
 *  Usage :
 *    $system = AiPromptBuilder::context('articles');
 *    $prompt = AiPromptBuilder::json($instructions, $schemaJson);
 *    $parsed = AiPromptBuilder::extractJson($aiResponse);
 *    $slug   = AiPromptBuilder::slug("Acheter à Bordeaux");
 *    $prompt = AiPromptBuilder::withMarketData($prompt, $perplexityContent);
 * ============================================================
 */

declare(strict_types=1);

class AiPromptBuilder
{
    // =========================================================================
    //  Contextes système par module
    //  Chaque module a sa persona spécialisée + le contexte de base d'Eduardo
    // =========================================================================
    private static array $contexts = [

        // ── Contexte de base : toujours inclus ───────────────────────────────
        '_base' => "Tu travailles pour Eduardo De Sul, conseiller immobilier indépendant "
                 . "avec le réseau eXp France, basé à Bordeaux.\n"
                 . "Zone d'intervention : Bordeaux et son agglomération (Mérignac, Pessac, "
                 . "Talence, Gradignan, Bègles) ainsi que le Médoc.\n"
                 . "Toujours répondre en français impeccable. Données chiffrées réelles si possible.",

        // ── Articles / Blog ───────────────────────────────────────────────────
        'articles' =>
            "Tu es le rédacteur expert d'Eduardo De Sul, conseiller immobilier eXp France Bordeaux.\n"
          . "Style : professionnel mais accessible, chaleureux, ancré dans la réalité bordelaise.\n"
          . "Framework MERE : Miroir (projection lecteur) → Émotion → Réassurance → Exclusivité.\n"
          . "Richesse sémantique cible : 50-70%. Données chiffrées réelles. Pas de jargon inutile.\n"
          . "Toujours inclure des références locales : quartiers, transports, prix au m² réels.",

        // ── Biens immobiliers ─────────────────────────────────────────────────
        'biens' =>
            "Tu es expert en rédaction d'annonces immobilières pour Eduardo De Sul, eXp France Bordeaux.\n"
          . "Framework MERE : le bien doit faire se projeter l'acheteur dès la 1ère phrase.\n"
          . "Ton vendeur mais factuel : pas de superlatifs vides (\"magnifique\", \"exceptionnel\" seuls).\n"
          . "Mettre en avant : localisation précise, transports, commodités, vie de quartier bordelaise.\n"
          . "Chaque description = bénéfice client, pas une liste de caractéristiques techniques.",

        // ── Leads / CRM ───────────────────────────────────────────────────────
        'leads' =>
            "Tu assistes Eduardo De Sul, conseiller immobilier indépendant eXp France Bordeaux.\n"
          . "Approche commerciale : conseil personnalisé, humain, sans pression.\n"
          . "Communications : chaleureuses, professionnelles, toujours en français.\n"
          . "Signature systématique : \"Eduardo De Sul | Conseiller Immobilier | eXp France Bordeaux\"\n"
          . "Objectif : créer de la confiance, pas conclure à tout prix.",

        // ── SEO ───────────────────────────────────────────────────────────────
        'seo' =>
            "Tu es expert SEO immobilier spécialisé sur le marché français et bordelais.\n"
          . "Objectif : positionner Eduardo De Sul en top 3 Google sur les requêtes immobilières locales.\n"
          . "Tu maîtrises : SEO technique, sémantique TF-IDF, SEO local (Google My Business, NAP), "
          . "Core Web Vitals, Schema.org (RealEstateListing, LocalBusiness, FAQPage).\n"
          . "Recommandations : actionnables, prioritisées impact/effort, avec exemples concrets.",

        // ── Social Media ──────────────────────────────────────────────────────
        'social' =>
            "Tu crées du contenu social media pour Eduardo De Sul, conseiller immobilier eXp France Bordeaux.\n"
          . "Ligne éditoriale : expertise accessible, conseils pratiques, transparence sur le marché local.\n"
          . "Ton : authentique, expert sans être condescendant, ancré dans le quotidien bordelais.\n"
          . "Objectifs : confiance, visibilité locale, incitation à prendre contact avec Eduardo.\n"
          . "Adapter le format à chaque plateforme (longueur, emojis, hashtags, CTA).",

        // ── Google My Business ────────────────────────────────────────────────
        'gmb' =>
            "Tu es expert Google My Business et SEO local pour Eduardo De Sul, eXp France Bordeaux.\n"
          . "Objectif principal : apparaître dans le Local Pack Google (top 3) sur les requêtes "
          . "immobilières bordelaises.\n"
          . "Eduardo prospecte aussi des professionnels B2B (notaires, architectes, syndics de copropriété, "
          . "promoteurs, artisans) via GMB pour construire un réseau d'apporteurs d'affaires.\n"
          . "Chaque contenu GMB = signal local + mot-clé immobilier + CTA clair.",

        // ── Pages de capture / Landing pages ─────────────────────────────────
        'captures' =>
            "Tu es expert en conversion et copywriting immobilier pour Eduardo De Sul, eXp France Bordeaux.\n"
          . "Framework principal : AIDA combiné avec MERE pour convertir les visiteurs en leads qualifiés.\n"
          . "Cibles : propriétaires souhaitant vendre, acheteurs primo-accédants, investisseurs locatifs.\n"
          . "Règle d'or : 1 page = 1 objectif = 1 CTA principal.\n"
          . "Leviers psychologiques : preuve sociale, urgence douce, exclusivité, autorité d'expert.",
    ];

    // =========================================================================
    //  Récupérer un contexte système
    // =========================================================================
    /**
     * Retourne le contexte système pour un module donné.
     * Combine toujours le contexte de base + le contexte spécifique du module.
     *
     * @param  string $module  ex: 'articles', 'leads', 'seo'
     * @return string
     */
    public static function context(string $module): string
    {
        $base    = self::$contexts['_base']   ?? '';
        $specific = self::$contexts[$module] ?? '';

        return trim($base . "\n\n" . $specific);
    }

    // =========================================================================
    //  Forcer un format JSON en sortie
    // =========================================================================
    /**
     * Ajoute des instructions JSON strictes à un prompt.
     * L'IA devra répondre UNIQUEMENT avec le JSON correspondant au schéma fourni.
     *
     * @param  string $instructions   Instructions métier
     * @param  string $schemaJson     Exemple de structure JSON attendue (string)
     * @return string                 Prompt complet avec instructions JSON
     */
    public static function json(string $instructions, string $schemaJson): string
    {
        return $instructions
             . "\n\n---\n"
             . "**IMPORTANT — Format de réponse obligatoire :**\n"
             . "Réponds UNIQUEMENT avec un JSON valide respectant exactement cette structure.\n"
             . "Pas de texte avant, pas de texte après, pas de balises markdown.\n"
             . "```json\n{$schemaJson}\n```";
    }

    // =========================================================================
    //  Extraire le JSON d'une réponse IA
    // =========================================================================
    /**
     * Tente d'extraire et parser un JSON depuis une réponse IA.
     * Gère tous les formats courants : ```json...```, ```...```, {}, []
     *
     * @param  string     $text  Texte brut retourné par l'IA
     * @return array|null        Tableau PHP ou null si aucun JSON trouvable
     */
    public static function extractJson(string $text): ?array
    {
        $text = trim($text);

        // 1. Entre ```json ... ```
        if (preg_match('/```json\s*([\s\S]*?)\s*```/i', $text, $m)) {
            $decoded = json_decode(trim($m[1]), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        // 2. Entre ``` ... ```
        if (preg_match('/```\s*([\s\S]*?)\s*```/', $text, $m)) {
            $decoded = json_decode(trim($m[1]), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        // 3. JSON objet brut { ... } (le plus grand bloc possible)
        if (preg_match('/(\{[\s\S]*\})/u', $text, $m)) {
            $decoded = json_decode($m[1], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        // 4. JSON tableau brut [ ... ]
        if (preg_match('/(\[[\s\S]*\])/u', $text, $m)) {
            $decoded = json_decode($m[1], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return null;
    }

    // =========================================================================
    //  Utilitaires
    // =========================================================================

    /**
     * Génère un slug SEO-friendly depuis un texte français.
     * Gère les accents, cédilles, caractères spéciaux.
     *
     * @param  string $text  ex: "Acheter à Bordeaux en 2025"
     * @return string        ex: "acheter-a-bordeaux-en-2025"
     */
    public static function slug(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');

        // Translittération des caractères français
        $map = [
            'à'=>'a','â'=>'a','ä'=>'a',
            'é'=>'e','è'=>'e','ê'=>'e','ë'=>'e',
            'î'=>'i','ï'=>'i',
            'ô'=>'o','ö'=>'o',
            'ù'=>'u','û'=>'u','ü'=>'u',
            'ç'=>'c','ñ'=>'n',
            'æ'=>'ae','œ'=>'oe',
        ];
        $text = strtr($text, $map);

        // Supprimer les caractères non alphanumériques (sauf tirets et espaces)
        $text = preg_replace('/[^a-z0-9\s\-]/', '', $text);

        // Remplacer espaces multiples et tirets par un seul tiret
        $text = preg_replace('/[\s\-]+/', '-', trim($text));

        return trim($text, '-');
    }

    /**
     * Enrichit un prompt avec des données marché Perplexity.
     * Si perplexityContent est vide, retourne le prompt original inchangé.
     *
     * @param  string $basePrompt          Prompt de base
     * @param  string $perplexityContent   Résultat de AiClient::perplexity()
     * @param  int    $maxChars            Tronquer les données marché à N caractères
     * @return string
     */
    public static function withMarketData(
        string $basePrompt,
        string $perplexityContent,
        int    $maxChars = 600
    ): string {
        if (empty(trim($perplexityContent))) {
            return $basePrompt;
        }

        $excerpt = substr($perplexityContent, 0, $maxChars);

        return $basePrompt
             . "\n\n---\n"
             . "**Données marché récentes (à intégrer naturellement dans ta réponse) :**\n"
             . $excerpt;
    }

    // =========================================================================
    //  Schémas JSON prédéfinis (réutilisables entre modules)
    // =========================================================================

    /**
     * Schéma pour la génération de métadonnées SEO.
     */
    public static function metaSchema(): string
    {
        return <<<JSON
{
  "meta_title": "... (50-60 caractères, mot-clé principal + marque)",
  "meta_description": "... (150-160 caractères, accrocheur + CTA implicite)",
  "slug": "...",
  "og_title": "... (60-70 caractères)",
  "og_description": "... (max 200 caractères)",
  "focus_keyword": "..."
}
JSON;
    }

    /**
     * Schéma pour une FAQ Schema.org.
     *
     * @param int $count Nombre de questions à générer
     */
    public static function faqSchema(int $count = 5): string
    {
        $items = implode(",\n    ", array_map(
            fn($i) => '{"question": "...", "answer": "... (2-4 phrases complètes, réponse directe)"}',
            range(1, $count)
        ));

        return <<<JSON
{
  "faq": [
    {$items}
  ]
}
JSON;
    }

    /**
     * Schéma pour un email simple.
     */
    public static function emailSchema(): string
    {
        return <<<JSON
{
  "subject": "...",
  "preheader": "... (max 90 caractères)",
  "body_html": "... (HTML avec <p>, <strong>, <a>)",
  "body_text": "... (version texte brut)",
  "cta_text": "...",
  "cta_url": "{{URL_CALENDRIER}}",
  "ps": "..."
}
JSON;
    }
}