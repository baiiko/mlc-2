<?php

declare(strict_types=1);

namespace App\Application\Player\Handler;

use App\Application\Player\Command\RegisterPlayerCommand;
use App\Application\Player\Notification\PlayerNotificationInterface;
use App\Domain\Player\Entity\Player;
use App\Domain\Player\Repository\PlayerRepositoryInterface;

final readonly class RegisterPlayerHandler implements RegisterPlayerHandlerInterface
{
    public function __construct(
        private PlayerRepositoryInterface $playerRepository,
        private PlayerNotificationInterface $playerNotification,
    ) {
    }

    public function __invoke(RegisterPlayerCommand $command): Player
    {
        $player = new Player($command->login, $command->email, $command->pseudo);
        $player->setDiscord($command->discord);
        $player->setNewsletter($command->newsletter);

        $this->playerRepository->save($player);
        $this->playerNotification->sendWelcomeEmail($player, $command->locale);

        return $player;
    }
}
