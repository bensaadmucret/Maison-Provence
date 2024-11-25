<?php

namespace App\Tests\Entity;

use App\Entity\Media;
use App\Entity\MediaCollection;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\TestCase;

class MediaCollectionTest extends TestCase
{
    private MediaCollection $collection;

    protected function setUp(): void
    {
        $this->collection = new MediaCollection();
    }

    public function testInitialState(): void
    {
        $this->assertNull($this->collection->getId());
        $this->assertNull($this->collection->getName());
        $this->assertNull($this->collection->getDescription());
        $this->assertNull($this->collection->getType());
        $this->assertNull($this->collection->getSettings());
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->collection->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->collection->getUpdatedAt());
        $this->assertInstanceOf(Collection::class, $this->collection->getMedia());
        $this->assertCount(0, $this->collection->getMedia());
    }

    public function testMediaManagement(): void
    {
        $media = new Media();

        // Test adding media
        $this->collection->addMedia($media);
        $this->assertCount(1, $this->collection->getMedia());
        $this->assertTrue($this->collection->getMedia()->contains($media));
        $this->assertSame($this->collection, $media->getCollection());

        // Test removing media
        $this->collection->removeMedia($media);
        $this->assertCount(0, $this->collection->getMedia());
        $this->assertFalse($this->collection->getMedia()->contains($media));
        $this->assertNull($media->getCollection());
    }

    public function testSettings(): void
    {
        $settings = [
            'autoplay' => true,
            'delay' => 5000,
            'transition' => 'fade',
        ];

        $this->collection->setSettings($settings);
        $this->assertEquals($settings, $this->collection->getSettings());
    }

    public function testToString(): void
    {
        $this->collection->setName('Test Collection');
        $this->assertEquals('Test Collection', (string) $this->collection);

        $this->collection->setName(null);
        $this->assertEquals('', (string) $this->collection);
    }

    public function testType(): void
    {
        $types = ['carousel', 'grid', 'masonry'];

        foreach ($types as $type) {
            $this->collection->setType($type);
            $this->assertEquals($type, $this->collection->getType());
        }
    }

    public function testTimestamps(): void
    {
        $initialCreatedAt = $this->collection->getCreatedAt();
        $initialUpdatedAt = $this->collection->getUpdatedAt();

        sleep(1); // Ensure time difference

        $this->collection->setName('New Name');
        $newUpdatedAt = $this->collection->getUpdatedAt();

        $this->assertSame($initialCreatedAt, $this->collection->getCreatedAt());
        $this->assertNotSame($initialUpdatedAt, $newUpdatedAt);
    }
}
