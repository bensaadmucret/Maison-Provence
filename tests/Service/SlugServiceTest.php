<?php

namespace App\Tests\Service;

use App\Service\SlugService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\String\Slugger\AsciiSlugger;

class SlugServiceTest extends TestCase
{
    private SlugService $slugService;

    protected function setUp(): void
    {
        $this->slugService = new SlugService(new AsciiSlugger());
    }

    /**
     * @dataProvider slugDataProvider
     */
    public function testGenerateSlug(string $input, string $expected): void
    {
        $this->assertEquals($expected, $this->slugService->generateSlug($input));
    }

    public function slugDataProvider(): array
    {
        return [
            'simple text' => ['Hello World', 'hello-world'],
            'accented characters' => ['Café crème', 'cafe-creme'],
            'special characters' => ['Product & Category', 'product-category'],
            'multiple spaces' => ['Product    Name', 'product-name'],
            'numbers' => ['Product 123', 'product-123'],
            'mixed case' => ['ProDuCt NaMe', 'product-name'],
            'french characters' => ['Château & Vins', 'chateau-vins'],
            'dots and underscores' => ['product.name_test', 'product-name-test'],
        ];
    }
}
