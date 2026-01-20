<?php

declare(strict_types=1);

namespace App\Domain\Championship\Entity;

use App\Domain\Player\Entity\Player;
use App\Domain\Team\Entity\Team;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;

#[ORM\Entity]
#[ORM\Table(name: 'round_registration')]
#[ORM\UniqueConstraint(name: 'unique_round_player', columns: ['round_id', 'player_id'])]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false)]
class RoundRegistration
{
    use SoftDeleteableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Round::class, inversedBy: 'registrations')]
    #[ORM\JoinColumn(nullable: false)]
    private Round $round;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Player $player;

    #[ORM\ManyToOne(targetEntity: Team::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Team $team = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $registeredAt;

    #[ORM\Column(options: ['default' => true])]
    private bool $availableSemiFinal1 = true;

    #[ORM\Column(options: ['default' => true])]
    private bool $availableSemiFinal2 = true;

    #[ORM\Column(options: ['default' => true])]
    private bool $availableFinal = true;

    public function __construct(
        Round $round,
        Player $player,
        ?Team $team = null,
        bool $availableSemiFinal1 = true,
        bool $availableSemiFinal2 = true,
        bool $availableFinal = true,
    ) {
        $this->round = $round;
        $this->player = $player;
        $this->team = $team;
        $this->registeredAt = new \DateTimeImmutable();
        $this->availableSemiFinal1 = $availableSemiFinal1;
        $this->availableSemiFinal2 = $availableSemiFinal2;
        $this->availableFinal = $availableFinal;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRound(): Round
    {
        return $this->round;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getTeam(): ?Team
    {
        return $this->team;
    }

    public function getRegisteredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function getPlayerPseudo(): ?string
    {
        return $this->player->getPseudo();
    }

    public function getTeamTag(): ?string
    {
        return $this->team?->getTag();
    }

    public function isAvailableSemiFinal1(): bool
    {
        return $this->availableSemiFinal1;
    }

    public function setAvailableSemiFinal1(bool $availableSemiFinal1): self
    {
        $this->availableSemiFinal1 = $availableSemiFinal1;

        return $this;
    }

    public function isAvailableSemiFinal2(): bool
    {
        return $this->availableSemiFinal2;
    }

    public function setAvailableSemiFinal2(bool $availableSemiFinal2): self
    {
        $this->availableSemiFinal2 = $availableSemiFinal2;

        return $this;
    }

    public function isAvailableFinal(): bool
    {
        return $this->availableFinal;
    }

    public function setAvailableFinal(bool $availableFinal): self
    {
        $this->availableFinal = $availableFinal;

        return $this;
    }
}
