<?php

namespace App\Controller\Admin;

use App\Entity\MediaCollection;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class MediaCollectionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return MediaCollection::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('name', 'Nom')->setRequired(true),
            TextEditorField::new('description', 'Description')->setRequired(false),
            ChoiceField::new('type', 'Type')
                ->setChoices([
                    'Galerie' => 'gallery',
                    'Carrousel' => 'carousel',
                    'Grille' => 'grid',
                ])
                ->setRequired(true),
            TextareaField::new('settings', 'Paramètres')
                ->setRequired(false)
                ->setHelp('Entrez les paramètres au format JSON')
                ->addCssClass('json-editor'),
            AssociationField::new('media', 'Médias')->setRequired(false),
            DateTimeField::new('createdAt', 'Créé le')->hideOnForm(),
            DateTimeField::new('updatedAt', 'Modifié le')->hideOnForm(),
        ];
    }

    public function createEntity(string $entityFqcn)
    {
        $collection = new MediaCollection();
        $collection->setType('gallery');

        return $collection;
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof MediaCollection) {
            $entityInstance->setUpdatedAt(new \DateTime());
        }
        parent::updateEntity($entityManager, $entityInstance);
    }
}
