<?php

namespace App\Controller\Admin;

use App\Entity\Media;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
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
                ->setBasePath('uploads/images')
                ->onlyOnIndex(),
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

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                return $action->setIcon('fa fa-plus')->addCssClass('btn btn-primary');
            })
            ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
                return $action->setIcon('fa fa-edit');
            })
            ->update(Crud::PAGE_INDEX, Action::DETAIL, function (Action $action) {
                return $action->setIcon('fa fa-eye');
            })
            ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
                return $action->setIcon('fa fa-trash');
            })
            ->update(Crud::PAGE_EDIT, Action::SAVE_AND_RETURN, function (Action $action) {
                return $action->setIcon('fa fa-save')->addCssClass('btn btn-primary');
            })
            ->update(Crud::PAGE_EDIT, Action::SAVE_AND_CONTINUE, function (Action $action) {
                return $action->setIcon('fa fa-save-and-continue')->addCssClass('btn btn-secondary');
            });
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Média')
            ->setEntityLabelInPlural('Médias')
            ->setPageTitle('index', 'Liste des médias')
            ->setPageTitle('new', 'Ajouter un média')
            ->setPageTitle('edit', 'Modifier le média')
            ->setPageTitle('detail', 'Détails du média')
            ->setDefaultSort(['createdAt' => 'DESC']);
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
        if ($entityInstance instanceof Media) {
            $entityInstance->setCreatedAt(new \DateTimeImmutable());
            $entityInstance->setUpdatedAt(new \DateTimeImmutable());
        }

        try {
            parent::persistEntity($entityManager, $entityInstance);
        } catch (\Exception $e) {
            throw new \RuntimeException('Erreur lors de la sauvegarde du média : '.$e->getMessage());
        }
    }
}
