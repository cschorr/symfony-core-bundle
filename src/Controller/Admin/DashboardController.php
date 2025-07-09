<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use App\Entity\Module;
use App\Entity\User;
use App\Entity\Company;
use App\Entity\CompanyGroup;

#[AdminDashboard(routePath: '/admin/{_locale}', routeName: 'admin')]
#[Route('/admin/{_locale}', name: 'admin', requirements: ['_locale' => 'en|fr|de'])]
class DashboardController extends AbstractDashboardController
{
    #[Route('/admin', name: 'admin_default')]
    public function adminDefault(): Response
    {
        // Redirect /admin to /admin/en (default locale)
        return $this->redirectToRoute('admin', ['_locale' => 'en']);
    }
    public function index(): Response
    {
        #return parent::index();

        // Option 1. You can make your dashboard redirect to some common page of your backend
        //
        // 1.1) If you have enabled the "pretty URLs" feature:
        return $this->redirectToRoute('admin_user_index');
        //
        // 1.2) Same example but using the "ugly URLs" that were used in previous EasyAdmin versions:
        // $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        // return $this->redirect($adminUrlGenerator->setController(OneOfYourCrudController::class)->generateUrl());

        // Option 2. You can make your dashboard redirect to different pages depending on the user
        //
        // if ('jane' === $this->getUser()->getUsername()) {
        //     return $this->redirectToRoute('...');
        // }

        // Option 3. You can render some custom template to display a proper dashboard with widgets, etc.
        // (tip: it's easier if your template extends from @EasyAdmin/page/content.html.twig)
        //
        // return $this->render('some/path/my-dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('kickstarter')
            ->setLocales(['en' => '🇺🇸 English', 'fr' => '🇫🇷 Français', 'de' => '🇩🇪 Deutsch']);
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToCrud('Module', 'fas fa-list', Module::class);
        yield MenuItem::linkToCrud('Benutzer', 'fas fa-list', User::class);
        yield MenuItem::linkToCrud('Unternehmen', 'fas fa-building', Company::class);
        yield MenuItem::linkToCrud('Unternehmensgruppen', 'fas fa-users', CompanyGroup::class);
    }
}
