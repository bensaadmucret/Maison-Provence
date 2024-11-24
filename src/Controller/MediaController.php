<?php

namespace App\Controller;

use App\Service\MediaService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/media')]
class MediaController extends AbstractController
{
    public function __construct(
        private readonly MediaService $mediaService,
    ) {
    }

    #[Route('/upload', methods: ['POST'])]
    public function upload(Request $request): JsonResponse
    {
        $file = $request->files->get('file');
        if (!$file) {
            return $this->json(['error' => 'No file uploaded'], Response::HTTP_BAD_REQUEST);
        }

        $title = $request->request->get('title');
        $alt = $request->request->get('alt');
        $position = (int) $request->request->get('position', 0);
        $type = $request->request->get('type');
        $collectionId = $request->request->get('collectionId');

        try {
            $media = $this->mediaService->uploadMedia(
                $file,
                $title,
                $alt,
                $position,
                $type,
                $collectionId ? (int) $collectionId : null
            );

            return $this->json($media, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/collections', methods: ['POST'])]
    public function createCollection(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        try {
            $collection = $this->mediaService->createMediaCollection(
                $data['name'],
                $data['description'],
                $data['type'],
                $data['settings'] ?? []
            );

            return $this->json($collection, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/collections/{type}', methods: ['GET'])]
    public function getCollectionsByType(string $type): JsonResponse
    {
        try {
            $collections = $this->mediaService->getMediaCollectionsByType($type);

            return $this->json($collections);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/collection/{type}', methods: ['GET'])]
    public function getMediaByCollection(string $type): JsonResponse
    {
        try {
            $media = $this->mediaService->getMediaByCollection($type);

            return $this->json($media);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        try {
            $this->mediaService->deleteMedia($id);

            return $this->json(null, Response::HTTP_NO_CONTENT);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}/position', methods: ['PUT'])]
    public function updatePosition(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $position = $data['position'] ?? null;

        if (null === $position) {
            return $this->json(['error' => 'Position is required'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $media = $this->mediaService->updateMediaPosition($id, (int) $position);

            return $this->json($media);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}
