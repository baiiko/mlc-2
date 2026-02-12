<?php

declare(strict_types=1);

namespace App\Domain\Championship\Entity;

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

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $adminLogin = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $password = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $relayLogin = null;

    #[ORM\Column(options: ['default' => 32])]
    private int $maxPlayers = 32;

    #[ORM\Column(options: ['default' => true])]
    private bool $isActive = true;

    /** Virtual property for password update (not persisted) */
    private ?string $plainPassword = null;

    public function __construct(?string $name = null, ?string $login = null)
    {
        $this->name = $name;
        $this->login = $login;
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

    public function getAdminLogin(): ?string
    {
        return $this->adminLogin;
    }

    public function setAdminLogin(?string $adminLogin): self
    {
        $this->adminLogin = $adminLogin;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword): self
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    public function getRelayLogin(): ?string
    {
        return $this->relayLogin;
    }

    public function setRelayLogin(?string $relayLogin): self
    {
        $this->relayLogin = $relayLogin;

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

    public function getActiveLabel(): string
    {
        return $this->isActive ? 'Oui' : 'Non';
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }
}
