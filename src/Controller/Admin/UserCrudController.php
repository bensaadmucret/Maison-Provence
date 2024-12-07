<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\Voter\UserVoter;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Bundle\SecurityBundle\Security;
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
        private Security $security,
        private UserRepository $userRepository,
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
        $user = $this->getUser();
        $isCurrentUser = $user === $this->getContext()?->getEntity()?->getInstance();
        $isSuperAdmin = $this->security->isGranted('ROLE_SUPER_ADMIN');

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

        // Gestion des rôles selon le contexte
        if (Crud::PAGE_NEW === $pageName) {
            // Dans le formulaire de création
            if ($isSuperAdmin) {
                // Super Admin peut créer des Admin ou User
                $roleChoices = [
                    'Utilisateur' => 'ROLE_USER',
                    'Administrateur' => 'ROLE_ADMIN',
                ];
            } elseif ($this->security->isGranted('ROLE_ADMIN')) {
                // Admin peut créer uniquement des User
                $roleChoices = [
                    'Utilisateur' => 'ROLE_USER',
                ];
            }

            if (isset($roleChoices)) {
                yield ChoiceField::new('roles', 'Rôles')
                    ->setChoices($roleChoices)
                    ->allowMultipleChoices(true)
                    ->renderExpanded()
                    ->setHelp('Sélectionnez le rôle pour cet utilisateur')
                    ->hideOnIndex();
            }
        } elseif ($isCurrentUser) {
            // Affichage en lecture seule de son propre rôle
            yield ChoiceField::new('roles', 'Rôles')
                ->setDisabled(true)
                ->hideOnIndex();
        }

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
            // Bouton Annuler pour la création
            ->add(Crud::PAGE_NEW, Action::INDEX)
            ->update(Crud::PAGE_NEW, Action::INDEX, function (Action $action) {
                return $action->setLabel('Annuler');
            })
            // Bouton Annuler pour l'édition
            ->add(Crud::PAGE_EDIT, Action::INDEX)
            ->update(Crud::PAGE_EDIT, Action::INDEX, function (Action $action) {
                return $action->setLabel('Annuler');
            })
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                return $action->setLabel('action.new')
                    ->displayIf(fn () => $this->security->isGranted('ROLE_ADMIN'));
            })
            ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
                return $action->setLabel('action.edit')
                    ->displayIf(fn (User $entity) => $this->isGranted(UserVoter::EDIT, $entity));
            })
            ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
                return $action->setLabel('action.delete')
                    ->displayIf(fn (User $entity) => $this->isGranted(UserVoter::DELETE, $entity));
            })
            ->update(Crud::PAGE_DETAIL, Action::EDIT, function (Action $action) {
                return $action->setLabel('action.edit')
                    ->displayIf(fn (User $entity) => $this->isGranted(UserVoter::EDIT, $entity));
            })
            ->update(Crud::PAGE_DETAIL, Action::DELETE, function (Action $action) {
                return $action->setLabel('action.delete')
                    ->displayIf(fn (User $entity) => $this->isGranted(UserVoter::DELETE, $entity));
            });
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $queryBuilder = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        // Si l'utilisateur n'est pas super admin, filtrer les super admins
        if (!$this->security->isGranted('ROLE_SUPER_ADMIN')) {
            $queryBuilder
                ->andWhere('entity.roles NOT LIKE :role')
                ->setParameter('role', '%ROLE_SUPER_ADMIN%');
        }

        return $this->addOrderByClauses($queryBuilder, $searchDto);
    }

    private function addOrderByClauses(QueryBuilder $queryBuilder, SearchDto $searchDto): QueryBuilder
    {
        $sort = $searchDto->getSort();
        foreach ($sort as $field => $direction) {
            if ('lastName' === $field) {
                $queryBuilder->orderBy('entity.lastName', $direction);
            } elseif ('firstName' === $field) {
                $queryBuilder->addOrderBy('entity.firstName', $direction);
            } elseif ('email' === $field) {
                $queryBuilder->addOrderBy('entity.email', $direction);
            }
        }

        return $queryBuilder;
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof User) {
            return;
        }

        if (!$this->isGranted(UserVoter::EDIT, $entityInstance)) {
            throw $this->createAccessDeniedException('Vous n\'avez pas les droits pour créer cet utilisateur.');
        }

        $this->updatePassword($entityInstance);
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof User) {
            return;
        }

        if (!$this->isGranted(UserVoter::EDIT, $entityInstance)) {
            throw $this->createAccessDeniedException('Vous n\'avez pas les droits pour modifier cet utilisateur.');
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
