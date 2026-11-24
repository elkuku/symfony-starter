<?php

declare(strict_types=1);

namespace App\Controller\User;

use App\Controller\BaseController;
use App\Enum\UserRole;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user', name: 'app_user_index', methods: ['GET'])]
#[IsGranted(UserRole::ADMIN->value)]
final class Index extends BaseController
{
    public function __invoke(
        UserRepository $userRepository,
        // A page below 1 or that isn't a number is a 404.
        #[MapQueryParameter(options: ['min_range' => 1])] int $page = 1,
    ): Response {
        $users = $userRepository->paginate($page);
        $pageCount = \max(1, (int) \ceil(\count($users) / UserRepository::PER_PAGE));

        if ($page > $pageCount) {
            throw $this->createNotFoundException(\sprintf('Page %d does not exist.', $page));
        }

        return $this->render('user/index.html.twig', [
            'users' => $users,
            'page' => $page,
            'page_count' => $pageCount,
        ]);
    }
}
