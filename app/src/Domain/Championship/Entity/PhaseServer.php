<?php

declare(strict_types=1);

namespace App\Domain\Championship\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity]
#[ORM\Table(name: 'phase_server')]
#[ORM\UniqueConstraint(name: 'phase_server_unique', columns: ['phase_id', 'server_id'])]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false)]
class PhaseServer
{
    use TimestampableEntity;
    use SoftDeleteableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Phase::class, inversedBy: 'phaseServers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Phase $phase = null;

    #[ORM\ManyToOne(targetEntity: Server::class, inversedBy: 'phaseServers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Server $server = null;

    #[ORM\Column]
    private int $serverNumber = 1;

    public function __construct(?Phase $phase = null, ?Server $server = null, int $serverNumber = 1)
    {
        $this->phase = $phase;
        $this->server = $server;
        $this->serverNumber = $serverNumber;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPhase(): ?Phase
    {
        return $this->phase;
    }

    public function setPhase(?Phase $phase): self
    {
        $this->phase = $phase;

        return $this;
    }

    public function getServer(): ?Server
    {
        return $this->server;
    }

    public function setServer(?Server $server): self
    {
        $this->server = $server;

        return $this;
    }

    public function getServerNumber(): int
    {
        return $this->serverNumber;
    }

    public function setServerNumber(int $serverNumber): self
    {
        $this->serverNumber = $serverNumber;

        return $this;
    }

    public function __toString(): string
    {
        return sprintf(
            'Serveur %d: %s',
            $this->serverNumber,
            $this->server?->getName() ?? ''
        );
    }
}
