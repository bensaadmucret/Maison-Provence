<?php

namespace App\Tests\Service;

use App\Service\SlugService;
use PHPUnit\Framework\TestCase;

/**
 * @extends TestCase
 */
class SlugServiceTest extends TestCase
{
    private SlugService $slugService;

    protected function setUp(): void
    {
        $this->slugService = new SlugService();
    }

    /**
     * @return array<string, array{string, string}>
     */
    public function slugDataProvider(): array
    {
        return [
            'simple string' => ['Hello World', 'hello-world'],
            'accented characters' => ['Café au lait', 'cafe-au-lait'],
            'special characters' => ['Product & Service', 'product-service'],
            'multiple spaces' => ['Multiple   Spaces', 'multiple-spaces'],
            'numbers' => ['Product 123', 'product-123'],
            'mixed case' => ['MiXeD CaSe', 'mixed-case'],
            'trim spaces' => [' Trim Spaces ', 'trim-spaces'],
            'dots and underscores' => ['product.name_test', 'product-name-test'],
        ];
    }

    /**
     * @dataProvider slugDataProvider
     */
    public function testSlugify(string $input, string $expected): void
    {
        self::assertEquals($expected, $this->slugService->slugify($input));
    }
}
