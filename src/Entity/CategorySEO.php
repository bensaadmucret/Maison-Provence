<?php

namespace App\Entity;

use App\Repository\CategorySEORepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CategorySEORepository::class)]
class CategorySEO extends SEO
{
    #[ORM\OneToOne(inversedBy: 'seo', targetEntity: Category::class)]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id', nullable: true)]
    private ?Category $category = null;

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        if (null !== $category && $category->getSeo() !== $this) {
            $category->setSeo($this);
        }

        return $this;
    }
}
