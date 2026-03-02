<?php

declare(strict_types=1);

namespace App\Domain\Team\Entity;

use App\Domain\Player\Entity\Player;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity]
#[ORM\Table(name: 'team_membership')]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false)]
class TeamMembership
{
    use TimestampableEntity;
    use SoftDeleteableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Player::class, inversedBy: 'memberships')]
    #[ORM\JoinColumn(nullable: false)]
    private Player $player;

    #[ORM\ManyToOne(targetEntity: Team::class, inversedBy: 'memberships')]
    #[ORM\JoinColumn(nullable: false)]
    private Team $team;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $joinedAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $leftAt = null;

    public function __construct(Player $player, Team $team)
    {
        $this->player = $player;
        $this->team = $team;
        $this->joinedAt = new \DateTimeImmutable();
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

    public function getJoinedAt(): \DateTimeImmutable
    {
        return $this->joinedAt;
    }

    public function getLeftAt(): ?\DateTimeImmutable
    {
        return $this->leftAt;
    }

    public function leave(): self
    {
        $this->leftAt = new \DateTimeImmutable();

        return $this;
    }

    public function isActive(): bool
    {
        return !$this->leftAt instanceof \DateTimeImmutable;
    }
}
