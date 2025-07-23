<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FrontendController extends AbstractController
{
    #[Route('/', name: 'app_frontend')]
    public function index(): Response
    {
        // redirect
        return $this->redirectToRoute('app_login');
    }
}
