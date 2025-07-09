<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use App\Service\LocaleService;
use App\Service\NavigationService;

use App\Entity\Module;
use App\Entity\User;
use App\Entity\Company;
use App\Entity\CompanyGroup;

#[AdminDashboard(routePath: '/admin/{_locale}', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private TranslatorInterface $translator,
        private LocaleService $localeService,
        private NavigationService $navigationService
    ) {
    }

    #[Route('/admin', name: 'admin_default')]
    public function adminDefault(): Response
    {
        // Redirect /admin to /admin/en (default locale)
        return $this->redirectToRoute('admin', ['_locale' => 'en']);
    }

    #[Route('/admin/{_locale}', name: 'admin', requirements: ['_locale' => '%app.locales.pattern%'])]
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

    /**
     * Configure EasyAdmin dashboard settings
     * 
     * Locales are automatically synchronized with services.yaml app.locales parameter.
     * To add/remove locales:
     * 1. Update the app.locales parameter in config/services.yaml
     * 2. Run: bin/console app:locale:sync
     * 3. Add corresponding display name in LocaleService::getLocaleDisplayName() method
     * 4. Create translation files: messages.{locale}.yaml and EasyAdminBundle.{locale}.yaml
     * 5. Clear cache: bin/console cache:clear
     * 6. Check synchronization: bin/console app:locale:check
     */
    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('kickstarter')
            ->setLocales($this->localeService->getEasyAdminLocales());
    }

    /**
     * Generate EasyAdmin locale configuration from services.yaml app.locales parameter
     * @deprecated Use LocaleService::getEasyAdminLocales() instead
     */
    private function getEasyAdminLocales(): array
    {
        return $this->localeService->getEasyAdminLocales();
    }

    /**
     * Get display name and flag for locale
     * @deprecated Use LocaleService::getLocaleDisplayName() instead
     */
    private function getLocaleDisplayName(string $locale): string
    {
        return $this->localeService->getLocaleDisplayName($locale);
    }

    /**
     * Get route requirements pattern for locales
     * @deprecated Use LocaleService::getLocaleRoutePattern() instead
     */
    private function getLocaleRoutePattern(): string
    {
        return $this->localeService->getLocaleRoutePattern();
    }

    public function configureMenuItems(): iterable
    {
        // Always show dashboard
        yield MenuItem::linkToDashboard($this->translator->trans('Dashboard'), 'fa fa-home');

        /** @var User $user */
        $user = $this->getUser();
        
        if (!$user instanceof User) {
            return;
        }

        // Get accessible modules for the current user
        // Admin users see all active modules, regular users see only modules they have permissions for
        if ($this->navigationService->isUserAdmin($user)) {
            $accessibleModules = $this->navigationService->getAllActiveModules();
        } else {
            $accessibleModules = $this->navigationService->getAccessibleModulesForUser($user);
        }

        $entityMapping = $this->navigationService->getModuleEntityMapping();
        $iconMapping = $this->navigationService->getModuleIconMapping();

        // Generate menu items dynamically based on user permissions and active modules
        foreach ($accessibleModules as $module) {
            $moduleCode = $module->getCode();
            
            // Check if we have an entity mapping for this module
            if (isset($entityMapping[$moduleCode])) {
                $entityClass = $entityMapping[$moduleCode];
                $icon = $iconMapping[$moduleCode] ?? 'fas fa-list';
                $label = $this->translator->trans($module->getName());
                
                yield MenuItem::linkToCrud($label, $icon, $entityClass);
            }
        }
    }
}
