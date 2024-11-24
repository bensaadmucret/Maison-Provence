<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use App\Entity\ProductSEO;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AsController]
#[Route('/admin/product')]
#[IsGranted('ROLE_ADMIN')]
class ProductCrudController extends AbstractCrudController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Produit')
            ->setEntityLabelInPlural('Produits')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setSearchFields(['name', 'description', 'category.name'])
            ->setPageTitle('index', 'Liste des produits')
            ->setPageTitle('new', 'Créer un produit')
            ->setPageTitle('edit', 'Modifier le produit')
            ->setPageTitle('detail', 'Détails du produit');
    }

    public function createEntity(string $entityFqcn)
    {
        $product = new Product();
        $seo = new ProductSEO();
        $seo->setIndexable(true)
            ->setFollowable(true)
            ->setMetaKeywords([])
            ->setOpenGraphData([]);
        
        $product->setSeo($seo);
        $seo->setProduct($product);
        
        // Persister explicitement l'entité SEO
        $this->entityManager->persist($seo);
        
        return $product;
    }

    public function configureFields(string $pageName): iterable
    {
        // Section Informations Générales
        yield FormField::addTab('Informations Générales');
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('name', 'Nom');
        yield SlugField::new('slug')
            ->setTargetFieldName('name')
            ->hideOnIndex();
        yield TextEditorField::new('description')
            ->hideOnIndex()
            ->setFormType(CKEditorType::class)
            ->setFormTypeOptions([
                'config_name' => 'product_description',
            ]);
        yield MoneyField::new('price', 'Prix')
            ->setCurrency('EUR')
            ->setStoredAsCents(false);
        yield NumberField::new('stock', 'Stock');
        yield AssociationField::new('category', 'Catégorie');
        yield BooleanField::new('isActive', 'Actif');
        yield DateTimeField::new('createdAt', 'Créé le')
            ->hideOnForm();
        yield DateTimeField::new('updatedAt', 'Mis à jour le')
            ->hideOnForm();

        // Section Médias
        yield FormField::addTab('Médias');
        yield CollectionField::new('media', 'Images')
            ->useEntryCrudForm()
            ->setFormTypeOption('by_reference', false)
            ->onlyOnForms();

        if ($pageName === Crud::PAGE_NEW || $pageName === Crud::PAGE_EDIT) {
            // Section SEO
            yield FormField::addTab('SEO');
            yield FormField::addPanel('Métadonnées SEO');

            yield TextField::new('seo.metaTitle', 'Titre meta')
                ->setHelp('Maximum 60 caractères')
                ->setColumns('col-md-12');

            yield TextEditorField::new('seo.metaDescription', 'Description meta')
                ->setHelp('Maximum 160 caractères')
                ->setColumns('col-md-12')
                ->setFormType(CKEditorType::class)
                ->setFormTypeOptions([
                    'config_name' => 'seo_description',
                ]);

            yield UrlField::new('seo.canonicalUrl', 'URL canonique')
                ->setColumns('col-md-12')
                ->setRequired(false);

            yield CollectionField::new('seo.metaKeywords', 'Mots-clés')
                ->setFormTypeOption('allow_add', true)
                ->setFormTypeOption('allow_delete', true)
                ->setColumns('col-md-12');

            yield BooleanField::new('seo.indexable', 'Indexable')
                ->setColumns('col-md-6');

            yield BooleanField::new('seo.followable', 'Followable')
                ->setColumns('col-md-6');
        }
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
            });
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Product) {
            return;
        }

        if (!$entityInstance->getSeo()) {
            $seo = new ProductSEO();
            $seo->setIndexable(true)
                ->setFollowable(true)
                ->setMetaKeywords([])
                ->setOpenGraphData([]);
            $entityInstance->setSeo($seo);
            $seo->setProduct($entityInstance);
            $entityManager->persist($seo);
        }

        $entityManager->flush();
    }
}
