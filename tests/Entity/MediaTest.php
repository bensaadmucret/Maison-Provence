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
        $this->assertNull($this->media->getId());
        $this->assertNull($this->media->getFilename());
        $this->assertNull($this->media->getTitle());
        $this->assertNull($this->media->getAlt());
        $this->assertNull($this->media->getPosition());
        $this->assertNull($this->media->getType());
        $this->assertNull($this->media->getProduct());
        $this->assertNull($this->media->getCollection());
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->media->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->media->getUpdatedAt());
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
