<?php

declare(strict_types=1);

namespace App\Domain\Championship\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity]
#[ORM\Table(name: 'phase_map_result')]
#[ORM\UniqueConstraint(name: 'unique_phase_map_winner', columns: ['map_uid', 'winner', 'phase_id'])]
class PhaseMapResult
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Phase::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Phase $phase;

    #[ORM\Column(length: 50)]
    private string $mapUid;

    #[ORM\Column(length: 100)]
    private string $winner;

    /** @var array<array{login: string, time: int, position: int}>|null */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $results = null;

    public function __construct(Phase $phase, string $mapUid, string $winner, ?array $results = null)
    {
        $this->phase = $phase;
        $this->mapUid = $mapUid;
        $this->winner = $winner;
        $this->results = $results;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPhase(): Phase
    {
        return $this->phase;
    }

    public function setPhase(Phase $phase): self
    {
        $this->phase = $phase;

        return $this;
    }

    public function getMapUid(): string
    {
        return $this->mapUid;
    }

    public function setMapUid(string $mapUid): self
    {
        $this->mapUid = $mapUid;

        return $this;
    }

    public function getWinner(): string
    {
        return $this->winner;
    }

    public function setWinner(string $winner): self
    {
        $this->winner = $winner;

        return $this;
    }

    /**
     * @return array<array{login: string, time: int, position: int}>|null
     */
    public function getResults(): ?array
    {
        return $this->results;
    }

    /**
     * @param array<array{login: string, time: int, position: int}>|null $results
     */
    public function setResults(?array $results): self
    {
        $this->results = $results;

        return $this;
    }
}
