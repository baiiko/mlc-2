<?php

declare(strict_types=1);

namespace App\Domain\Championship\Entity;

use App\Domain\Player\Entity\Player;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity]
#[ORM\Table(name: 'phase_result')]
#[ORM\UniqueConstraint(name: 'unique_phase_player', columns: ['phase_id', 'player_id'])]
class PhaseResult
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Phase::class, inversedBy: 'results')]
    #[ORM\JoinColumn(nullable: false)]
    private Phase $phase;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Player $player;

    #[ORM\ManyToOne(targetEntity: RoundRegistration::class)]
    #[ORM\JoinColumn(nullable: false)]
    private RoundRegistration $registration;

    #[ORM\Column]
    private int $time;

    #[ORM\Column]
    private int $position;

    #[ORM\Column(options: ['default' => false])]
    private bool $isQualified = false;

    #[ORM\Column(length: 20, nullable: true, enumType: PhaseType::class)]
    private ?PhaseType $qualifiedTo = null;

    #[ORM\Column(nullable: true)]
    private ?int $serverNumber = null;

    public function __construct(
        Phase $phase,
        Player $player,
        RoundRegistration $registration,
        int $time,
        int $position,
    ) {
        $this->phase = $phase;
        $this->player = $player;
        $this->registration = $registration;
        $this->time = $time;
        $this->position = $position;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPhase(): Phase
    {
        return $this->phase;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getRegistration(): RoundRegistration
    {
        return $this->registration;
    }

    public function getTime(): int
    {
        return $this->time;
    }

    public function setTime(int $time): self
    {
        $this->time = $time;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function isQualified(): bool
    {
        return $this->isQualified;
    }

    public function setIsQualified(bool $isQualified): self
    {
        $this->isQualified = $isQualified;

        return $this;
    }

    public function getQualifiedTo(): ?PhaseType
    {
        return $this->qualifiedTo;
    }

    public function setQualifiedTo(?PhaseType $qualifiedTo): self
    {
        $this->qualifiedTo = $qualifiedTo;

        return $this;
    }

    public function getServerNumber(): ?int
    {
        return $this->serverNumber;
    }

    public function setServerNumber(?int $serverNumber): self
    {
        $this->serverNumber = $serverNumber;

        return $this;
    }

    public function getFormattedTime(): string
    {
        $milliseconds = $this->time;
        $minutes = (int) floor($milliseconds / 60000);
        $seconds = (int) floor(($milliseconds % 60000) / 1000);
        $ms = $milliseconds % 1000;

        return sprintf('%d:%02d.%03d', $minutes, $seconds, $ms);
    }
}
