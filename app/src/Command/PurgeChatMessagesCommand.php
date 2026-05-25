<?php

declare(strict_types=1);

namespace App\Command;

use App\Domain\Communication\Repository\ChatMessageRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:chat:purge',
    description: 'Delete chat messages older than the retention threshold (default 1 day)',
)]
final class PurgeChatMessagesCommand extends Command
{
    public function __construct(
        private readonly ChatMessageRepositoryInterface $chatMessageRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('hours', null, InputOption::VALUE_REQUIRED, 'Retention in hours', '24');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $hours = (int) $input->getOption('hours');

        if ($hours <= 0) {
            $io->error('Hours must be a positive integer.');

            return Command::INVALID;
        }

        $threshold = (new \DateTimeImmutable(\sprintf('-%d hours', $hours)))
            ->setTimezone(new \DateTimeZone('UTC'));
        $deleted = $this->chatMessageRepository->deleteOlderThan($threshold);

        $io->success(\sprintf('Deleted %d chat message(s) older than %s.', $deleted, $threshold->format('Y-m-d H:i:s')));

        return Command::SUCCESS;
    }
}
