<?php

namespace App\Service;

use App\Entity\Media;
use App\Entity\MediaCollection;
use App\Message\ProcessImageMessage;
use App\Repository\MediaCollectionRepository;
use App\Repository\MediaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

#[AsService]
#[AutoconfigureTag('app.service')]
class MediaService
{
    private string $uploadDir;

    public function __construct(
        #[Autowire(service: EntityManagerInterface::class)]
        private readonly EntityManagerInterface $entityManager,
        #[Autowire(service: MediaRepository::class)]
        private readonly MediaRepository $mediaRepository,
        #[Autowire(service: MediaCollectionRepository::class)]
        private readonly MediaCollectionRepository $mediaCollectionRepository,
        #[Autowire(service: SluggerInterface::class)]
        private readonly SluggerInterface $slugger,
        #[Autowire(service: MessageBusInterface::class)]
        private readonly MessageBusInterface $messageBus,
        #[Autowire('%kernel.project_dir%')]
        string $projectDir,
    ) {
        $this->uploadDir = $projectDir.'/public/uploads/media';
    }

    public function uploadMedia(
        UploadedFile $file,
        string $title,
        string $alt,
        int $position,
        string $type,
        ?int $collectionId = null,
    ): Media {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

        $file->move($this->uploadDir, $newFilename);

        $media = new Media();
        $media
            ->setFilename($newFilename)
            ->setTitle($title)
            ->setAlt($alt)
            ->setPosition($position)
            ->setType($type);

        if ($collectionId) {
            $collection = $this->mediaCollectionRepository->find($collectionId);
            if (!$collection) {
                throw new EntityNotFoundException('Media collection not found');
            }
            $media->setMediaCollection($collection);
        }

        $this->mediaRepository->save($media, true);

        // Traitement asynchrone des images
        if (str_starts_with($media->getMimeType(), 'image/')) {
            $this->processImage($media);
        }

        return $media;
    }

    private function processImage(Media $media): void
    {
        // Définir les dimensions pour les différentes versions
        $dimensions = [
            ['width' => 800, 'height' => 600],  // Version standard
            ['width' => 300, 'height' => 300],  // Version thumbnail
            ['width' => 1200, 'height' => null], // Version large
        ];

        $this->messageBus->dispatch(
            new ProcessImageMessage($media->getId(), $dimensions)
        );
    }

    public function createMediaCollection(string $name, string $description, string $type, array $settings = []): MediaCollection
    {
        $collection = new MediaCollection();
        $collection
            ->setName($name)
            ->setDescription($description)
            ->setType($type)
            ->setSettings($settings);

        $this->mediaCollectionRepository->save($collection, true);

        return $collection;
    }

    /**
     * @return array<Media>
     */
    public function getMediaByCollection(MediaCollection $collection): array
    {
        return $this->mediaRepository->findBy(['mediaCollection' => $collection]);
    }

    public function deleteMedia(int $id): void
    {
        $media = $this->mediaRepository->find($id);
        if (!$media) {
            throw new EntityNotFoundException(sprintf('Media with id %d not found', $id));
        }

        $this->delete($media);
    }

    private function delete(Media $media): void
    {
        $filename = $media->getFilename();
        $filepath = $this->uploadDir.'/'.$filename;

        if (file_exists($filepath)) {
            unlink($filepath);
        }

        // Supprimer aussi les versions redimensionnées
        $pattern = $this->uploadDir.'/resized/'.pathinfo($filename, PATHINFO_FILENAME).'_*';
        array_map('unlink', glob($pattern));

        $this->mediaRepository->remove($media, true);
    }

    public function updateMediaPosition(int $id, int $position): Media
    {
        $media = $this->mediaRepository->find($id);
        if (!$media) {
            throw new EntityNotFoundException(sprintf('Media with id %d not found', $id));
        }

        $media->setPosition($position);
        $this->mediaRepository->save($media, true);

        return $media;
    }

    /**
     * @return array<MediaCollection>
     */
    public function getMediaCollectionsByType(string $type): array
    {
        return $this->mediaCollectionRepository->findCollectionsByType($type);
    }

    public function getMediaCollection(int $id): MediaCollection
    {
        $collection = $this->mediaCollectionRepository->find($id);
        if (!$collection) {
            throw new EntityNotFoundException(sprintf('Media collection with id %d not found', $id));
        }

        return $collection;
    }

    public function addMediaToCollection(Media $media, MediaCollection $collection): void
    {
        $media->setMediaCollection($collection);
        $this->entityManager->persist($media);
        $this->entityManager->flush();
    }

    /**
     * @param array<string, mixed> $settings
     */
    public function createMediaCollectionNew(string $name, string $type, array $settings = []): MediaCollection
    {
        $collection = new MediaCollection();
        $collection->setName($name);
        $collection->setType($type);
        $collection->setSettings($settings);

        $this->entityManager->persist($collection);
        $this->entityManager->flush();

        return $collection;
    }

    public function getMedia(int $id): Media
    {
        $media = $this->mediaRepository->find($id);
        if (!$media) {
            throw new EntityNotFoundException(sprintf('Media with id %d not found', $id));
        }

        return $media;
    }

    public function deleteMediaNew(int $id): void
    {
        $media = $this->getMedia($id);
        if (!$media) {
            throw new EntityNotFoundException(sprintf('Media with id %d not found', $id));
        }

        $this->delete($media);
    }
}
