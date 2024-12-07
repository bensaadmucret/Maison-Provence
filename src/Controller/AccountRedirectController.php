<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class AccountRedirectController extends AbstractController
{
    #[Route('/mon-compte', name: 'app_account_redirect')]
    public function redirectToAccount(AuthorizationCheckerInterface $authorizationChecker): Response
    {
        // Vérifier si l'utilisateur est connecté
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Rediriger vers l'admin UNIQUEMENT si l'utilisateur a le rôle ADMIN
        if ($authorizationChecker->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('easyadmin');
        } else {
            throw new AccessDeniedException('Vous n\'avez pas les droits nécessaires pour accéder à cette page.');
        }

        // Rediriger vers le tableau de bord client pour les utilisateurs standard
        return $this->redirectToRoute('app_customer_dashboard');
    }
}
