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

use App\Entity\Module;
use App\Entity\User;
use App\Entity\Company;
use App\Entity\CompanyGroup;

#[AdminDashboard(routePath: '/admin/{_locale}', routeName: 'admin')]
#[Route('/admin/{_locale}', name: 'admin', requirements: ['_locale' => 'en|fr|de|zh_TW'])]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private TranslatorInterface $translator,
        private ParameterBagInterface $parameterBag
    ) {
    }

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

    /**
     * Configure EasyAdmin dashboard settings
     * 
     * Locales are automatically synchronized with services.yaml app.locales parameter.
     * To add/remove locales:
     * 1. Update the app.locales parameter in config/services.yaml
     * 2. Add corresponding display name in getLocaleDisplayName() method
     * 3. Create translation files: messages.{locale}.yaml and EasyAdminBundle.{locale}.yaml
     * 4. Clear cache: bin/console cache:clear
     * 5. Check synchronization: bin/console app:locale:check
     */
    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('kickstarter')
            ->setLocales($this->getEasyAdminLocales());
    }

    /**
     * Generate EasyAdmin locale configuration from services.yaml app.locales parameter
     */
    private function getEasyAdminLocales(): array
    {
        $appLocales = $this->parameterBag->get('app.locales');
        $localeMap = [];

        foreach ($appLocales as $locale) {
            $localeMap[$locale] = $this->getLocaleDisplayName($locale);
        }

        return $localeMap;
    }

    /**
     * Get display name and flag for locale
     */
    private function getLocaleDisplayName(string $locale): string
    {
        return match ($locale) {
            'en' => '🇺🇸 English',
            'fr' => '🇫🇷 Français',
            'de' => '🇩🇪 Deutsch',
            'zh_TW' => '🇹🇼 繁體中文',
            default => strtoupper($locale)
        };
    }

    /**
     * Get route requirements pattern for locales
     */
    private function getLocaleRoutePattern(): string
    {
        $appLocales = $this->parameterBag->get('app.locales');
        return implode('|', $appLocales);
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard($this->translator->trans('Dashboard'), 'fa fa-home');
        yield MenuItem::linkToCrud($this->translator->trans('Module'), 'fas fa-list', Module::class);
        yield MenuItem::linkToCrud($this->translator->trans('User'), 'fas fa-list', User::class);
        yield MenuItem::linkToCrud($this->translator->trans('Company'), 'fas fa-building', Company::class);
        yield MenuItem::linkToCrud($this->translator->trans('CompanyGroup'), 'fas fa-users', CompanyGroup::class);
    }
}
