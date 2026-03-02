<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\Content\Repository\RuleRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

#[AsController]
final readonly class RuleController
{
    public function __construct(
        private Environment $twig,
        private RuleRepositoryInterface $ruleRepository,
    ) {
    }

    #[Route('/rules', name: 'app_rules')]
    public function __invoke(Request $request): Response
    {
        $rule = $this->ruleRepository->findLatest();

        return new Response(
            $this->twig->render('rule/index.html.twig', [
                'rule' => $rule,
                'locale' => $request->getLocale(),
            ])
        );
    }
}
