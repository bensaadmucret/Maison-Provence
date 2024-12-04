<?php

namespace App\MessageHandler;

use App\Message\ProcessImageMessage;
use App\Repository\MediaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Intervention\Image\ImageManager;
use Psr\Log\LoggerInterface;

#[AsMessageHandler]
class ProcessImageMessageHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MediaRepository $mediaRepository,
        private readonly string $uploadDirectory,
        private readonly LoggerInterface $logger
    ) {}

    public function __invoke(ProcessImageMessage $message): void
    {
        $media = $this->mediaRepository->find($message->getImageId());
        
        if (!$media) {
            $this->logger->error('Media not found', ['id' => $message->getImageId()]);
            return;
        }

        try {
            $manager = new ImageManager(['driver' => 'gd']);
            $image = $manager->make($this->uploadDirectory . '/' . $media->getFilename());

            foreach ($message->getDimensions() as $dimension) {
                $width = $dimension['width'] ?? null;
                $height = $dimension['height'] ?? null;

                if ($width && $height) {
                    $image->fit($width, $height);
                } elseif ($width) {
                    $image->widen($width, function ($constraint) {
                        $constraint->upsize();
                    });
                } elseif ($height) {
                    $image->heighten($height, function ($constraint) {
                        $constraint->upsize();
                    });
                }

                if ($filter = $message->getFilter()) {
                    $image->filter($filter);
                }

                // Générer un nom unique pour la version redimensionnée
                $filename = pathinfo($media->getFilename(), PATHINFO_FILENAME);
                $extension = pathinfo($media->getFilename(), PATHINFO_EXTENSION);
                $newFilename = sprintf(
                    '%s_%dx%d.%s',
                    $filename,
                    $width ?? 'auto',
                    $height ?? 'auto',
                    $extension
                );

                $image->save($this->uploadDirectory . '/resized/' . $newFilename);
            }

            $this->logger->info('Image processed successfully', [
                'id' => $message->getImageId(),
                'dimensions' => $message->getDimensions()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error processing image', [
                'id' => $message->getImageId(),
                'error' => $e->getMessage()
            ]);
        }
    }
}
