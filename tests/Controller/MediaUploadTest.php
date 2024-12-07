<?php

namespace App\Tests\Controller;

use App\Entity\Media;
use App\Repository\MediaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MediaUploadTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;
    private MediaRepository $mediaRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->mediaRepository = $this->entityManager->getRepository(Media::class);
    }

    public function testUploadMedia(): void
    {
        $client = static::createClient();

        // Create a test file
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tempFile, 'Test content');
        $uploadedFile = new UploadedFile(
            $tempFile,
            'test.txt',
            'text/plain',
            null,
            true
        );

        // Submit the form
        $client->request(
            'POST',
            '/admin/media/upload',
            [],
            ['media' => $uploadedFile]
        );

        $this->assertResponseIsSuccessful();

        // Clean up
        @unlink($tempFile);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }
}
