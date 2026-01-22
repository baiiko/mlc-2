<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Listener;

use App\Domain\Championship\Entity\Server;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::prePersist, entity: Server::class)]
#[AsEntityListener(event: Events::preUpdate, entity: Server::class)]
class ServerPasswordListener
{
    public function prePersist(Server $server): void
    {
        $this->handlePassword($server);
    }

    public function preUpdate(Server $server): void
    {
        $this->handlePassword($server);
    }

    private function handlePassword(Server $server): void
    {
        $plainPassword = $server->getPlainPassword();

        if ($plainPassword) {
            $server->setPassword($plainPassword);
            $server->setPlainPassword(null);
        }
    }
}
