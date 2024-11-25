<?php

namespace App\Tests\Controller;

use App\Entity\Media;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\User\UserInterface;
use App\Controller\Admin\MediaCrudController;

class MediaUploadTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $mediaRepository;
    private $userRepository;
    private $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()->get('doctrine')->getManager();
        $this->mediaRepository = $this->entityManager->getRepository(Media::class);
        $this->userRepository = $this->entityManager->getRepository(User::class);

        $this->adminUser = new User();
        $this->adminUser->setEmail('admin' . uniqid() . '@test.com');
        $this->adminUser->setPassword('$2y$13$' . uniqid());
        $this->adminUser->setRoles(['ROLE_ADMIN']);
        $this->adminUser->setFirstName('Admin');
        $this->adminUser->setLastName('Test');
        $this->entityManager->persist($this->adminUser);
        $this->entityManager->flush();

        $this->client->loginUser($this->adminUser);
    }

    public function testUploadImage(): void
    {
        $uploadDir = $this->client->getContainer()->getParameter('upload_directory');
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $crawler = $this->client->request('GET', '/admin?crudAction=new&crudControllerFqcn=App%5CController%5CAdmin%5CMediaCrudController');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Create')->form();
        $testFile = __DIR__ . '/../fixtures/test-image.jpg';
        $form['Media[file]']->upload($testFile);
        $form['Media[title]'] = 'Test Image';
        $form['Media[description]'] = 'Test Description';

        $this->client->submit($form);
        $this->assertResponseRedirects('/admin?crudAction=index&crudControllerFqcn=App%5CController%5CAdmin%5CMediaCrudController');

        $media = $this->mediaRepository->findOneBy(['title' => 'Test Image']);
        $this->assertNotNull($media);
        $this->assertEquals('Test Description', $media->getDescription());
        $this->assertFileExists($uploadDir . '/' . $media->getFilename());
    }

    public function testUploadInvalidImage(): void
    {
        $crawler = $this->client->request('GET', '/admin?crudAction=new&crudControllerFqcn=App%5CController%5CAdmin%5CMediaCrudController');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Create')->form();
        $testFile = __DIR__ . '/../fixtures/invalid-file.txt';
        $form['Media[file]']->upload($testFile);
        $form['Media[title]'] = 'Invalid File';
        $form['Media[description]'] = 'Invalid Description';

        $this->client->submit($form);
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.invalid-feedback', 'Please upload a valid image file');

        $media = $this->mediaRepository->findOneBy(['title' => 'Invalid File']);
        $this->assertNull($media);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $uploadDir = $this->client->getContainer()->getParameter('upload_directory');
        if (is_dir($uploadDir)) {
            $files = glob($uploadDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }

        if ($this->adminUser) {
            $this->entityManager->remove($this->adminUser);
            $this->entityManager->flush();
        }

        $this->entityManager->close();
        $this->entityManager = null;
        $this->client = null;
    }
}
