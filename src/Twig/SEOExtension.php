<?php

namespace App\Twig;

use App\Entity\SEO;
use App\Service\SEOService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SEOExtension extends AbstractExtension
{
    public function __construct(
        private SEOService $seoService
    ) {
    }

    /**
     * @return array<string, TwigFunction>
     */
    public function getFunctions(): array
    {
        return [
            'seo_meta_title' => new TwigFunction('seo_meta_title', [$this, 'getMetaTitle']),
            'seo_meta_description' => new TwigFunction('seo_meta_description', [$this, 'getMetaDescription']),
            'seo_meta_keywords' => new TwigFunction('seo_meta_keywords', [$this, 'getMetaKeywords']),
            'seo_canonical_url' => new TwigFunction('seo_canonical_url', [$this, 'getCanonicalUrl']),
            'seo_robots' => new TwigFunction('seo_robots', [$this, 'getRobots']),
            'seo_open_graph' => new TwigFunction('seo_open_graph', [$this, 'getOpenGraphData']),
            'seo_twitter_card' => new TwigFunction('seo_twitter_card', [$this, 'getTwitterCardData']),
        ];
    }

    public function getMetaTitle(SEO $seo): string
    {
        return $seo->getMetaTitle() ?? '';
    }

    public function getMetaDescription(SEO $seo): string
    {
        return $seo->getMetaDescription() ?? '';
    }

    /**
     * @return array<string>
     */
    public function getMetaKeywords(SEO $seo): array
    {
        return $seo->getMetaKeywords() ?? [];
    }

    public function getCanonicalUrl(SEO $seo): string
    {
        return $seo->getCanonicalUrl() ?? '';
    }

    public function getRobots(SEO $seo): string
    {
        return $seo->getRobots() ?? 'index, follow';
    }

    /**
     * @return array<string, string>
     */
    public function getOpenGraphData(SEO $seo): array
    {
        $data = $seo->getOpenGraphData() ?? [];
        return array_map(fn ($value) => (string) $value, $data);
    }

    /**
     * @return array<string, string>
     */
    public function getTwitterCardData(SEO $seo): array
    {
        $data = $seo->getTwitterCardData() ?? [];
        return array_map(fn ($value) => (string) $value, $data);
    }
}
