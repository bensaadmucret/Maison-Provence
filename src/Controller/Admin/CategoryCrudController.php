<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use App\Entity\CategorySEO;
use App\Service\SEOService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AsController]
#[Route('/admin/category')]
#[IsGranted('ROLE_ADMIN')]
class CategoryCrudController extends AbstractCrudController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SEOService $seoService,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Category::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Catégorie')
            ->setEntityLabelInPlural('Catégories')
            ->setDefaultSort(['name' => 'ASC'])
            ->setSearchFields(['name', 'description', 'seo.metaTitle', 'seo.metaDescription'])
            ->setPageTitle('index', 'Liste des catégories')
            ->setPageTitle('new', 'Créer une catégorie')
            ->setPageTitle('edit', 'Modifier la catégorie')
            ->setPageTitle('detail', 'Détails de la catégorie');
    }

    public function createEntity(string $entityFqcn)
    {
        $category = new Category();
        $seo = new CategorySEO();
        $seo->setIndexable(true)
            ->setFollowable(true)
            ->setMetaKeywords([])
            ->setMetaTitle('')
            ->setMetaDescription('')
            ->setOpenGraphData([]);

        $category->setSeo($seo);
        $seo->setCategory($category);

        $this->entityManager->persist($seo);

        return $category;
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
                'config_name' => 'category_description',
            ]);

        // Relations
        yield AssociationField::new('parent', 'Catégorie parente')
            ->setRequired(false)
            ->setFormTypeOption('placeholder', 'Sélectionnez une catégorie parente (optionnel)');

        yield AssociationField::new('children', 'Sous-catégories')
            ->onlyOnIndex();

        yield AssociationField::new('products', 'Produits')
            ->onlyOnIndex();

        if (Crud::PAGE_INDEX !== $pageName) {
            // Section SEO
            yield FormField::addTab('SEO');
            yield FormField::addPanel('Métadonnées SEO');

            $entity = $this->getContext()->getEntity()->getInstance();
            if (!$entity->getSeo()) {
                $seo = new CategorySEO();
                $seo->setCategory($entity);
                $seo->setIndexable(true)
                    ->setFollowable(true)
                    ->setMetaKeywords([])
                    ->setMetaTitle('')
                    ->setMetaDescription('')
                    ->setOpenGraphData([]);
                $entity->setSeo($seo);
                $this->entityManager->persist($seo);
                $this->entityManager->flush();
            }

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

            yield ArrayField::new('seo.metaKeywords', 'Mots-clés')
                ->setHelp('Liste des mots-clés')
                ->setColumns('col-md-12');

            yield BooleanField::new('seo.indexable', 'Indexable')
                ->setHelp('Autoriser l\'indexation par les moteurs de recherche')
                ->setColumns('col-md-6');

            yield BooleanField::new('seo.followable', 'Followable')
                ->setHelp('Autoriser le suivi des liens par les moteurs de recherche')
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

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Category) {
            // S'assurer que l'entité SEO existe
            if (!$entityInstance->getSeo()) {
                $seo = new CategorySEO();
                $seo->setCategory($entityInstance);
                $seo->setIndexable(true)
                    ->setFollowable(true)
                    ->setMetaKeywords([])
                    ->setMetaTitle('')
                    ->setMetaDescription('')
                    ->setOpenGraphData([]);
                $entityInstance->setSeo($seo);
                $entityManager->persist($seo);
            }
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Category) {
            // S'assurer que l'entité SEO existe
            if (!$entityInstance->getSeo()) {
                $seo = new CategorySEO();
                $seo->setCategory($entityInstance);
                $seo->setIndexable(true)
                    ->setFollowable(true)
                    ->setMetaKeywords([])
                    ->setMetaTitle('')
                    ->setMetaDescription('')
                    ->setOpenGraphData([]);
                $entityInstance->setSeo($seo);
                $entityManager->persist($seo);
            }

            // Mise à jour des mots-clés si nécessaire
            if ($seo = $entityInstance->getSeo()) {
                $keywords = $seo->getMetaKeywords();
                if (!is_array($keywords)) {
                    // Si les mots-clés ne sont pas un tableau, les convertir
                    $keywords = !empty($keywords) ? array_map('trim', explode(',', $keywords)) : [];
                    $seo->setMetaKeywords($keywords);
                }
            }
        }

        parent::updateEntity($entityManager, $entityInstance);
    }
}
