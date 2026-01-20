<?php

declare(strict_types=1);

namespace App\Domain\Championship\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity]
#[ORM\Table(name: 'phase')]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false)]
class Phase
{
    use TimestampableEntity;
    use SoftDeleteableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Round::class, inversedBy: 'phases')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Round $round = null;

    #[ORM\Column(length: 20, enumType: PhaseType::class)]
    private ?PhaseType $type = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $startAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $endAt = null;

    /** @var Collection<int, PhaseResult> */
    #[ORM\OneToMany(targetEntity: PhaseResult::class, mappedBy: 'phase', cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $results;

    /** @var Collection<int, PhaseServer> */
    #[ORM\OneToMany(targetEntity: PhaseServer::class, mappedBy: 'phase', cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['serverNumber' => 'ASC'])]
    private Collection $phaseServers;

    public function __construct(?Round $round = null, ?PhaseType $type = null, ?\DateTimeImmutable $startAt = null)
    {
        $this->round = $round;
        $this->type = $type;
        $this->startAt = $startAt;
        $this->results = new ArrayCollection();
        $this->phaseServers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRound(): ?Round
    {
        return $this->round;
    }

    public function setRound(Round $round): self
    {
        $this->round = $round;

        return $this;
    }

    public function getType(): ?PhaseType
    {
        return $this->type;
    }

    public function setType(PhaseType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getStartAt(): ?\DateTimeImmutable
    {
        return $this->startAt;
    }

    public function setStartAt(\DateTimeImmutable $startAt): self
    {
        $this->startAt = $startAt;

        return $this;
    }

    public function getEndAt(): ?\DateTimeImmutable
    {
        return $this->endAt;
    }

    public function setEndAt(?\DateTimeImmutable $endAt): self
    {
        $this->endAt = $endAt;

        return $this;
    }

    public function getServerCount(): int
    {
        return $this->phaseServers->count();
    }

    public function getMaxPlayersPerServer(): int
    {
        return 32;
    }

    public function getTotalCapacity(): int
    {
        $total = 0;
        foreach ($this->phaseServers as $phaseServer) {
            $total += $phaseServer->getServer()?->getMaxPlayers() ?? $this->getMaxPlayersPerServer();
        }

        return $total;
    }

    /**
     * @return Collection<int, PhaseResult>
     */
    public function getResults(): Collection
    {
        return $this->results;
    }

    public function isPlayable(): bool
    {
        return $this->type?->isPlayable() ?? false;
    }

    /**
     * @return Collection<int, PhaseServer>
     */
    public function getPhaseServers(): Collection
    {
        return $this->phaseServers;
    }

    public function addPhaseServer(PhaseServer $phaseServer): self
    {
        if (!$this->phaseServers->contains($phaseServer)) {
            $this->phaseServers->add($phaseServer);
            $phaseServer->setPhase($this);
        }

        return $this;
    }

    public function removePhaseServer(PhaseServer $phaseServer): self
    {
        if ($this->phaseServers->removeElement($phaseServer)) {
            if ($phaseServer->getPhase() === $this) {
                $phaseServer->setPhase(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return sprintf('%s - %s', $this->round?->getName() ?? '', $this->type?->getLabel() ?? '');
    }
}
