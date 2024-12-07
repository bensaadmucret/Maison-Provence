<?php

namespace App\Controller\Admin;

use App\Entity\LegalPage;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class LegalPageCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return LegalPage::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Page légale')
            ->setEntityLabelInPlural('Pages légales')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setSearchFields(['title', 'content', 'slug'])
            ->setAutofocusSearch();
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('title', 'Titre');
        yield SlugField::new('slug')
            ->setTargetFieldName('title')
            ->setUnlockConfirmationMessage(
                'Il est recommandé de laisser le slug se générer automatiquement, mais vous pouvez le personnaliser.'
            );
        yield TextEditorField::new('content', 'Contenu')
            ->setTrixEditorConfig([
                'blockAttributes' => [
                    'default' => ['tagName' => 'p'],
                    'heading1' => ['tagName' => 'h1'],
                ],
            ])
            ->hideOnIndex();
        yield AssociationField::new('seo', 'SEO')
            ->setCrudController(PageSEOCrudController::class);
        yield DateTimeField::new('createdAt', 'Créé le')
            ->hideOnForm();
        yield DateTimeField::new('updatedAt', 'Mis à jour le')
            ->hideOnForm();
    }
}
