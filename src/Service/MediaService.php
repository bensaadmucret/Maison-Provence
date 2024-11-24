<?php

namespace App\Service;

use App\Entity\Media;
use App\Entity\MediaCollection;
use App\Repository\MediaCollectionRepository;
use App\Repository\MediaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

#[AsService]
#[AutoconfigureTag('app.service')]
class MediaService
{
    public function __construct(
        #[Autowire(service: MediaRepository::class)]
        private readonly MediaRepository $mediaRepository,
        #[Autowire(service: MediaCollectionRepository::class)]
        private readonly MediaCollectionRepository $mediaCollectionRepository,
        #[Autowire(service: EntityManagerInterface::class)]
        private readonly EntityManagerInterface $entityManager,
        #[Autowire(service: SluggerInterface::class)]
        private readonly SluggerInterface $slugger,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
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

        $file->move($this->projectDir.'/public/uploads', $newFilename);

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

    public function getMediaByCollection(string $collectionType): array
    {
        return $this->mediaRepository->findByCollection($collectionType);
    }

    public function deleteMedia(int $id): void
    {
        $media = $this->mediaRepository->find($id);
        if (!$media) {
            throw new EntityNotFoundException('Media not found');
        }

        // Delete the physical file
        $filePath = $this->projectDir.'/public/uploads/'.$media->getFilename();
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $this->mediaRepository->remove($media, true);
    }

    public function updateMediaPosition(int $id, int $position): Media
    {
        $media = $this->mediaRepository->find($id);
        if (!$media) {
            throw new EntityNotFoundException('Media not found');
        }

        $media->setPosition($position);
        $this->mediaRepository->save($media, true);

        return $media;
    }

    public function getMediaCollectionsByType(string $type): array
    {
        return $this->mediaCollectionRepository->findByType($type);
    }
}
