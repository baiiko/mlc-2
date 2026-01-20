<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Admin;

use App\Domain\Championship\Entity\Phase;
use App\Domain\Championship\Entity\PhaseResult;
use App\Domain\Championship\Entity\PhaseServer;
use App\Domain\Championship\Entity\Round;
use App\Domain\Championship\Entity\RoundMap;
use App\Domain\Championship\Entity\RoundRegistration;
use App\Domain\Championship\Entity\Season;
use App\Domain\Championship\Entity\Server;
use App\Domain\Player\Entity\Player;
use App\Domain\Team\Entity\Team;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Locale;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_MODERATOR')]
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

    public function configureMenuItems(): iterable
    {
        $locale = $this->container->get('request_stack')->getCurrentRequest()?->getLocale() ?? 'fr';

        yield MenuItem::linkToDashboard('admin.menu.dashboard', 'fa fa-home');

        yield MenuItem::section('admin.menu.management');
        yield MenuItem::linkToCrud('admin.menu.players', 'fa fa-users', Player::class);
        yield MenuItem::linkToCrud('admin.menu.teams', 'fa fa-people-group', Team::class);

        yield MenuItem::section('admin.menu.championship')->setPermission('ROLE_ADMIN');
        yield MenuItem::linkToCrud('admin.menu.seasons', 'fa fa-calendar', Season::class)->setPermission('ROLE_ADMIN');
        yield MenuItem::linkToCrud('admin.menu.rounds', 'fa fa-flag-checkered', Round::class)->setPermission('ROLE_ADMIN');
        yield MenuItem::linkToCrud('admin.menu.phases', 'fa fa-layer-group', Phase::class)->setPermission('ROLE_ADMIN');
        yield MenuItem::linkToCrud('admin.menu.maps', 'fa fa-map', RoundMap::class)->setPermission('ROLE_ADMIN');
        yield MenuItem::linkToCrud('admin.menu.registrations', 'fa fa-clipboard-list', RoundRegistration::class)->setPermission('ROLE_ADMIN');
        yield MenuItem::linkToCrud('admin.menu.results', 'fa fa-trophy', PhaseResult::class)->setPermission('ROLE_ADMIN');

        yield MenuItem::section('admin.menu.infrastructure')->setPermission('ROLE_SERVEUR_ADMIN');
        yield MenuItem::linkToCrud('admin.menu.servers', 'fa fa-server', Server::class)->setPermission('ROLE_SERVEUR_ADMIN');
        yield MenuItem::linkToCrud('admin.menu.phase_servers', 'fa fa-network-wired', PhaseServer::class)->setPermission('ROLE_SERVEUR_ADMIN');

        yield MenuItem::section('');
        yield MenuItem::linkToRoute('admin.menu.back_to_site', 'fa fa-arrow-left', 'app_home', ['_locale' => $locale]);
    }
}
