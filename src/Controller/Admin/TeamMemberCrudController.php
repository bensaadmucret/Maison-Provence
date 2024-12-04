<?php

namespace App\Controller\Admin;

use App\Entity\TeamMember;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use Vich\UploaderBundle\Form\Type\VichImageType;

class TeamMemberCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return TeamMember::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Membre de l\'équipe')
            ->setEntityLabelInPlural('Membres de l\'équipe')
            ->setDefaultSort(['position' => 'ASC'])
            ->setPageTitle('index', 'Gérer l\'équipe')
            ->setPageTitle('new', 'Ajouter un membre')
            ->setPageTitle('edit', 'Modifier un membre')
            ->setFormOptions([
                'validation_groups' => ['Default']
            ])
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('firstname', 'Prénom')
                ->setHelp('Prénom du membre de l\'équipe')
                ->setColumns(6),
                
            TextField::new('name', 'Nom')
                ->setHelp('Nom de famille du membre de l\'équipe')
                ->setColumns(6),
                
            TextField::new('role', 'Rôle')
                ->setHelp('Fonction ou rôle dans l\'équipe')
                ->setColumns(6),
                
            TextareaField::new('description', 'Description')
                ->setHelp('Description détaillée du rôle et de l\'expérience')
                ->setColumns(12)
                ->hideOnIndex()
                ->setNumOfRows(5),
                
            TextField::new('photoFile', 'Photo')
                ->setFormType(VichImageType::class)
                ->setFormTypeOptions([
                    'allow_delete' => true,
                    'delete_label' => 'Supprimer la photo',
                    'download_uri' => false,
                    'image_uri' => true,
                    'asset_helper' => true,
                ])
                ->onlyOnForms()
                ->setColumns(12),
                
            ImageField::new('photo', 'Photo')
                ->setBasePath('/uploads/team')
                ->onlyOnIndex(),
                
            IntegerField::new('position', 'Position')
                ->setHelp('Ordre d\'affichage (plus petit nombre = apparaît en premier)')
                ->setColumns(6),
        ];
    }
}
