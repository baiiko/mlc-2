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
#[ORM\Table(name: 'round')]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false)]
class Round
{
    use TimestampableEntity;
    use SoftDeleteableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Season::class, inversedBy: 'rounds')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Season $season = null;

    #[ORM\Column]
    private ?int $number = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $isActive = false;

    #[ORM\Column(options: ['default' => 0])]
    private int $qualifyToFinalCount = 0;

    #[ORM\Column(options: ['default' => 0])]
    private int $qualifyToSemiCount = 0;

    #[ORM\Column(options: ['default' => 0])]
    private int $qualifyFromSemiCount = 0;

    /** @var Collection<int, Phase> */
    #[ORM\OneToMany(targetEntity: Phase::class, mappedBy: 'round', cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['startAt' => 'ASC'])]
    private Collection $phases;

    /** @var Collection<int, RoundRegistration> */
    #[ORM\OneToMany(targetEntity: RoundRegistration::class, mappedBy: 'round', cascade: ['persist', 'remove'])]
    private Collection $registrations;

    /** @var Collection<int, RoundMap> */
    #[ORM\OneToMany(targetEntity: RoundMap::class, mappedBy: 'round', cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['isSurprise' => 'ASC', 'name' => 'ASC'])]
    private Collection $maps;

    /** Virtual field for phase generation (not persisted) */
    private ?\DateTimeInterface $startDate = null;

    public function __construct(
        ?Season $season = null,
        ?int $number = null,
        ?string $name = null,
    ) {
        $this->season = $season;
        $this->number = $number;
        $this->name = $name;
        $this->phases = new ArrayCollection();
        $this->registrations = new ArrayCollection();
        $this->maps = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSeason(): ?Season
    {
        return $this->season;
    }

    public function setSeason(Season $season): self
    {
        $this->season = $season;

        return $this;
    }

    public function getNumber(): ?int
    {
        return $this->number;
    }

    public function setNumber(int $number): self
    {
        $this->number = $number;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getRegistrationPhase(): ?Phase
    {
        return $this->getPhaseByType(PhaseType::Registration);
    }

    public function getRegistrationStartAt(): ?\DateTimeImmutable
    {
        return $this->getRegistrationPhase()?->getStartAt();
    }

    public function getRegistrationEndAt(): ?\DateTimeImmutable
    {
        return $this->getRegistrationPhase()?->getEndAt();
    }

    public function isRegistrationOpen(): bool
    {
        $phase = $this->getRegistrationPhase();
        if ($phase === null) {
            return false;
        }

        $startAt = $phase->getStartAt();
        $endAt = $phase->getEndAt();

        if ($startAt === null || $endAt === null) {
            return false;
        }

        $now = new \DateTimeImmutable();

        return $startAt <= $now && $now <= $endAt;
    }

    public function getQualifyToFinalCount(): int
    {
        return $this->qualifyToFinalCount;
    }

    public function setQualifyToFinalCount(int $qualifyToFinalCount): self
    {
        $this->qualifyToFinalCount = $qualifyToFinalCount;

        return $this;
    }

    public function getQualifyToSemiCount(): int
    {
        return $this->qualifyToSemiCount;
    }

    public function setQualifyToSemiCount(int $qualifyToSemiCount): self
    {
        $this->qualifyToSemiCount = $qualifyToSemiCount;

        return $this;
    }

    public function getQualifyFromSemiCount(): int
    {
        return $this->qualifyFromSemiCount;
    }

    public function setQualifyFromSemiCount(int $qualifyFromSemiCount): self
    {
        $this->qualifyFromSemiCount = $qualifyFromSemiCount;

        return $this;
    }

    /**
     * @return Collection<int, Phase>
     */
    public function getPhases(): Collection
    {
        return $this->phases;
    }

    public function addPhase(Phase $phase): self
    {
        if (!$this->phases->contains($phase)) {
            $this->phases->add($phase);
        }

        return $this;
    }

    public function getPhaseByType(PhaseType $type): ?Phase
    {
        foreach ($this->phases as $phase) {
            if ($phase->getType() === $type) {
                return $phase;
            }
        }

        return null;
    }

    /**
     * @return Collection<int, RoundRegistration>
     */
    public function getRegistrations(): Collection
    {
        return $this->registrations;
    }

    public function getRegistrationsCount(): int
    {
        return $this->registrations->count();
    }

    /**
     * @return Collection<int, RoundMap>
     */
    public function getMaps(): Collection
    {
        return $this->maps;
    }

    public function addMap(RoundMap $map): self
    {
        if (!$this->maps->contains($map)) {
            $this->maps->add($map);
            $map->setRound($this);
        }

        return $this;
    }

    public function removeMap(RoundMap $map): self
    {
        if ($this->maps->removeElement($map)) {
            if ($map->getRound() === $this) {
                $map->setRound(null);
            }
        }

        return $this;
    }

    public function getMapsCount(): int
    {
        return $this->maps->count();
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeInterface $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function __toString(): string
    {
        return sprintf('%s - %s', $this->season?->getName() ?? '', $this->name ?? '');
    }
}
