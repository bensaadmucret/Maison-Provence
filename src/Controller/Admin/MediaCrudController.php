<?php

namespace App\Controller\Admin;

use App\Entity\Media;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Vich\UploaderBundle\Form\Type\VichImageType;

class MediaCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Media::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('imageFile', 'Image')
                ->setFormType(VichImageType::class)
                ->setFormTypeOptions([
                    'required' => true,
                    'allow_delete' => true,
                    'download_uri' => false,
                    'image_uri' => true,
                    'asset_helper' => true,
                    'error_bubbling' => true,
                ])
                ->onlyOnForms(),
            ImageField::new('filename', 'Image')
                ->setBasePath('uploads/media')
                ->hideOnForm(),
            TextField::new('title', 'Titre')->setRequired(false),
            TextField::new('alt', 'Texte alternatif')->setRequired(false),
            IntegerField::new('position', 'Position')->setRequired(false),
            ChoiceField::new('type', 'Type')
                ->setChoices([
                    'Image' => 'image',
                    'Document' => 'document',
                    'Vidéo' => 'video',
                ])
                ->setRequired(true),
            AssociationField::new('product', 'Produit')
                ->setRequired(false)
                ->setFormTypeOptions([
                    'class' => 'App\Entity\Product',
                    'choice_label' => 'name',
                ]),
            AssociationField::new('collection', 'Collection')->setRequired(false),
            DateTimeField::new('createdAt', 'Créé le')->hideOnForm(),
            DateTimeField::new('updatedAt', 'Modifié le')->hideOnForm(),
        ];
    }

    public function createEntity(string $entityFqcn)
    {
        $media = new Media();
        $media->setType('image');

        return $media;
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Media) {
            $entityInstance->setUpdatedAt(new \DateTimeImmutable());
        }
        parent::updateEntity($entityManager, $entityInstance);
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        try {
            parent::persistEntity($entityManager, $entityInstance);
        } catch (\Exception $e) {
            // Log the error if needed
            // Return to form with error message
            return;
        }
    }
}
