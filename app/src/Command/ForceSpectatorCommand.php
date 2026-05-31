<?php

declare(strict_types=1);

namespace App\Command;

use App\Application\Championship\Service\ServerCommandService;
use App\Domain\Championship\Repository\ServerRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:server:force-spectator',
    description: 'Force un joueur en spectateur sur un serveur TM.',
)]
final class ForceSpectatorCommand extends Command
{
    public function __construct(
        private readonly ServerRepositoryInterface $serverRepository,
        private readonly ServerCommandService $serverCommandService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('server', InputArgument::REQUIRED, 'Login du serveur (champ Server.login)')
            ->addArgument('player', InputArgument::REQUIRED, 'Login du joueur à forcer')
            ->addArgument('mode', InputArgument::OPTIONAL, '1=spectateur, 2=joueur, 3=spectateur sélectionnable, 0=user-sélectionnable', '1');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $serverLogin = (string) $input->getArgument('server');
        $playerLogin = (string) $input->getArgument('player');
        $mode = (int) $input->getArgument('mode');

        if (!\in_array($mode, [0, 1, 2, 3], true)) {
            $io->error('Mode invalide (doit être 0, 1, 2 ou 3).');

            return Command::FAILURE;
        }

        $server = $this->serverRepository->findByLogin($serverLogin);

        if ($server === null) {
            $io->error(\sprintf('Serveur inconnu : %s', $serverLogin));

            return Command::FAILURE;
        }

        $result = $this->serverCommandService->forceSpectator($server, $playerLogin, $mode);

        if (!$result['success']) {
            $io->error(\sprintf('[%s] échec : %s', $server->getName(), $result['message']));

            return Command::FAILURE;
        }

        $io->success(\sprintf('[%s] %s', $server->getName(), $result['message']));

        return Command::SUCCESS;
    }
}
