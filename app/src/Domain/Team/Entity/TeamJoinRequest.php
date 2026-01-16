<?php

declare(strict_types=1);

namespace App\Domain\Team\Entity;

use App\Domain\Player\Entity\Player;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity]
#[ORM\Table(name: 'team_join_request')]
#[ORM\UniqueConstraint(name: 'unique_pending_request', columns: ['player_id', 'team_id', 'status'])]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false)]
class TeamJoinRequest
{
    use TimestampableEntity;
    use SoftDeleteableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Player $player;

    #[ORM\ManyToOne(targetEntity: Team::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Team $team;

    #[ORM\Column(length: 20, enumType: JoinRequestStatus::class)]
    private JoinRequestStatus $status = JoinRequestStatus::Pending;

    public function __construct(Player $player, Team $team)
    {
        $this->player = $player;
        $this->team = $team;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getTeam(): Team
    {
        return $this->team;
    }

    public function getStatus(): JoinRequestStatus
    {
        return $this->status;
    }

    public function isPending(): bool
    {
        return $this->status === JoinRequestStatus::Pending;
    }

    public function accept(): self
    {
        $this->status = JoinRequestStatus::Accepted;

        return $this;
    }

    public function reject(): self
    {
        $this->status = JoinRequestStatus::Rejected;

        return $this;
    }
}
