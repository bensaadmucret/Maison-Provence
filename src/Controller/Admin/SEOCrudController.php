<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use App\Entity\CategorySEO;
use App\Entity\Product;
use App\Entity\ProductSEO;
use App\Entity\SEO;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use Symfony\Component\Form\Exception\InvalidArgumentException;

class SEOCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return SEO::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('SEO')
            ->setEntityLabelInPlural('SEO')
            ->setPageTitle('index', 'Gestion SEO')
            ->setPageTitle('new', 'Créer une configuration SEO')
            ->setPageTitle('edit', 'Modifier la configuration SEO')
            ->setPageTitle('detail', 'Détails SEO')
            ->setSearchFields(['metaTitle', 'metaDescription', 'canonicalUrl']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW) // Désactiver la création directe car SEO est abstraite
            ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
                return $action->setIcon('fa fa-edit');
            })
            ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
                return $action->setIcon('fa fa-trash');
            });
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('metaTitle', 'Titre meta')
            ->setHelp('Maximum 60 caractères')
            ->setColumns('col-md-12');

        yield TextEditorField::new('metaDescription', 'Description meta')
            ->setHelp('Maximum 160 caractères')
            ->setColumns('col-md-12');

        yield UrlField::new('canonicalUrl', 'URL canonique')
            ->setColumns('col-md-12')
            ->setRequired(false);

        yield ArrayField::new('metaKeywords', 'Mots-clés')
            ->setHelp('Liste des mots-clés')
            ->setColumns('col-md-12');

        yield BooleanField::new('indexable', 'Indexable')
            ->setHelp('Autoriser l\'indexation par les moteurs de recherche')
            ->setColumns('col-md-6');

        yield BooleanField::new('followable', 'Followable')
            ->setHelp('Autoriser le suivi des liens par les moteurs de recherche')
            ->setColumns('col-md-6');

        $entity = $this->getContext()?->getEntity()->getInstance();

        if ($entity instanceof ProductSEO) {
            yield AssociationField::new('product', 'Produit')
                ->setFormTypeOptions([
                    'class' => Product::class,
                    'choice_label' => 'name',
                ])
                ->setColumns('col-md-12');
        } elseif ($entity instanceof CategorySEO) {
            yield AssociationField::new('category', 'Catégorie')
                ->setFormTypeOptions([
                    'class' => Category::class,
                    'choice_label' => 'name',
                ])
                ->setColumns('col-md-12');
        }
    }

    public function createEntity(string $entityFqcn)
    {
        throw new InvalidArgumentException('SEO est une classe abstraite. Veuillez créer une instance de ProductSEO ou CategorySEO.');
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        parent::updateEntity($entityManager, $entityInstance);
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        parent::persistEntity($entityManager, $entityInstance);
    }
}
