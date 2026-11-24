<?php

declare(strict_types=1);

namespace App\Controller\User;

use App\Controller\BaseController;
use App\Entity\User;
use App\Enum\UserRole;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user/{id}', name: 'app_user_delete', requirements: ['id' => Requirement::DIGITS], methods: ['POST'])]
#[IsGranted(UserRole::ADMIN->value)]
#[IsCsrfTokenValid(new Expression('"delete" ~ args["user"].id'), tokenKey: '_token')]
final class Delete extends BaseController
{
    public function __invoke(User $user, EntityManagerInterface $entityManager): Response
    {
        // Deleting yourself could leave the application without an administrator.
        if ($user->id === $this->getUser()->id) {
            $this->addFlash('danger', 'You cannot delete your own account.');

            return $this->redirectToRoute('app_user_show', ['id' => $user->id], Response::HTTP_SEE_OTHER);
        }

        $entityManager->remove($user);
        $entityManager->flush();

        $this->addFlash('success', 'User has been deleted.');

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }
}
