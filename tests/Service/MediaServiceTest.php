<?php

namespace App\Tests\Service;

use App\Entity\Media;
use App\Entity\Product;
use App\Repository\MediaRepository;
use App\Service\MediaService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MediaServiceTest extends TestCase
{
    private MediaService $mediaService;
    private MediaRepository&MockObject $mediaRepository;
    private EntityManagerInterface&MockObject $entityManager;

    protected function setUp(): void
    {
        $this->mediaRepository = $this->createMock(MediaRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->mediaService = new MediaService(
            $this->entityManager,
            $this->mediaRepository
        );
    }

    public function testAddMediaToProduct(): void
    {
        $product = new Product();
        $product->setName('Test Product');

        $uploadedFile = $this->createMock(UploadedFile::class);
        $uploadedFile->method('getClientOriginalName')->willReturn('test-image.jpg');
        $uploadedFile->method('getMimeType')->willReturn('image/jpeg');

        $media = new Media();
        $media->setFilename('test-image.jpg');
        $media->setType('image');
        $media->setTitle('Test Image');

        $this->entityManager
            ->expects(self::once())
            ->method('persist')
            ->with($media);

        $this->entityManager
            ->expects(self::once())
            ->method('flush');

        $result = $this->mediaService->addMediaToProduct($product, $uploadedFile);

        self::assertInstanceOf(Media::class, $result);
        self::assertSame('test-image.jpg', $result->getFilename());
        self::assertSame($product, $result->getProduct());
    }

    public function testRemoveMediaFromProduct(): void
    {
        $product = new Product();
        $media = new Media();
        $media->setProduct($product);
        $product->addMedia($media);

        $this->mediaRepository
            ->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn($media);

        $this->entityManager
            ->expects(self::once())
            ->method('remove')
            ->with($media);

        $this->entityManager
            ->expects(self::once())
            ->method('flush');

        $this->mediaService->removeMediaFromProduct($product, 1);

        self::assertCount(0, $product->getMedia());
    }

    public function testUpdateMediaPosition(): void
    {
        $media = new Media();
        $media->setPosition(1);

        $this->mediaRepository
            ->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn($media);

        $this->entityManager
            ->expects(self::once())
            ->method('flush');

        $this->mediaService->updateMediaPosition(1, 2);

        self::assertSame(2, $media->getPosition());
    }

    public function testGetProductMainImage(): void
    {
        $product = new Product();

        $media1 = new Media();
        $media1->setPosition(2);
        $media1->setProduct($product);

        $media2 = new Media();
        $media2->setPosition(1);
        $media2->setProduct($product);

        $product->addMedia($media1);
        $product->addMedia($media2);

        $result = $this->mediaService->getProductMainImage($product);

        self::assertSame($media2, $result);
    }
}
