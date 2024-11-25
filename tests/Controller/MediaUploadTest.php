<?php

namespace App\Tests\Controller;

use App\Entity\Media;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\User\UserInterface;

class MediaUploadTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $uploadDir;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->uploadDir = static::getContainer()->getParameter('kernel.project_dir') . '/public/uploads/media/test';
        
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }

        // Create a test admin user
        $this->createTestUser();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up uploaded files
        if (is_dir($this->uploadDir)) {
            $files = scandir($this->uploadDir);
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..') {
                    unlink($this->uploadDir . '/' . $file);
                }
            }
            rmdir($this->uploadDir);
        }

        // Reset database
        $this->entityManager->createQuery('DELETE FROM App\Entity\Media')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\User')->execute();
        $this->entityManager->close();
        $this->entityManager = null;
    }

    private function createTestUser(): UserInterface
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('$2y$13$PJzqKVxwJXhfW1P.zT5D8.9CvjWH9cN41ACO7H9JJhqX8URuGBNwO'); // 'password'
        $user->setRoles(['ROLE_ADMIN']);
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        return $user;
    }

    public function testUploadImage(): void
    {
        // Login as admin
        $this->client->loginUser($this->createTestUser());

        // Create a test image
        $imagePath = $this->uploadDir . '/test.jpg';
        copy(__DIR__ . '/../fixtures/test.jpg', $imagePath);
        
        $uploadedFile = new UploadedFile(
            $imagePath,
            'test.jpg',
            'image/jpeg',
            null,
            true
        );

        // Submit the form with the image
        $crawler = $this->client->request('GET', '/admin');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Upload')->form();
        $form['media[imageFile]']->upload($uploadedFile);
        $form['media[title]'] = 'Test Image';
        $form['media[alt]'] = 'Test Alt Text';

        $this->client->submit($form);
        $this->assertResponseRedirects();

        // Verify the image was saved
        $media = $this->entityManager->getRepository(Media::class)->findOneBy(['title' => 'Test Image']);
        $this->assertNotNull($media);
        $this->assertEquals('Test Alt Text', $media->getAlt());
        $this->assertNotNull($media->getFilename());
        $this->assertTrue(file_exists($this->uploadDir . '/' . $media->getFilename()));
    }

    public function testUploadInvalidImage(): void
    {
        // Login as admin
        $this->client->loginUser($this->createTestUser());

        // Create an invalid file
        $invalidFilePath = $this->uploadDir . '/test.txt';
        file_put_contents($invalidFilePath, 'This is not an image');
        
        $uploadedFile = new UploadedFile(
            $invalidFilePath,
            'test.txt',
            'text/plain',
            null,
            true
        );

        // Try to upload the invalid file
        $crawler = $this->client->request('GET', '/admin');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Upload')->form();
        $form['media[imageFile]']->upload($uploadedFile);
        $form['media[title]'] = 'Invalid File';

        $this->client->submit($form);
        
        // Verify the upload was rejected
        $this->assertResponseStatusCodeSame(400);
        $this->assertSelectorTextContains('.invalid-feedback', 'Please upload a valid image file');
    }
}
