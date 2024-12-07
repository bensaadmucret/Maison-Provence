<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class UserVoter extends Voter
{
    public const VIEW = 'view';
    public const EDIT = 'edit';
    public const DELETE = 'delete';

    public function __construct(
        private Security $security,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE])
            && $subject instanceof User;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            return false;
        }

        /** @var User $targetUser */
        $targetUser = $subject;

        // Si c'est un super admin, il a tous les droits
        if ($this->security->isGranted('ROLE_SUPER_ADMIN')) {
            return true;
        }

        // Un admin ne peut pas modifier/supprimer d'autres admins
        if (in_array('ROLE_ADMIN', $targetUser->getRoles()) && $user !== $targetUser) {
            return false;
        }

        switch ($attribute) {
            case self::VIEW:
                // Un admin peut voir tous les utilisateurs
                return $this->security->isGranted('ROLE_ADMIN');

            case self::EDIT:
                // Un admin peut éditer son propre compte ou les comptes non-admin
                return $this->security->isGranted('ROLE_ADMIN')
                    && ($user === $targetUser || !in_array('ROLE_ADMIN', $targetUser->getRoles()));

            case self::DELETE:
                // Un admin peut supprimer uniquement les comptes non-admin
                return $this->security->isGranted('ROLE_ADMIN')
                    && !in_array('ROLE_ADMIN', $targetUser->getRoles());
        }

        return false;
    }
}
