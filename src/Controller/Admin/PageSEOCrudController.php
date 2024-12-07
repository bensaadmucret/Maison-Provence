<?php

namespace App\Controller\Admin;

use App\Entity\PageSEO;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class PageSEOCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return PageSEO::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('SEO de page')
            ->setEntityLabelInPlural('SEO des pages')
            ->setSearchFields(['metaTitle', 'metaDescription', 'canonicalUrl', 'metaKeywords'])
            ->setDefaultSort(['id' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('metaTitle', 'Titre Meta')
            ->setHelp('Le titre qui apparaîtra dans les résultats de recherche (50-60 caractères)');

        yield TextareaField::new('metaDescription', 'Description Meta')
            ->setHelp('La description qui apparaîtra dans les résultats de recherche (150-160 caractères)');

        yield TextField::new('canonicalUrl', 'URL Canonique')
            ->setHelp('URL canonique de la page');

        yield TextField::new('metaKeywords', 'Mots-clés')
            ->setHelp('Mots-clés séparés par des virgules');

        yield BooleanField::new('indexable', 'Indexable')
            ->setHelp('Autoriser l\'indexation par les moteurs de recherche');

        yield BooleanField::new('followable', 'Followable')
            ->setHelp('Autoriser le suivi des liens par les moteurs de recherche');
    }
}
