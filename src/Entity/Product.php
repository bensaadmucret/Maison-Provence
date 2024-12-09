<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\Table(name: 'product')]
#[ORM\Index(name: 'idx_product_name', columns: ['name'])]
#[ORM\Index(name: 'idx_product_category', columns: ['category_id'])]
#[ORM\Index(name: 'idx_product_active_featured', columns: ['is_active', 'is_featured'])]
#[ORM\UniqueConstraint(name: 'UNIQ_SLUG', columns: ['slug'])]
#[ORM\HasLifecycleCallbacks]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom du produit est obligatoire')]
    #[Assert\Length(max: 255, maxMessage: 'Le nom du produit ne peut pas dépasser {{ limit }} caractères')]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'La description du produit est obligatoire')]
    private ?string $description = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\NotNull(message: 'Le prix est obligatoire')]
    #[Assert\Positive(message: 'Le prix doit être positif')]
    private ?float $price = null;

    #[ORM\Column(type: 'integer')]
    #[Assert\NotNull(message: 'Le stock est obligatoire')]
    #[Assert\GreaterThanOrEqual(value: 0, message: 'Le stock ne peut pas être négatif')]
    private ?int $stock = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: false)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: false)]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank(message: 'Le slug est obligatoire')]
    #[Assert\Regex(
        pattern: '/^[a-z0-9-]+$/', 
        message: 'Le slug ne peut contenir que des lettres minuscules, des chiffres et des traits d\'union'
    )]
    private string $slug;

    #[ORM\Column(type: 'boolean')]
    private ?bool $isActive = true;

    #[ORM\Column(type: 'boolean')]
    private bool $isFeatured = false;

    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'products', cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id', nullable: true)]
    private ?Category $category = null;

    #[ORM\OneToMany(
        mappedBy: 'product', 
        targetEntity: Media::class, 
        cascade: ['persist', 'remove'], 
        orphanRemoval: true,
        indexBy: 'id'
    )]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    private Collection $media;

    #[ORM\OneToOne(
        targetEntity: ProductSEO::class, 
        inversedBy: 'product', 
        cascade: ['persist', 'remove'],
        fetch: 'LAZY'
    )]
    #[ORM\JoinColumn(
        name: 'seo_id', 
        referencedColumnName: 'id', 
        nullable: true, 
        unique: true,
        onDelete: 'SET NULL'
    )]
    private ?ProductSEO $seo = null;

    private ?EntityManagerInterface $entityManager = null;

    public function __construct(
        ?EntityManagerInterface $entityManager = null
    ) {
        $this->media = new ArrayCollection();
        $this->isActive = true;
        $this->isFeatured = false;
        $this->stock = 0;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->entityManager = $entityManager;
    }

    #[ORM\PrePersist]
    public function prePersist(PrePersistEventArgs $args): void
    {
        $entityManager = $args->getEntityManager();
        
        // Définir la date de création
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();

        // Générer le slug si pas déjà défini
        if (empty($this->slug)) {
            $this->slug = $this->generateUniqueSlug($entityManager);
        }
    }

    #[ORM\PreUpdate]
    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entityManager = $args->getObjectManager();
        
        // Mettre à jour le timestamp
        $this->updatedAt = new \DateTimeImmutable();

        // Régénérer le slug si le nom a changé
        if ($args->hasChangedField('name')) {
            $this->slug = $this->generateUniqueSlug($entityManager);
        }
    }

    private function generateUniqueSlug(EntityManagerInterface $entityManager, ?string $proposedSlug = null): string
    {
        // Utiliser le nom ou le slug proposé
        $baseSlug = $proposedSlug ?? $this->name ?? 'produit';

        // Nettoyer le slug initial
        $slugger = new AsciiSlugger();
        $cleanSlug = $slugger->slug(strtolower($baseSlug))->toString();

        // Si le slug est vide, générer un slug par défaut
        if (empty($cleanSlug)) {
            $cleanSlug = 'produit-' . bin2hex(random_bytes(4));
        }

        // Vérifier l'unicité du slug via DQL
        $originalSlug = $cleanSlug;
        $counter = 1;

        while (true) {
            // Construire une requête DQL pour compter les slugs existants
            $dql = "SELECT COUNT(p.id) FROM " . self::class . " p WHERE p.slug = :slug";
            $currentId = $this->getId();

            // Ajouter une condition pour exclure l'entité courante si elle existe
            if ($currentId !== null) {
                $dql .= " AND p.id != :currentId";
            }

            $query = $entityManager->createQuery($dql);
            $query->setParameter('slug', $cleanSlug);

            if ($currentId !== null) {
                $query->setParameter('currentId', $currentId);
            }

            // Exécuter la requête et vérifier le nombre de slugs existants
            $slugCount = $query->getSingleScalarResult();

            if ($slugCount === 0) {
                break;
            }

            // Générer un nouveau slug avec un suffixe
            $cleanSlug = sprintf('%s-%d', $originalSlug, $counter);
            $counter++;

            // Limiter le nombre de tentatives pour éviter une boucle infinie
            if ($counter > 100) {
                $cleanSlug .= '-' . bin2hex(random_bytes(4));
                break;
            }
        }

        return $cleanSlug;
    }

    public function setSlug(?string $slug): static
    {
        // Nettoyer le slug proposé
        $slugger = new AsciiSlugger();
        $this->slug = $slug ? $slugger->slug(strtolower($slug))->toString() : null;
        return $this;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;
        
        // Régénérer le slug si le nom change
        $slugger = new AsciiSlugger();
        $this->slug = $slugger->slug(strtolower($name))->toString();
        
        return $this;
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getStock(): ?int
    {
        return $this->stock;
    }

    public function setStock(int $stock): static
    {
        $this->stock = $stock;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function isFeatured(): bool
    {
        return $this->isFeatured;
    }

    public function setIsFeatured(bool $isFeatured): self
    {
        $this->isFeatured = $isFeatured;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        // Gérer la relation bidirectionnelle
        if ($this->category !== $category) {
            // Supprimer le produit de l'ancienne catégorie
            if ($this->category !== null) {
                $this->category->removeProduct($this);
            }

            // Définir la nouvelle catégorie
            $this->category = $category;

            // Ajouter le produit à la nouvelle catégorie si nécessaire
            if ($category !== null) {
                $category->addProduct($this);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Media>
     */
    public function getMedia(): Collection
    {
        return $this->media;
    }

    public function addMedia(Media $media): self
    {
        if (!$this->media->contains($media)) {
            $this->media->add($media);
            $media->setProduct($this);
        }

        return $this;
    }

    public function removeMedia(Media $media): self
    {
        if ($this->media->removeElement($media)) {
            // set the owning side to null (unless already changed)
            if ($media->getProduct() === $this) {
                $media->setProduct(null);
            }
        }

        return $this;
    }

    public function setMedia(?Collection $media): static
    {
        $this->media = $media;

        return $this;
    }

    public function getSeo(): ?ProductSEO
    {
        return $this->seo;
    }

    public function setSeo(?ProductSEO $seo): self
    {
        // Désassocier l'ancien SEO si existant
        if (null !== $this->seo && $this->seo !== $seo) {
            $oldSeo = $this->seo;
            $this->seo = null;
            $oldSeo->setProduct(null);
        }

        // Associer le nouveau SEO
        $this->seo = $seo;
        if (null !== $seo && $seo->getProduct() !== $this) {
            $seo->setProduct($this);
        }

        return $this;
    }

    public function setEntityManager(?EntityManagerInterface $entityManager): self
    {
        $this->entityManager = $entityManager;
        return $this;
    }
}
