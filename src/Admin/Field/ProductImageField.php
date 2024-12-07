<?php

namespace App\Admin\Field;

use Doctrine\ORM\PersistentCollection;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\FieldTrait;

final class ProductImageField implements FieldInterface
{
    use FieldTrait;

    public static function new(string $propertyName, ?string $label = null): self
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setTemplatePath('admin/field/product_image.html.twig')
            ->setFormType(\Symfony\Component\Form\Extension\Core\Type\CollectionType::class)
            ->addCssClass('field-product-image');
    }

    public function setValue($value): self
    {
        if ($value instanceof PersistentCollection) {
            $this->dto->setValue($value);
        }

        return $this;
    }
}
