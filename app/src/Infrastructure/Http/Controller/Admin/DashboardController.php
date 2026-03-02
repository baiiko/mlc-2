<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Admin;

use App\Domain\Championship\Entity\Phase;
use App\Domain\Championship\Entity\PhaseResult;
use App\Domain\Championship\Entity\Round;
use App\Domain\Championship\Entity\RoundMap;
use App\Domain\Championship\Entity\RoundRegistration;
use App\Domain\Championship\Entity\Season;
use App\Domain\Championship\Entity\Server;
use App\Domain\Content\Entity\Rule;
use App\Domain\Player\Entity\Player;
use App\Domain\Team\Entity\Team;
use EasyCorp\Bundle\EasyAdminBundle\Config\Asset;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Locale;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(new Expression('is_granted("ROLE_MODERATOR") or is_granted("ROLE_SERVER_ADMIN")'))]
class DashboardController extends AbstractDashboardController
{
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('MLC Administration')
            ->setFaviconPath('favicon.ico')
            ->setLocales([
                Locale::new('fr', 'Français', 'flag-icon flag-icon-fr'),
                Locale::new('en', 'English', 'flag-icon flag-icon-gb'),
            ])
            ->setTranslationDomain('messages');
    }

    public function configureAssets(): Assets
    {
        return Assets::new()
            ->addHtmlContentToHead('<link rel="preconnect" href="https://fonts.googleapis.com">')
            ->addHtmlContentToHead('<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>')
            ->addHtmlContentToHead('<link href="https://fonts.googleapis.com/css2?family=Russo+One&family=Exo+2:wght@300;400;500;600;700&display=swap" rel="stylesheet">')
            ->addHtmlContentToHead('<style>.tm-pseudo { font-family: "Exo 2", sans-serif; }</style>');
    }

    public function configureMenuItems(): iterable
    {
        $locale = $this->container->get('request_stack')->getCurrentRequest()?->getLocale() ?? 'fr';

        yield MenuItem::linkToDashboard('admin.menu.dashboard', 'fa fa-home');

        yield MenuItem::section('admin.menu.management')->setPermission('ROLE_MODERATOR');
        yield MenuItem::linkToCrud('admin.menu.players', 'fa fa-users', Player::class)->setPermission('ROLE_MODERATOR');
        yield MenuItem::linkToCrud('admin.menu.teams', 'fa fa-people-group', Team::class)->setPermission('ROLE_MODERATOR');

        yield MenuItem::section('admin.menu.championship')->setPermission('ROLE_ADMIN');
        yield MenuItem::linkToCrud('admin.menu.seasons', 'fa fa-calendar', Season::class)->setPermission('ROLE_ADMIN');
        yield MenuItem::linkToCrud('admin.menu.rounds', 'fa fa-flag-checkered', Round::class)->setPermission('ROLE_ADMIN');
        yield MenuItem::linkToCrud('admin.menu.phases', 'fa fa-layer-group', Phase::class)->setPermission('ROLE_ADMIN');
        yield MenuItem::linkToCrud('admin.menu.maps', 'fa fa-map', RoundMap::class)->setPermission('ROLE_ADMIN');
        yield MenuItem::linkToCrud('admin.menu.registrations', 'fa fa-clipboard-list', RoundRegistration::class)->setPermission('ROLE_ADMIN');
        yield MenuItem::linkToCrud('admin.menu.results', 'fa fa-trophy', PhaseResult::class)->setPermission('ROLE_ADMIN');

        yield MenuItem::section('admin.menu.infrastructure')->setPermission('ROLE_SERVER_ADMIN');
        yield MenuItem::linkToCrud('admin.menu.servers', 'fa fa-server', Server::class)->setPermission('ROLE_SERVER_ADMIN');

        yield MenuItem::section('admin.menu.content')->setPermission('ROLE_ADMIN');
        yield MenuItem::linkToCrud('admin.menu.rules', 'fa fa-book', Rule::class)->setPermission('ROLE_ADMIN');

        yield MenuItem::section('');
        yield MenuItem::linkToRoute('admin.menu.back_to_site', 'fa fa-arrow-left', 'app_home', ['_locale' => $locale]);
    }
}
