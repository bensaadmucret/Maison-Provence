<?php

namespace App\Service;

use App\Entity\Media;
use App\Entity\MediaCollection;
use App\Repository\MediaCollectionRepository;
use App\Repository\MediaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
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
        #[Autowire('%kernel.project_dir%')]
        string $projectDir,
    ) {
        $this->uploadDir = $projectDir . '/public/uploads/media';
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

        return $media;
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

        // Delete the physical file
        $filePath = $this->uploadDir.'/'.$media->getFilename();
        if (file_exists($filePath)) {
            unlink($filePath);
        }

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

        $this->entityManager->remove($media);
        $this->entityManager->flush();
    }
}
