<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use App\Entity\ProductSEO;
use App\Entity\SEO;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;

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
            ->setSearchFields(['metaTitle', 'metaDescription', 'canonicalUrl', 'product.name']);
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
            ->setColumns('col-md-12');

        yield CollectionField::new('metaKeywords', 'Mots-clés')
            ->setFormTypeOption('allow_add', true)
            ->setFormTypeOption('allow_delete', true)
            ->setColumns('col-md-12');

        yield BooleanField::new('indexable', 'Indexable')
            ->setColumns('col-md-6');

        yield BooleanField::new('followable', 'Followable')
            ->setColumns('col-md-6');

        // Only show product association for ProductSEO entities
        if ($this->isProductSEO()) {
            yield AssociationField::new('product', 'Produit')
                ->setFormTypeOptions([
                    'class' => Product::class,
                    'choice_label' => 'name',
                ])
                ->setColumns('col-md-12');
        }

        yield TextField::new('openGraphData', 'Données Open Graph')
            ->formatValue(function ($value) {
                if (!$value) {
                    return '';
                }

                return json_encode($value, JSON_PRETTY_PRINT);
            })
            ->onlyOnDetail();
    }

    private function isProductSEO(): bool
    {
        $entity = $this->getContext()?->getEntity()->getInstance();

        return $entity instanceof ProductSEO;
    }
}
