<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

#[AsController]
final readonly class HomeController
{
    public function __construct(
        private Environment $twig,
    ) {}

    #[Route('/', name: 'app_home')]
    public function __invoke(): Response
    {
        // Données de test pour le classement
        $players = [
            ['rank' => 1, 'name' => 'Speedy_King', 'team' => 'Team Velocity', 'initials' => 'SK', 'points' => 2847, 'wins' => 8, 'podiums' => 14, 'trend' => 'same', 'trend_value' => 0],
            ['rank' => 2, 'name' => 'NightRacer_FR', 'team' => 'French Drift', 'initials' => 'NR', 'points' => 2654, 'wins' => 6, 'podiums' => 12, 'trend' => 'up', 'trend_value' => 1],
            ['rank' => 3, 'name' => 'TurboX_', 'team' => 'X-Racing', 'initials' => 'TX', 'points' => 2521, 'wins' => 5, 'podiums' => 11, 'trend' => 'down', 'trend_value' => 1],
            ['rank' => 4, 'name' => 'DriftMaster', 'team' => 'Solo', 'initials' => 'DM', 'points' => 2398, 'wins' => 4, 'podiums' => 9, 'trend' => 'up', 'trend_value' => 2],
            ['rank' => 5, 'name' => 'ZenFlow', 'team' => 'Chill Racing', 'initials' => 'ZF', 'points' => 2245, 'wins' => 3, 'podiums' => 8, 'trend' => 'same', 'trend_value' => 0],
        ];

        // Données de test pour les évènements
        $events = [
            ['round' => 15, 'name' => 'Grand Prix de la Vitesse', 'date' => 'Samedi 11 Janvier 2025', 'time' => '21h00 (CET)', 'circuit' => 'Speed Valley', 'status' => 'live'],
            ['round' => 16, 'name' => 'Night Race Challenge', 'date' => 'Samedi 18 Janvier 2025', 'time' => '21h00 (CET)', 'circuit' => 'Midnight Run', 'status' => 'upcoming'],
            ['round' => 17, 'name' => 'Drift Masters Cup', 'date' => 'Samedi 25 Janvier 2025', 'time' => '21h00 (CET)', 'circuit' => 'Drift Paradise', 'status' => 'upcoming'],
        ];

        // Données de test pour les news
        $news = [
            ['tag' => 'Annonce', 'title' => 'La Saison 13 arrive en Février !', 'excerpt' => 'Préparez-vous pour une nouvelle saison pleine de surprises. Nouveau système de points, nouvelles maps et tournoi spécial pour le lancement.', 'date' => '5 Janvier 2025', 'author' => 'Admin', 'featured' => true],
            ['tag' => 'Résultats', 'title' => 'Speedy_King remporte le Round 14', 'date' => '3 Janvier 2025', 'featured' => false],
            ['tag' => 'Communauté', 'title' => 'Nouveau record sur Alpine Sprint', 'date' => '1er Janvier 2025', 'featured' => false],
            ['tag' => 'Mise à jour', 'title' => 'Règlement mis à jour pour 2025', 'date' => '28 Décembre 2024', 'featured' => false],
        ];

        return new Response(
            $this->twig->render('home/index.html.twig', [
                'players' => $players,
                'events' => $events,
                'news' => $news,
            ])
        );
    }
}
