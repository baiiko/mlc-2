<?php

declare(strict_types=1);

namespace App\Command;

use App\Application\Player\Service\ChangeLoginServiceInterface;
use App\Domain\Player\Repository\PlayerRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fix-player-logins',
    description: 'Fix player logins that were incorrectly imported from legacy (MLC login instead of TM login)',
)]
class FixPlayerLoginsCommand extends Command
{
    /**
     * Mapping: MLC login (currently in DB) => real TM login (what it should be).
     * This is the REVERSE of the tmToMlcLogin mapping used during migration.
     */
    private const LOGIN_FIXES = [
        'jashugan01' => 'jashounet',
        '-ne' => 'ne-',
        'marswann' => 'matador33',
        'Domi_5' => 'domi_7',
        'totoman1' => 'totoman',
        'yuggoth' => '4lturbo',
    ];

    public function __construct(
        private readonly PlayerRepositoryInterface $playerRepository,
        private readonly ChangeLoginServiceInterface $changeLoginService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Afficher les changements sans les appliquer');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');

        $io->title('Correction des logins joueurs (legacy → TM)');

        if ($dryRun) {
            $io->note('Mode dry-run : aucune modification ne sera effectuée.');
        }

        $headers = ['Login actuel', 'Login TM correct', 'Statut'];
        $rows = [];

        foreach (self::LOGIN_FIXES as $currentLogin => $correctLogin) {
            $player = $this->playerRepository->findByLogin($currentLogin);

            if ($player === null) {
                $rows[] = [$currentLogin, $correctLogin, 'Joueur non trouvé (déjà corrigé ?)'];
                continue;
            }

            if ($dryRun) {
                $rows[] = [$currentLogin, $correctLogin, 'À corriger'];
                continue;
            }

            $result = $this->changeLoginService->changeLogin($player, $correctLogin);

            if ($result['success']) {
                $rows[] = [
                    $currentLogin,
                    $correctLogin,
                    \sprintf('OK (%d records, %d phases)', $result['updatedRecords'], $result['updatedPhases']),
                ];
            } else {
                $rows[] = [$currentLogin, $correctLogin, 'Erreur : ' . $result['error']];
            }
        }

        $io->table($headers, $rows);

        if ($dryRun) {
            $io->info('Relancer sans --dry-run pour appliquer les corrections.');
        } else {
            $io->success('Corrections appliquées.');
        }

        return Command::SUCCESS;
    }
}
