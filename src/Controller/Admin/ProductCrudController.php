<?php

namespace App\Controller\Admin;

use App\Admin\Field\ProductImageField;
use App\Entity\Product;
use App\Entity\ProductSEO;
use App\Form\MediaType;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
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
            ->setMetaTitle('')
            ->setMetaDescription('')
            ->setOpenGraphData([]);

        $product->setSeo($seo);
        $seo->setProduct($product);

        $this->entityManager->persist($seo);

        return $product;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            FormField::addTab('Général'),
            TextField::new('name', 'Nom')
                ->setRequired(true),
            SlugField::new('slug')
                ->setTargetFieldName('name')
                ->hideOnIndex(),
            TextEditorField::new('description', 'Description')
                ->hideOnIndex(),
            MoneyField::new('price', 'Prix')
                ->setCurrency('EUR')
                ->setStoredAsCents(false),
            BooleanField::new('isActive', 'Actif'),
            AssociationField::new('category', 'Catégorie'),

            FormField::addTab('Images'),
            CollectionField::new('media', 'Images')
                ->setEntryType(MediaType::class)
                ->setFormTypeOptions([
                    'by_reference' => false,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'error_bubbling' => false,
                ])
                ->onlyOnForms()
                ->addJsFiles('build/collection.js'),
            ProductImageField::new('media', 'Images')
                ->onlyOnIndex(),

            FormField::addTab('SEO'),
            TextField::new('seo.metaTitle', 'Titre SEO')
                ->onlyOnForms()
                ->setColumns(12),
            TextEditorField::new('seo.metaDescription', 'Description SEO')
                ->onlyOnForms()
                ->setColumns(12)
                ->setNumOfRows(3),
            CollectionField::new('seo.metaKeywords', 'Mots-clés SEO')
                ->onlyOnForms()
                ->setColumns(12)
                ->allowAdd()
                ->allowDelete(),
            BooleanField::new('seo.indexable', 'Indexable')
                ->onlyOnForms()
                ->setColumns(6),
            BooleanField::new('seo.followable', 'Followable')
                ->onlyOnForms()
                ->setColumns(6),
            UrlField::new('seo.canonicalUrl', 'URL Canonique')
                ->onlyOnForms()
                ->setColumns(12),
            ArrayField::new('seo.openGraphData', 'Open Graph Data')
                ->onlyOnForms()
                ->setColumns(12)
                ->setHelp('Format : clé = valeur (ex: og:title = Mon Titre)')
                ->setFormTypeOption('allow_add', true)
                ->setFormTypeOption('allow_delete', true)
                ->setFormTypeOption('delete_empty', true),
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
            });
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        // Si l'entité est un Product
        if ($entityInstance instanceof Product) {
            // S'assurer que l'entité SEO existe
            if (!$entityInstance->getSeo()) {
                $seo = new ProductSEO();
                $seo->setProduct($entityInstance);
                $seo->setIndexable(true)
                    ->setFollowable(true)
                    ->setMetaKeywords([])
                    ->setMetaTitle('')
                    ->setMetaDescription('')
                    ->setOpenGraphData([]);
                $entityInstance->setSeo($seo);
                $entityManager->persist($seo);
            }

            // Parcourir les médias et définir le produit pour chaque média
            foreach ($entityInstance->getMedia() as $media) {
                $media->setProduct($entityInstance);
                $entityManager->persist($media);
            }
        }

        // Persister l'entité principale
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        // Si l'entité est un Product
        if ($entityInstance instanceof Product) {
            // S'assurer que l'entité SEO existe
            if (!$entityInstance->getSeo()) {
                $seo = new ProductSEO();
                $seo->setProduct($entityInstance);
                $seo->setIndexable(true)
                    ->setFollowable(true)
                    ->setMetaKeywords([])
                    ->setMetaTitle('')
                    ->setMetaDescription('')
                    ->setOpenGraphData([]);
                $entityInstance->setSeo($seo);
                $entityManager->persist($seo);
            }

            // Parcourir les médias et définir le produit pour chaque média
            foreach ($entityInstance->getMedia() as $media) {
                $media->setProduct($entityInstance);
                $entityManager->persist($media);
            }
        }

        // Mettre à jour l'entité principale
        parent::updateEntity($entityManager, $entityInstance);
    }
}
