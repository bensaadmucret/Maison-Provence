<?php

namespace App\Tests\Service\SEO;

use App\DTO\SEODTO;
use App\Entity\LegalPage;
use App\Entity\PageSEO;
use App\Service\SEO\PageSEOService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PageSEOServiceTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private UrlGeneratorInterface $urlGenerator;
    private PageSEOService $seoService;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);

        $this->seoService = new PageSEOService(
            $this->entityManager,
            $this->urlGenerator
        );
    }

    public function testCreateNewPageSEO(): void
    {
        // Arrange
        $page = new LegalPage();
        $page->setTitle('Mentions légales');
        $page->setSlug('mentions-legales');

        $seoDTO = new SEODTO();
        $seoDTO->setMetaTitle('Mentions légales - Maison Provence');
        $seoDTO->setMetaDescription('Consultez nos mentions légales');
        $seoDTO->setMetaKeywords(['mentions légales', 'conditions']);

        $this->urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with(
                'legal_page_show',
                ['slug' => 'mentions-legales'],
                UrlGeneratorInterface::ABSOLUTE_URL
            )
            ->willReturn('https://maison-provence.fr/mentions-legales');

        // Act
        $seo = $this->seoService->createOrUpdatePageSEO($page, $seoDTO);

        // Assert
        $this->assertInstanceOf(PageSEO::class, $seo);
        $this->assertEquals('Mentions légales - Maison Provence', $seo->getMetaTitle());
        $this->assertEquals('Consultez nos mentions légales', $seo->getMetaDescription());
        $this->assertEquals('mentions-legales', $seo->getIdentifier());
        $this->assertEquals('https://maison-provence.fr/mentions-legales', $seo->getCanonicalUrl());
    }

    public function testUpdateExistingPageSEO(): void
    {
        // Arrange
        $page = new LegalPage();
        $page->setTitle('CGV');
        $page->setSlug('cgv');

        $existingSeo = new PageSEO();
        $existingSeo->setMetaTitle('Ancien titre');
        $existingSeo->setMetaDescription('Ancienne description');
        $page->setSeo($existingSeo);

        $seoDTO = new SEODTO();
        $seoDTO->setMetaTitle('Nouveau titre');
        $seoDTO->setMetaDescription('Nouvelle description');

        // Act
        $seo = $this->seoService->createOrUpdatePageSEO($page, $seoDTO);

        // Assert
        $this->assertSame($existingSeo, $seo);
        $this->assertEquals('Nouveau titre', $seo->getMetaTitle());
        $this->assertEquals('Nouvelle description', $seo->getMetaDescription());
    }

    public function testValidationErrorOnTooLongMetaTitle(): void
    {
        // Arrange
        $page = new LegalPage();
        $seoDTO = new SEODTO();
        $seoDTO->setMetaTitle(str_repeat('a', 70)); // Titre trop long

        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Meta title should not exceed 60 characters');

        // Act
        $this->seoService->createOrUpdatePageSEO($page, $seoDTO);
    }

    public function testValidationErrorOnTooLongMetaDescription(): void
    {
        // Arrange
        $page = new LegalPage();
        $seoDTO = new SEODTO();
        $seoDTO->setMetaDescription(str_repeat('a', 170)); // Description trop longue

        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Meta description should not exceed 160 characters');

        // Act
        $this->seoService->createOrUpdatePageSEO($page, $seoDTO);
    }
}
