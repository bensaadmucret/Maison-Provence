<?php

namespace App\Service;

use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpClient\Exception\ServerException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[When(env: 'dev')]
#[Autoconfigure(tags: ['app.pexels_image_service'])]
class PexelsImageService
{
    private const MIN_WIDTH = 500;
    private const MIN_HEIGHT = 500;
    private const MAX_IMAGE_SIZE_MB = 5;
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    public function __construct(
        private HttpClientInterface $httpClient,
        #[Autowire(param: 'app.pexels_api_key')]
        private string $pexelsApiKey,
        private ParameterBagInterface $params,
        private LoggerInterface $logger,
        private Filesystem $filesystem,
    ) {
    }

    public function searchImages(string $query, int $perPage = 15): array
    {
        try {
            $this->logger->info('Searching Pexels images', [
                'query' => $query,
                'per_page' => $perPage,
            ]);

            $response = $this->httpClient->request('GET', 'https://api.pexels.com/v1/search', [
                'headers' => [
                    'Authorization' => $this->pexelsApiKey,
                ],
                'query' => [
                    'query' => $query,
                    'per_page' => $perPage,
                    'orientation' => 'square',
                ],
            ]);

            $content = $response->toArray();

            $this->logger->info('Pexels image search successful', [
                'total_results' => $content['total_results'] ?? 0,
                'returned_images' => count($content['photos'] ?? []),
            ]);

            return $content['photos'] ?? [];
        } catch (ClientException $e) {
            $this->logger->error('Pexels API Client Error', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            return [];
        } catch (ServerException $e) {
            $this->logger->critical('Pexels API Server Error', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            return [];
        } catch (\Exception $e) {
            $this->logger->error('Unexpected error in Pexels image search', [
                'message' => $e->getMessage(),
            ]);

            return [];
        }
    }

    public function downloadImage(string $imageUrl): ?string
    {
        try {
            $this->logger->info('Downloading image from Pexels', [
                'url' => $imageUrl,
            ]);

            $response = $this->httpClient->request('GET', $imageUrl);

            // Generate a unique filename
            $filename = uniqid('product_', true).'.jpg';
            $uploadDir = $this->params->get('kernel.project_dir').'/public/uploads/images/';

            // Ensure directory exists
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
                $this->logger->info('Created upload directory', [
                    'path' => $uploadDir,
                ]);
            }

            $fullPath = $uploadDir.$filename;

            // Save the image
            $imageContent = $response->getContent();
            $bytesWritten = file_put_contents($fullPath, $imageContent);

            if (false === $bytesWritten) {
                throw new \Exception('Failed to write image file');
            }

            $this->logger->info('Image downloaded successfully', [
                'filename' => $filename,
                'bytes' => $bytesWritten,
            ]);

            return $filename;
        } catch (ClientException $e) {
            $this->logger->error('Error downloading image from Pexels', [
                'url' => $imageUrl,
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            return null;
        } catch (\Exception $e) {
            $this->logger->error('Unexpected error downloading image', [
                'url' => $imageUrl,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Redimensionne et optimise l'image téléchargée.
     *
     * @param string $filename  Chemin du fichier à optimiser
     * @param int    $maxWidth  Largeur maximale souhaitée
     * @param int    $maxHeight Hauteur maximale souhaitée
     *
     * @return string|null Chemin du fichier optimisé
     */
    public function optimizeImage(string $filename, int $maxWidth = 1200, int $maxHeight = 1200): ?string
    {
        try {
            $this->logger->info('Optimizing image', [
                'filename' => $filename,
                'max_width' => $maxWidth,
                'max_height' => $maxHeight,
            ]);

            $imagine = new Imagine();
            $image = $imagine->open($filename);

            // Redimensionnement proportionnel
            $originalSize = $image->getSize();
            $ratio = min(
                $maxWidth / $originalSize->getWidth(),
                $maxHeight / $originalSize->getHeight()
            );

            if ($ratio < 1) {
                $newWidth = round($originalSize->getWidth() * $ratio);
                $newHeight = round($originalSize->getHeight() * $ratio);

                $image->resize(new Box($newWidth, $newHeight), ImageInterface::FILTER_LANCZOS);

                // Générer un nouveau nom de fichier
                $pathInfo = pathinfo($filename);
                $optimizedFilename = sprintf(
                    '%s/%s_optimized.%s',
                    $pathInfo['dirname'],
                    $pathInfo['filename'],
                    $pathInfo['extension']
                );

                $image->save($optimizedFilename, [
                    'quality' => 85,  // Compression de qualité
                    'format' => $pathInfo['extension'],
                ]);

                $this->logger->info('Image optimized successfully', [
                    'original_size' => $originalSize->__toString(),
                    'new_size' => $image->getSize()->__toString(),
                    'optimized_filename' => $optimizedFilename,
                ]);

                return $optimizedFilename;
            }

            return $filename;
        } catch (\Exception $e) {
            $this->logger->error('Image optimization failed', [
                'filename' => $filename,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Génère un nom de fichier unique et sécurisé.
     *
     * @param string $originalFilename Nom du fichier original
     * @param string $prefix           Préfixe optionnel
     *
     * @return string Nom de fichier unique
     */
    public function generateUniqueFilename(string $originalFilename, string $prefix = 'product_'): string
    {
        $extension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));

        // Validation de l'extension
        if (!in_array($extension, self::ALLOWED_EXTENSIONS)) {
            $extension = 'jpg';  // Fallback
        }

        // Génération d'un nom unique
        $uniqueFilename = sprintf(
            '%s%s_%s.%s',
            $prefix,
            uniqid(),
            bin2hex(random_bytes(4)),
            $extension
        );

        return $uniqueFilename;
    }

    /**
     * Nettoie les fichiers temporaires et anciens.
     *
     * @param string $directory Répertoire à nettoyer
     * @param int    $maxAge    Âge maximum des fichiers (en secondes)
     */
    public function cleanupTemporaryFiles(string $directory, int $maxAge = 86400): void
    {
        try {
            $now = time();
            $files = glob($directory.'/*');

            foreach ($files as $file) {
                if (is_file($file)) {
                    $fileAge = $now - filemtime($file);

                    if ($fileAge > $maxAge) {
                        $this->filesystem->remove($file);

                        $this->logger->info('Removed old temporary file', [
                            'filename' => $file,
                            'age' => $fileAge,
                        ]);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Temporary file cleanup failed', [
                'directory' => $directory,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Vérifie si un fichier image est valide.
     *
     * @param string $filename Chemin du fichier
     *
     * @return bool Indique si le fichier est une image valide
     */
    private function validateImage(string $filename): bool
    {
        try {
            $imageInfo = getimagesize($filename);

            if (false === $imageInfo) {
                $this->logger->warning('Invalid image file', [
                    'filename' => $filename,
                ]);

                return false;
            }

            // Vérification des dimensions minimales
            if ($imageInfo[0] < self::MIN_WIDTH || $imageInfo[1] < self::MIN_HEIGHT) {
                $this->logger->warning('Image too small', [
                    'filename' => $filename,
                    'width' => $imageInfo[0],
                    'height' => $imageInfo[1],
                ]);

                return false;
            }

            // Vérification de la taille du fichier
            $fileSize = filesize($filename);
            $fileSizeMB = $fileSize / (1024 * 1024);

            if ($fileSizeMB > self::MAX_IMAGE_SIZE_MB) {
                $this->logger->warning('Image file too large', [
                    'filename' => $filename,
                    'size_mb' => $fileSizeMB,
                ]);

                return false;
            }

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Image validation error', [
                'filename' => $filename,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
