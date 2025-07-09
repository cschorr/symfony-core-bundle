<?php

namespace App\Controller;

use App\Service\LocaleService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Translation\TranslatorInterface;

class SecurityController extends AbstractController
{
    public function __construct(
        private LocaleService $localeService,
        private TranslatorInterface $translator
    ) {
    }

    #[Route(path: '/login', name: 'app_login')]
    #[Route(path: '/login/{_locale}', name: 'app_login_locale', requirements: ['_locale' => '%app.locales.pattern%'])]
    public function login(AuthenticationUtils $authenticationUtils, string $_locale = null): Response
    {
        // Use default locale if none provided
        if ($_locale === null) {
            $supportedLocales = $this->localeService->getSupportedLocales();
            $_locale = $supportedLocales[0] ?? 'de'; // fallback to 'de' if no locales configured
        }

        // redirect to easyadmin dashboard if logged on
        if ($this->getUser()) {
            return $this->redirectToRoute('admin', ['_locale' => $_locale]);
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('body/security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'current_locale' => $_locale,
            'available_locales' => $this->localeService->getEasyAdminLocales(),
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
