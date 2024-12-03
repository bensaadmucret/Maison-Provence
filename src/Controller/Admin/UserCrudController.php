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
            ->setEntityLabelInSingular('client.title')
            ->setEntityLabelInPlural('client.list')
            ->setDefaultSort(['email' => 'ASC'])
            ->setSearchFields(['email', 'firstName', 'lastName'])
            ->setPageTitle('index', 'client.list')
            ->setPageTitle('new', 'client.new')
            ->setPageTitle('edit', 'client.edit')
            ->setFormOptions([
                'validation_groups' => ['Default', 'create'],
            ]);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'client.id')
            ->hideOnForm();

        yield TextField::new('firstName', 'client.nom')
            ->setFormType(TextType::class)
            ->setFormTypeOption('attr', [
                'placeholder' => 'Prénom',
                'required' => true,
            ]);

        yield TextField::new('lastName', 'client.prenom')
            ->setFormType(TextType::class)
            ->setFormTypeOption('attr', [
                'placeholder' => 'Nom',
                'required' => true,
            ]);

        yield EmailField::new('email', 'client.email')
            ->setFormTypeOption('attr', [
                'placeholder' => 'exemple@email.com',
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
            ->setHelp('Les rôles disponibles sont: ROLE_USER, ROLE_ADMIN')
            ->hideOnIndex();

        if (Crud::PAGE_NEW !== $pageName) {
            yield DateTimeField::new('lastLoginAt', 'Dernière connexion')
                ->setFormat('dd/MM/Y HH:mm:ss');
            yield DateTimeField::new('createdAt', 'client.created_at')
                ->setFormat('dd/MM/Y HH:mm:ss')
                ->hideOnForm();
            yield DateTimeField::new('updatedAt', 'client.updated_at')
                ->setFormat('dd/MM/Y HH:mm:ss')
                ->hideOnForm();
        }
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                return $action->setLabel('action.new');
            })
            ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
                return $action->setLabel('action.edit');
            })
            ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
                return $action->setLabel('action.delete');
            })
            ->update(Crud::PAGE_DETAIL, Action::EDIT, function (Action $action) {
                return $action->setLabel('action.edit');
            })
            ->update(Crud::PAGE_DETAIL, Action::DELETE, function (Action $action) {
                return $action->setLabel('action.delete');
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
