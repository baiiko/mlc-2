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
#[ORM\Table(name: 'server')]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false)]
class Server
{
    use TimestampableEntity;
    use SoftDeleteableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column(length: 100, unique: true)]
    private ?string $login = null;

    #[ORM\Column(length: 45, nullable: true)]
    private ?string $ip = null;

    #[ORM\Column(nullable: true)]
    private ?int $port = null;

    #[ORM\Column(options: ['default' => 32])]
    private int $maxPlayers = 32;

    #[ORM\Column(options: ['default' => true])]
    private bool $isActive = true;

    /** @var Collection<int, PhaseServer> */
    #[ORM\OneToMany(targetEntity: PhaseServer::class, mappedBy: 'server')]
    private Collection $phaseServers;

    public function __construct(?string $name = null, ?string $login = null)
    {
        $this->name = $name;
        $this->login = $login;
        $this->phaseServers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getLogin(): ?string
    {
        return $this->login;
    }

    public function setLogin(?string $login): self
    {
        $this->login = $login;

        return $this;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(?string $ip): self
    {
        $this->ip = $ip;

        return $this;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function setPort(?int $port): self
    {
        $this->port = $port;

        return $this;
    }

    public function getMaxPlayers(): int
    {
        return $this->maxPlayers;
    }

    public function setMaxPlayers(int $maxPlayers): self
    {
        $this->maxPlayers = $maxPlayers;

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

    public function getConnectionString(): ?string
    {
        if ($this->ip && $this->port) {
            return sprintf('%s:%d', $this->ip, $this->port);
        }

        return null;
    }

    /**
     * @return Collection<int, PhaseServer>
     */
    public function getPhaseServers(): Collection
    {
        return $this->phaseServers;
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }
}
