<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Repository\ProjectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardCustomController extends AbstractController
{
    public function __construct(private ProjectRepository $projectRepository)
    {
    }

    #[Route('/admin/{_locale}/dashboard/editor', name: 'app_admin_dashboard_editor')]
    public function dashboard1(): Response
    {
        // only select projects where i am assigned
        $user = $this->getUser();

        if ($user === null) {
            return $this->redirectToRoute('admin');
        }

        $projects = $this->projectRepository->findBy(['assignee' => $user]);

        // Render the dashboard template
        return $this->render('body/admin/dashboard/dashboard_editor.html.twig', [
            'projects' => $projects,
        ]);
    }

    #[Route('/admin/{_locale}/dashboard/manager', name: 'app_admin_dashboard_manager')]
    public function dashboard2(): Response
    {
        $projects = $this->projectRepository->findAll();

        // Render the dashboard template
        return $this->render('body/admin/dashboard/dashboard_manager.html.twig', [
            'projects' => $projects,
        ]);
    }
}
