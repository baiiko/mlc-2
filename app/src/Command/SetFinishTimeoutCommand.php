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
    name: 'app:server:set-finish-timeout',
    description: 'Change le finishtimeout (délai après la première arrivée) en direct sur un serveur TM.',
)]
final class SetFinishTimeoutCommand extends Command
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
            ->addArgument('seconds', InputArgument::OPTIONAL, 'Durée en secondes', '60');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $login = (string) $input->getArgument('server');
        $seconds = (int) $input->getArgument('seconds');

        if ($seconds <= 0) {
            $io->error('Durée invalide (doit être > 0).');

            return Command::FAILURE;
        }

        $server = $this->serverRepository->findByLogin($login);

        if ($server === null) {
            $io->error(\sprintf('Serveur inconnu : %s', $login));

            return Command::FAILURE;
        }

        $milliseconds = $seconds * 1000;
        $result = $this->serverCommandService->setFinishTimeout($server, $milliseconds);

        if (!$result['success']) {
            $io->error(\sprintf('[%s] échec : %s', $server->getName(), $result['message']));

            return Command::FAILURE;
        }

        $io->success(\sprintf('[%s] %s', $server->getName(), $result['message']));

        return Command::SUCCESS;
    }
}
