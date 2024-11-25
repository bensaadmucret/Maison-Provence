<?php

namespace App\Controller\Admin;

use App\Entity\SiteConfiguration;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AsController]
#[Route('/admin/configuration')]
#[IsGranted('ROLE_ADMIN')]
class SiteConfigurationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return SiteConfiguration::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Configuration')
            ->setEntityLabelInPlural('Configuration')
            ->setPageTitle('index', 'Configuration du site')
            ->setPageTitle('edit', 'Modifier la configuration')
            ->setSearchFields(null)
            ->showEntityActionsInlined();
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::DELETE)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_EDIT, Action::INDEX)
            ->add(Crud::PAGE_EDIT, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('siteName', 'Nom du site')
            ->setHelp('Le nom qui apparaîtra dans le titre du site');

        yield BooleanField::new('maintenanceMode', 'Mode maintenance')
            ->setHelp('Activer/désactiver le mode maintenance')
            ->renderAsSwitch();

        yield TextareaField::new('maintenanceMessage', 'Message de maintenance')
            ->setHelp('Message affiché pendant la maintenance')
            ->hideOnIndex();

        yield BooleanField::new('isEcommerceEnabled', 'Activer la e-boutique')
            ->setHelp('Désactiver pour basculer en mode site vitrine');

        yield TextareaField::new('ecommerceDisabledMessage', 'Message e-boutique désactivée')
            ->setHelp('Message affiché quand la e-boutique est désactivée')
            ->hideOnIndex();

        yield DateTimeField::new('updatedAt', 'Dernière modification')
            ->setFormat('dd/MM/Y HH:mm:ss')
            ->hideOnForm();
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof SiteConfiguration) {
            $entityInstance->setUpdatedAt(new \DateTimeImmutable());
        }
        parent::updateEntity($entityManager, $entityInstance);
    }
}
