<?php

namespace App\Tests\Entity;

use App\Entity\Media;
use App\Entity\MediaCollection;
use App\Entity\Product;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\File;

class MediaTest extends TestCase
{
    private Media $media;

    protected function setUp(): void
    {
        $this->media = new Media();
    }

    public function testInitialState(): void
    {
        $media = new Media();

        self::assertNull($media->getId());
        self::assertNull($media->getFilename());
        self::assertNull($media->getTitle());
        self::assertNull($media->getAlt());
        self::assertNull($media->getPosition());
        self::assertNull($media->getProduct());
        self::assertInstanceOf(\DateTimeImmutable::class, $media->getCreatedAt());
        self::assertInstanceOf(\DateTimeImmutable::class, $media->getUpdatedAt());
    }

    public function testImageFileUpload(): void
    {
        $file = $this->createMock(File::class);
        $initialUpdatedAt = $this->media->getUpdatedAt();

        $this->media->setImageFile($file);

        $this->assertSame($file, $this->media->getImageFile());
        $this->assertNotSame($initialUpdatedAt, $this->media->getUpdatedAt());
    }

    public function testNullImageFile(): void
    {
        $initialUpdatedAt = $this->media->getUpdatedAt();

        $this->media->setImageFile(null);

        $this->assertNull($this->media->getImageFile());
        $this->assertSame($initialUpdatedAt, $this->media->getUpdatedAt());
    }

    public function testProductAssociation(): void
    {
        $product = new Product();
        $this->media->setProduct($product);

        $this->assertSame($product, $this->media->getProduct());
    }

    public function testCollectionAssociation(): void
    {
        $collection = new MediaCollection();
        $this->media->setCollection($collection);

        $this->assertSame($collection, $this->media->getCollection());
    }

    public function testToString(): void
    {
        // Test with title
        $this->media->setTitle('Test Title');
        $this->media->setFilename('test.jpg');
        $this->assertEquals('Test Title', (string) $this->media);

        // Test with only filename
        $this->media->setTitle(null);
        $this->assertEquals('test.jpg', (string) $this->media);

        // Test with neither
        $this->media->setFilename(null);
        $this->assertEquals('', (string) $this->media);
    }
}
