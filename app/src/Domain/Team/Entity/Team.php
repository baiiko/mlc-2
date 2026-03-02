<?php

declare(strict_types=1);

namespace App\Domain\Team\Entity;

use App\Domain\Player\Entity\Player;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity]
#[ORM\Table(name: 'team')]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false)]
class Team
{
    use TimestampableEntity;
    use SoftDeleteableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 10, unique: true)]
    private string $tag;

    #[ORM\Column(length: 50)]
    private string $fullName;

    /** @var Collection<int, TeamMembership> */
    #[ORM\OneToMany(targetEntity: TeamMembership::class, mappedBy: 'team')]
    private Collection $memberships;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Player $creator;

    public function __construct(string $tag, string $fullName, Player $creator)
    {
        $this->tag = $tag;
        $this->fullName = $fullName;
        $this->creator = $creator;
        $this->memberships = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->tag;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTag(): string
    {
        return $this->tag;
    }

    public function setTag(string $tag): self
    {
        $this->tag = $tag;

        return $this;
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function setFullName(string $fullName): self
    {
        $this->fullName = $fullName;

        return $this;
    }

    /**
     * @return Collection<int, TeamMembership>
     */
    public function getMemberships(): Collection
    {
        return $this->memberships;
    }

    /**
     * @return Collection<int, TeamMembership>
     */
    public function getActiveMemberships(): Collection
    {
        return $this->memberships->filter(fn (TeamMembership $m): bool => $m->isActive());
    }

    /**
     * @return array<Player>
     */
    public function getActiveMembers(): array
    {
        return $this->getActiveMemberships()
            ->map(fn (TeamMembership $m): Player => $m->getPlayer())
            ->toArray();
    }

    public function addMembership(TeamMembership $membership): self
    {
        if (!$this->memberships->contains($membership)) {
            $this->memberships->add($membership);
        }

        return $this;
    }

    public function getCreator(): Player
    {
        return $this->creator;
    }

    public function setCreator(Player $creator): self
    {
        $this->creator = $creator;

        return $this;
    }

    public function isCreator(Player $player): bool
    {
        return $this->creator->getId() === $player->getId();
    }

    public function getCreatorName(): string
    {
        return $this->creator->getPseudo();
    }

    public function getActiveMembersCount(): string
    {
        return (string) \count($this->getActiveMembers());
    }
}
