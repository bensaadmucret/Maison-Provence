<?php

namespace App\Controller\Admin;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AsController]
#[Route('/admin/user')]
#[IsGranted('ROLE_ADMIN')]
class UserCrudController extends AbstractCrudController
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Utilisateur')
            ->setEntityLabelInPlural('Utilisateurs')
            ->setDefaultSort(['email' => 'ASC'])
            ->setSearchFields(['email', 'firstName', 'lastName'])
            ->setPageTitle('index', 'Liste des utilisateurs')
            ->setPageTitle('new', 'Créer un utilisateur')
            ->setPageTitle('edit', 'Modifier l\'utilisateur')
            ->setPageTitle('detail', 'Détails de l\'utilisateur')
            ->setFormOptions([
                'validation_groups' => ['Default', 'create'],
            ]);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();

        yield EmailField::new('email', 'Email')
            ->setFormTypeOption('attr', [
                'placeholder' => 'exemple@email.com',
                'required' => true,
            ]);

        yield TextField::new('firstName', 'Prénom')
            ->setFormType(TextType::class)
            ->setFormTypeOption('attr', [
                'placeholder' => 'Prénom',
                'required' => true,
            ]);

        yield TextField::new('lastName', 'Nom')
            ->setFormType(TextType::class)
            ->setFormTypeOption('attr', [
                'placeholder' => 'Nom',
                'required' => true,
            ]);

        if (Crud::PAGE_NEW === $pageName || Crud::PAGE_EDIT === $pageName) {
            yield TextField::new('plainPassword', 'Mot de passe')
                ->setFormType(PasswordType::class)
                ->setRequired(Crud::PAGE_NEW === $pageName)
                ->setFormTypeOption('attr', [
                    'placeholder' => Crud::PAGE_NEW === $pageName ? 'Mot de passe' : 'Laisser vide pour ne pas modifier',
                    'required' => Crud::PAGE_NEW === $pageName,
                ]);
        }

        yield ArrayField::new('roles', 'Rôles')
            ->setHelp('Les rôles disponibles sont: ROLE_USER, ROLE_ADMIN');

        if (Crud::PAGE_NEW !== $pageName) {
            yield DateTimeField::new('lastLoginAt', 'Dernière connexion')
                ->setFormat('dd/MM/Y HH:mm:ss');
            yield DateTimeField::new('createdAt', 'Créé le')
                ->setFormat('dd/MM/Y HH:mm:ss')
                ->hideOnForm();
            yield DateTimeField::new('updatedAt', 'Mis à jour le')
                ->setFormat('dd/MM/Y HH:mm:ss')
                ->hideOnForm();
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
        if (!$entityInstance instanceof User) {
            return;
        }

        $this->updatePassword($entityInstance);
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof User) {
            return;
        }

        $this->updatePassword($entityInstance);
        parent::updateEntity($entityManager, $entityInstance);
    }

    private function updatePassword(User $user): void
    {
        if (!$user->getPlainPassword()) {
            return;
        }

        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            $user->getPlainPassword()
        );

        $user->setPassword($hashedPassword);
        $user->eraseCredentials();
    }
}
