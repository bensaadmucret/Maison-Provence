<?php

namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
#[ORM\Table(name: 'category')]
#[ORM\Index(name: 'idx_category_name', columns: ['name'])]
#[ORM\Index(name: 'idx_category_parent', columns: ['parent_id'])]
#[ORM\Index(name: 'idx_category_slug', columns: ['slug'])]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom de la catégorie est obligatoire')]
    #[Assert\Length(max: 255, maxMessage: 'Le nom de la catégorie ne peut pas dépasser {{ limit }} caractères')]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 1000, maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères')]
    private ?string $description = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank(message: 'Le slug est obligatoire')]
    #[Assert\Regex(
        pattern: '/^[a-z0-9-]+$/', 
        message: 'Le slug ne peut contenir que des lettres minuscules, des chiffres et des traits d\'union'
    )]
    private ?string $slug = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToMany(
        mappedBy: 'category', 
        targetEntity: Product::class, 
        fetch: 'LAZY',
        cascade: ['persist', 'remove'],
        orphanRemoval: false
    )]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    private Collection $products;

    #[ORM\ManyToOne(
        targetEntity: self::class, 
        inversedBy: 'children',
        fetch: 'LAZY'
    )]
    #[ORM\JoinColumn(
        name: 'parent_id', 
        referencedColumnName: 'id', 
        nullable: true,
        onDelete: 'SET NULL'
    )]
    private ?self $parent = null;

    #[ORM\OneToMany(
        mappedBy: 'parent', 
        targetEntity: self::class, 
        fetch: 'LAZY',
        orphanRemoval: false
    )]
    #[ORM\OrderBy(['name' => 'ASC'])]
    private Collection $children;

    #[ORM\Column(type: 'integer')]
    private int $level = 0;

    #[ORM\OneToOne(
        inversedBy: 'category', 
        targetEntity: CategorySEO::class, 
        cascade: ['persist', 'remove']
    )]
    #[ORM\JoinColumn(name: 'seo_id', referencedColumnName: 'id')]
    private ?CategorySEO $seo = null;

    public function __construct()
    {
        $this->products = new ArrayCollection();
        $this->children = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return Collection<int, Product>
     */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function addProduct(Product $product): self
    {
        if (!$this->products->contains($product)) {
            $this->products->add($product);
            $product->setCategory($this);
        }
        return $this;
    }

    public function removeProduct(Product $product): self
    {
        if ($this->products->removeElement($product)) {
            if ($product->getCategory() === $this) {
                $product->setCategory(null);
            }
        }
        return $this;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function setLevel(int $level): static
    {
        $this->level = $level;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): static
    {
        if ($parent === $this) {
            return $this;
        }

        $this->parent = $parent;
        $this->level = $parent ? $parent->getLevel() + 1 : 0;

        return $this;
    }

    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function addChild(self $child): self
    {
        if (!$this->children->contains($child)) {
            $this->children->add($child);
            $child->setParent($this);
        }
        return $this;
    }

    public function removeChild(self $child): self
    {
        if ($this->children->removeElement($child)) {
            if ($child->getParent() === $this) {
                $child->setParent(null);
            }
        }
        return $this;
    }

    public function getSeo(): ?CategorySEO
    {
        return $this->seo;
    }

    public function setSeo(?CategorySEO $seo): static
    {
        $this->seo = $seo;

        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
