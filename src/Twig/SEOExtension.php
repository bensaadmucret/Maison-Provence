<?php

namespace App\Twig;

use App\Entity\Product;
use App\Service\SEOService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SEOExtension extends AbstractExtension
{
    public function __construct(
        private readonly SEOService $seoService
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('seo_meta_title', [$this, 'getMetaTitle']),
            new TwigFunction('seo_meta_description', [$this, 'getMetaDescription']),
            new TwigFunction('seo_robots_meta', [$this, 'getRobotsMeta']),
            new TwigFunction('seo_canonical_url', [$this, 'getCanonicalUrl']),
            new TwigFunction('seo_open_graph_data', [$this, 'getOpenGraphData']),
        ];
    }

    public function getMetaTitle(Product $product): string
    {
        return $this->seoService->generateMetaTitle($product);
    }

    public function getMetaDescription(Product $product): string
    {
        return $this->seoService->generateMetaDescription($product);
    }

    public function getRobotsMeta(Product $product): string
    {
        return $this->seoService->generateRobotsMeta($product);
    }

    public function getCanonicalUrl(Product $product): ?string
    {
        return $product->getSeo()?->getCanonicalUrl();
    }

    public function getOpenGraphData(Product $product): array
    {
        return $product->getSeo()?->getOpenGraphData() ?? [];
    }
}
