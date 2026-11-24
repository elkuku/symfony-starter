<?php

declare(strict_types=1);

namespace App\Controller\Security;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

final class GoogleIdentityController extends AbstractController
{
    /**
     * GoogleIdentityAuthenticator handles this route before the controller is reached.
     */
    #[Route(path: '/connect/google/verify', name: 'connect_google_verify', methods: ['POST'])]
    public function connectVerify(): never
    {
        throw new \LogicException('This route is handled by GoogleIdentityAuthenticator.');
    }
}
