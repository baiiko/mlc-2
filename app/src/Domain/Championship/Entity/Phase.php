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

    #[ORM\Column(nullable: true)]
    private ?int $laps = null;

    #[ORM\Column(nullable: true)]
    private ?int $timeLimit = null;

    #[ORM\Column(nullable: true)]
    private ?int $finishTimeout = null;

    #[ORM\Column(nullable: true)]
    private ?int $warmupDuration = null;

    /** @var array<array{position: int, login: string, pseudo: string, points: int, bonus: int, total: int, nbMaps: int, availableSemiFinal: bool, availableFinal: bool}>|null */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $ranking = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $rankingUpdatedAt = null;

    #[ORM\Column(nullable: true)]
    private ?int $groupNumber = null;

    /** @var array<string>|null Liste des logins des joueurs de cette phase */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $players = null;

    /** @var Collection<int, PhaseResult> */
    #[ORM\OneToMany(targetEntity: PhaseResult::class, mappedBy: 'phase', cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $results;

    public function __construct(?Round $round = null, ?PhaseType $type = null, ?\DateTimeImmutable $startAt = null)
    {
        $this->round = $round;
        $this->type = $type;
        $this->startAt = $startAt;
        $this->results = new ArrayCollection();
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

    public function getLaps(): ?int
    {
        return $this->laps;
    }

    public function setLaps(?int $laps): self
    {
        $this->laps = $laps;

        return $this;
    }

    public function getTimeLimit(): ?int
    {
        return $this->timeLimit;
    }

    public function setTimeLimit(?int $timeLimit): self
    {
        $this->timeLimit = $timeLimit;

        return $this;
    }

    public function getFinishTimeout(): ?int
    {
        return $this->finishTimeout;
    }

    public function setFinishTimeout(?int $finishTimeout): self
    {
        $this->finishTimeout = $finishTimeout;

        return $this;
    }

    public function getWarmupDuration(): ?int
    {
        return $this->warmupDuration;
    }

    public function setWarmupDuration(?int $warmupDuration): self
    {
        $this->warmupDuration = $warmupDuration;

        return $this;
    }

    public function getEffectiveLaps(): int
    {
        if ($this->laps !== null) {
            return $this->laps;
        }

        return match ($this->type) {
            PhaseType::Qualification => 5,
            PhaseType::SemiFinal => 3,
            PhaseType::Final => 10,
            default => 5,
        };
    }

    public function getEffectiveTimeLimit(): int
    {
        if ($this->timeLimit !== null) {
            return $this->timeLimit;
        }

        return match ($this->type) {
            PhaseType::Qualification => 210000,
            PhaseType::SemiFinal => 150000,
            PhaseType::Final => 360000,
            default => 210000,
        };
    }

    public function getEffectiveFinishTimeout(): int
    {
        if ($this->finishTimeout !== null) {
            return $this->finishTimeout;
        }

        return match ($this->type) {
            PhaseType::Qualification => 30000,
            PhaseType::SemiFinal => 30000,
            PhaseType::Final => 40000,
            default => 30000,
        };
    }

    public function getEffectiveWarmupDuration(): int
    {
        if ($this->warmupDuration !== null) {
            return $this->warmupDuration;
        }

        return match ($this->type) {
            PhaseType::Registration => 0,
            default => 1,
        };
    }

    /**
     * @return array<array{position: int, login: string, pseudo: string, points: int, bonus: int, total: int, nbMaps: int, availableSemiFinal: bool, availableFinal: bool}>|null
     */
    public function getRanking(): ?array
    {
        return $this->ranking;
    }

    /**
     * @param array<array{position: int, login: string, pseudo: string, points: int, bonus: int, total: int, nbMaps: int, availableSemiFinal: bool, availableFinal: bool}>|null $ranking
     */
    public function setRanking(?array $ranking): self
    {
        $this->ranking = $ranking;

        return $this;
    }

    public function getRankingUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->rankingUpdatedAt;
    }

    public function setRankingUpdatedAt(?\DateTimeImmutable $rankingUpdatedAt): self
    {
        $this->rankingUpdatedAt = $rankingUpdatedAt;

        return $this;
    }

    public function getGroupNumber(): ?int
    {
        return $this->groupNumber;
    }

    public function setGroupNumber(?int $groupNumber): self
    {
        $this->groupNumber = $groupNumber;

        return $this;
    }

    /**
     * @return array<string>|null
     */
    public function getPlayers(): ?array
    {
        return $this->players;
    }

    /**
     * @param array<string>|null $players
     */
    public function setPlayers(?array $players): self
    {
        $this->players = $players;

        return $this;
    }

    /**
     * Get ranking data for a specific player.
     *
     * @return array{position: int, login: string, pseudo: string, points: int, bonus: int, total: int, nbMaps: int, availableSemiFinal: bool, availableFinal: bool}|null
     */
    public function getPlayerRanking(string $login): ?array
    {
        if ($this->ranking === null) {
            return null;
        }

        foreach ($this->ranking as $entry) {
            if ($entry['login'] === $login) {
                return $entry;
            }
        }

        return null;
    }

    public function __toString(): string
    {
        return sprintf('%s - %s', $this->round?->getName() ?? '', $this->type?->getLabel() ?? '');
    }
}
