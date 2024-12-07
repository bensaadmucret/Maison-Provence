<?php

namespace App\Service;

use App\DTO\SEODTO;
use App\Entity\Category;
use App\Entity\CategorySEO;
use App\Entity\PageSEO;
use App\Entity\Product;
use App\Entity\ProductSEO;
use App\Entity\SEO;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SEOService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function createOrUpdateSEO(Product|Category|PageSEO $entity, SEODTO $seoDTO): void
    {
        // Vérifier si l'entité existe déjà
        $seo = match (true) {
            $entity instanceof Product => $this->createOrUpdateProductSEO($entity, $seoDTO),
            $entity instanceof Category => $this->createOrUpdateCategorySEO($entity, $seoDTO),
            $entity instanceof PageSEO => $entity,
            default => throw new \InvalidArgumentException('Invalid entity type'),
        };

        // Validation des métadonnées SEO
        $seoErrors = $this->validateSEOMetadata($seoDTO);
        if (!empty($seoErrors)) {
            // Gérer les erreurs de validation
            throw new \InvalidArgumentException(implode(', ', $seoErrors));
        }

        $seo->setMetaTitle($seoDTO->getMetaTitle() ?? '');
        $seo->setMetaDescription($seoDTO->getMetaDescription() ? substr((string) $seoDTO->getMetaDescription(), 0, 160) : '');

        // Gestion des mots-clés
        $keywords = $seoDTO->getMetaKeywords();
        if (is_array($keywords)) {
            $keywords = implode(', ', array_filter($keywords));
        }
        $seo->setMetaKeywords($keywords ? trim($keywords) : null);

        $this->setCanonicalUrl($seo, $seoDTO->getCanonicalUrl() ?? '');
        $seo->setIndexable($seoDTO->isIndexable() ?? true);
        $seo->setFollowable($seoDTO->isFollowable() ?? true);

        $this->entityManager->persist($seo);
        $this->entityManager->flush();
    }

    private function createOrUpdateProductSEO(Product $product, SEODTO $seoDTO): ProductSEO
    {
        $seo = $product->getSeo();

        if (!$seo) {
            $seo = new ProductSEO();
            $seo->setProduct($product);
            $product->setSeo($seo);
        }

        $this->updateProductSEO($seo, $product);

        return $seo;
    }

    private function createOrUpdateCategorySEO(Category $category, SEODTO $seoDTO): CategorySEO
    {
        $seo = $category->getSeo();

        if (!$seo) {
            $seo = new CategorySEO();
            $seo->setCategory($category);
            $category->setSeo($seo);
        }

        $this->updateCategorySEO($seo, $category);

        return $seo;
    }

    private function updateProductSEO(ProductSEO $seo, Product $product): void
    {
        $mainImage = $product->getMainImage();
        $ogImage = $mainImage ? $mainImage->getPath() : null;

        $openGraphData = [
            'title' => $product->getName(),
            'description' => $product->getDescription() ?? '',
            'type' => 'product',
            'url' => $this->generateCanonicalUrl($seo) ?? '',
            'image' => $ogImage ?? '',
        ];

        $this->setOpenGraphData($seo, $openGraphData);
    }

    private function updateCategorySEO(CategorySEO $seo, Category $category): void
    {
        $openGraphData = [
            'title' => $category->getName(),
            'description' => $category->getDescription() ?? '',
            'type' => 'category',
            'url' => $this->generateCanonicalUrl($seo) ?? '',
        ];

        $this->setOpenGraphData($seo, $openGraphData);
    }

    private function setOpenGraphData(SEO $seo, array $openGraphData): void
    {
        // Nettoyer et filtrer les données OpenGraph
        $cleanedData = array_filter($openGraphData, function ($value) {
            return null !== $value && '' !== $value;
        });

        $seo->setOpenGraphData($cleanedData);
        $this->entityManager->flush();
    }

    private function createSEO(Product|Category|string $entity): ProductSEO|CategorySEO|PageSEO
    {
        if ($entity instanceof Product) {
            $seo = new ProductSEO();
            $seo->setProduct($entity);

            return $seo;
        }

        if ($entity instanceof Category) {
            $seo = new CategorySEO();
            $seo->setCategory($entity);

            return $seo;
        }

        if (is_string($entity)) {
            $existingSeo = $this->entityManager
                ->getRepository(PageSEO::class)
                ->findOneBy(['identifier' => $entity]);

            if ($existingSeo) {
                return $existingSeo;
            }

            $seo = new PageSEO();
            $seo->setIdentifier($entity);

            return $seo;
        }

        throw new \InvalidArgumentException('Invalid entity type');
    }

    public function generateProductSEO(Product $product): ProductSEO
    {
        $seo = new ProductSEO();

        $title = $product->getName();
        $description = $product->getDescription();
        if (null !== $description) {
            $description = substr($description, 0, 160);
        }

        $mainImage = $product->getMainImage();
        $imageUrl = $mainImage ? $mainImage->getPath() : null;

        $seo->setMetaTitle($title);
        $seo->setMetaDescription($description);
        $seo->setMetaKeywords('');  // Initialiser avec une chaîne vide
        $seo->setIndexable(true);
        $seo->setFollowable(true);

        // Générer l'URL canonique
        $canonicalUrl = $this->urlGenerator->generate('app_product_show', [
            'id' => $product->getId(),
            'slug' => $product->getSlug(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);
        $seo->setCanonicalUrl($canonicalUrl);

        // Configurer les données Open Graph
        $openGraphData = [
            'title' => $title,
            'description' => $description ?? '',
            'type' => 'product',
            'url' => $canonicalUrl,
        ];
        if ($imageUrl) {
            $openGraphData['image'] = $imageUrl;
        }
        $seo->setOpenGraphData($openGraphData);

        $this->entityManager->persist($seo);
        $this->entityManager->flush();

        return $seo;
    }

    public function generateCategorySEO(Category $category): CategorySEO
    {
        $seo = new CategorySEO();

        $name = $category->getName();
        $description = $category->getDescription();
        if (null !== $description) {
            $description = substr($description, 0, 160);
        }

        $seo->setMetaTitle($name);
        $seo->setMetaDescription($description);
        $seo->setMetaKeywords('');  // Initialiser avec une chaîne vide
        $seo->setIndexable(true);
        $seo->setFollowable(true);

        // Générer l'URL canonique
        $canonicalUrl = $this->urlGenerator->generate('app_category_show', [
            'id' => $category->getId(),
            'slug' => $category->getSlug(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);
        $seo->setCanonicalUrl($canonicalUrl);

        // Configurer les données Open Graph
        $openGraphData = [
            'title' => $name,
            'description' => $description ?? '',
            'type' => 'category',
            'url' => $canonicalUrl,
        ];
        $seo->setOpenGraphData($openGraphData);

        $this->entityManager->persist($seo);
        $this->entityManager->flush();

        return $seo;
    }

    public function generateMetaTitle(Product $product): string
    {
        $seo = $product->getSeo();
        if ($seo && $seo->getMetaTitle()) {
            return $seo->getMetaTitle();
        }

        $category = $product->getCategory();
        if (!$category) {
            return $product->getName();
        }

        return sprintf('%s - %s', $product->getName(), $category->getName() ?? '');
    }

    public function generateMetaDescription(Product $product): string
    {
        $seo = $product->getSeo();
        if ($seo && $seo->getMetaDescription()) {
            return $seo->getMetaDescription();
        }

        $description = $product->getDescription();
        if (null === $description) {
            return '';
        }

        return substr($description, 0, 160);
    }

    public function generateRobotsMeta(Product $product): string
    {
        $seo = $product->getSeo();
        if (!$seo) {
            return 'index, follow';
        }

        $directives = [];

        if ($seo->isIndexable()) {
            $directives[] = 'index';
        } else {
            $directives[] = 'noindex';
        }

        if ($seo->isFollowable()) {
            $directives[] = 'follow';
        } else {
            $directives[] = 'nofollow';
        }

        return implode(', ', $directives);
    }

    private function setMetaKeywords(SEO $seo, ?string $keywords): void
    {
        if (null !== $keywords) {
            // Nettoyage des espaces superflus
            $keywords = preg_replace('/\s*,\s*/', ', ', trim($keywords));
        }

        $seo->setMetaKeywords($keywords);
        $this->entityManager->flush();
    }

    public function setMetaDescription(SEO $seo, string $description): void
    {
        $seo->setMetaDescription($description);
        $this->entityManager->flush();
    }

    public function setCanonicalUrl(SEO $seo, string $url): void
    {
        // Validation et nettoyage de l'URL
        $url = filter_var($url, FILTER_VALIDATE_URL)
            ? $url
            : $this->generateCanonicalUrl($seo);

        $seo->setCanonicalUrl($url);
        $this->entityManager->flush();
    }

    public function setRobots(SEO $seo, string $robots): void
    {
        $seo->setRobots($robots);
        $this->entityManager->flush();
    }

    /**
     * @param array<string, string> $data
     */
    public function setTwitterCardData(SEO $seo, array $data): void
    {
        $seo->setTwitterCardData($data);
        $this->entityManager->flush();
    }

    public function getPageSEO(string $identifier): ?PageSEO
    {
        return $this->entityManager
            ->getRepository(PageSEO::class)
            ->findOneBy(['identifier' => $identifier]);
    }

    public function createPageSEO(string $identifier, string $title, string $description): PageSEO
    {
        // Vérifier si une page SEO existe déjà
        $existingSeo = $this->entityManager
            ->getRepository(PageSEO::class)
            ->findOneBy(['identifier' => $identifier]);

        if ($existingSeo) {
            // Mettre à jour l'existant plutôt que de créer un nouveau
            $existingSeo->setMetaTitle($title);
            $existingSeo->setMetaDescription($description);
            $existingSeo->setIndexable(true);
            $existingSeo->setFollowable(true);
            $this->entityManager->flush();

            return $existingSeo;
        }

        // Créer une nouvelle page SEO
        $seo = new PageSEO();
        $seo->setIdentifier($identifier);
        $seo->setMetaTitle($title);
        $seo->setMetaDescription($description);
        $seo->setIndexable(true);
        $seo->setFollowable(true);

        $this->entityManager->persist($seo);
        $this->entityManager->flush();

        return $seo;
    }

    /**
     * Génère des mots-clés automatiquement à partir du contenu.
     *
     * @param string $content     Contenu à analyser
     * @param int    $maxKeywords Nombre maximum de mots-clés
     *
     * @return array Mots-clés générés
     */
    public function generateKeywords(string $content, int $maxKeywords = 10): array
    {
        // Nettoyer et normaliser le texte
        $content = strtolower($content);
        $content = preg_replace('/[^\p{L}\p{N}\s]/u', '', $content);

        // Supprimer les mots courants
        $stopWords = [
            'le', 'la', 'les', 'un', 'une', 'des', 'de', 'du', 'et', 'ou', 'mais',
            'donc', 'car', 'ni', 'je', 'tu', 'il', 'elle', 'nous', 'vous', 'ils',
            'en', 'à', 'au', 'aux', 'par', 'pour', 'avec', 'sans', 'sur', 'sous',
        ];

        // Tokeniser et compter les mots
        $words = str_word_count($content, 1, '0123456789');
        $words = array_diff($words, $stopWords);

        // Compter les occurrences
        $wordCounts = array_count_values($words);
        arsort($wordCounts);

        // Sélectionner les mots-clés
        $keywords = array_slice(array_keys($wordCounts), 0, $maxKeywords);

        return $keywords;
    }

    /**
     * Valide la longueur des balises meta.
     *
     * @return array Erreurs de validation
     */
    public function validateSEOMetadata(SEODTO $seoDTO): array
    {
        $errors = [];

        // Validation du titre
        if (strlen($seoDTO->getMetaTitle() ?? '') > 60) {
            $errors[] = 'Le titre meta doit faire moins de 60 caractères';
        }

        // Validation de la description
        if (strlen($seoDTO->getMetaDescription() ?? '') > 160) {
            $errors[] = 'La description meta doit faire moins de 160 caractères';
        }

        // Validation des mots-clés
        $keywords = $seoDTO->getMetaKeywords();
        if (is_array($keywords) && count($keywords) > 10) {
            $errors[] = 'Maximum 10 mots-clés autorisés';
        }

        return $errors;
    }

    /**
     * Génère une URL canonique par défaut.
     */
    private function generateCanonicalUrl(SEO $seo): string
    {
        // Logique de génération d'URL canonique
        // Peut être basée sur l'entité associée (produit, catégorie, page)
        try {
            return match (true) {
                $seo instanceof ProductSEO => $this->urlGenerator->generate('product_detail', [
                    'slug' => $seo->getProduct()->getSlug(),
                ], UrlGeneratorInterface::ABSOLUTE_URL),

                $seo instanceof CategorySEO => $this->urlGenerator->generate('category_detail', [
                    'slug' => $seo->getCategory()->getSlug(),
                ], UrlGeneratorInterface::ABSOLUTE_URL),

                $seo instanceof PageSEO => $this->urlGenerator->generate($seo->getIdentifier(), [], UrlGeneratorInterface::ABSOLUTE_URL),

                default => '',
            };
        } catch (\Exception $e) {
            // Fallback si la génération échoue
            return '';
        }
    }
}
