<?php

declare(strict_types=1);

namespace App\Domain\Player\Entity;

use App\Domain\Team\Entity\Team;
use App\Domain\Team\Entity\TeamMembership;
use App\Infrastructure\Service\TmColorParser;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
#[ORM\Table(name: 'player')]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false)]
class Player implements UserInterface, PasswordAuthenticatedUserInterface
{
    use TimestampableEntity;
    use SoftDeleteableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    private string $login;

    #[ORM\Column(length: 255, unique: true)]
    private string $email;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $password = null;

    #[ORM\Column(length: 64, unique: true, nullable: true)]
    private ?string $activationToken = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $tokenExpiresAt = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $isActive = false;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $discord = null;

    #[ORM\Column(length: 50)]
    private string $pseudo;

    #[ORM\Column(options: ['default' => false])]
    private bool $newsletter = false;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(length: 64, unique: true, nullable: true)]
    private ?string $resetPasswordToken = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $resetPasswordTokenExpiresAt = null;

    /** @var Collection<int, TeamMembership> */
    #[ORM\OneToMany(targetEntity: TeamMembership::class, mappedBy: 'player')]
    private Collection $memberships;

    public function __construct(string $login, string $email, string $pseudo)
    {
        $this->login = $login;
        $this->email = $email;
        $this->pseudo = $pseudo;
        $this->memberships = new ArrayCollection();
        $this->generateActivationToken();
    }

    public function __toString(): string
    {
        return TmColorParser::stripColors($this->pseudo);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLogin(): string
    {
        return $this->login;
    }

    public function setLogin(string $login): self
    {
        $this->login = $login;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getActivationToken(): ?string
    {
        return $this->activationToken;
    }

    public function generateActivationToken(): self
    {
        $this->activationToken = bin2hex(random_bytes(32));
        $this->tokenExpiresAt = new \DateTimeImmutable('+24 hours');

        return $this;
    }

    public function clearActivationToken(): self
    {
        $this->activationToken = null;
        $this->tokenExpiresAt = null;

        return $this;
    }

    public function isTokenValid(): bool
    {
        return $this->activationToken !== null
            && $this->tokenExpiresAt instanceof \DateTimeImmutable
            && $this->tokenExpiresAt > new \DateTimeImmutable();
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

    public function activate(): self
    {
        $this->isActive = true;
        $this->clearActivationToken();

        return $this;
    }

    public function getDiscord(): ?string
    {
        return $this->discord;
    }

    public function setDiscord(?string $discord): self
    {
        $this->discord = $discord;

        return $this;
    }

    public function getPseudo(): string
    {
        return $this->pseudo;
    }

    public function setPseudo(string $pseudo): self
    {
        $this->pseudo = $pseudo;

        return $this;
    }

    public function getDisplayName(): string
    {
        return $this->pseudo;
    }

    public function hasNewsletter(): bool
    {
        return $this->newsletter;
    }

    public function setNewsletter(bool $newsletter): self
    {
        $this->newsletter = $newsletter;

        return $this;
    }

    // UserInterface methods
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_PLAYER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function addRole(string $role): self
    {
        if (!\in_array($role, $this->roles, true)) {
            $this->roles[] = $role;
        }

        return $this;
    }

    public function removeRole(string $role): self
    {
        $this->roles = array_filter($this->roles, fn ($r): bool => $r !== $role);

        return $this;
    }

    public function eraseCredentials(): void
    {
        // Nothing to erase
    }

    public function getUserIdentifier(): string
    {
        return $this->login;
    }

    public function getResetPasswordToken(): ?string
    {
        return $this->resetPasswordToken;
    }

    public function generateResetPasswordToken(): self
    {
        $this->resetPasswordToken = bin2hex(random_bytes(32));
        $this->resetPasswordTokenExpiresAt = new \DateTimeImmutable('+1 hour');

        return $this;
    }

    public function clearResetPasswordToken(): self
    {
        $this->resetPasswordToken = null;
        $this->resetPasswordTokenExpiresAt = null;

        return $this;
    }

    public function isResetPasswordTokenValid(): bool
    {
        return $this->resetPasswordToken !== null
            && $this->resetPasswordTokenExpiresAt instanceof \DateTimeImmutable
            && $this->resetPasswordTokenExpiresAt > new \DateTimeImmutable();
    }

    /**
     * @return Collection<int, TeamMembership>
     */
    public function getMemberships(): Collection
    {
        return $this->memberships;
    }

    public function getActiveMembership(): ?TeamMembership
    {
        foreach ($this->memberships as $membership) {
            if ($membership->isActive()) {
                return $membership;
            }
        }

        return null;
    }

    public function getTeam(): ?Team
    {
        $membership = $this->getActiveMembership();

        return $membership?->getTeam();
    }

    public function hasTeam(): bool
    {
        return $this->getActiveMembership() instanceof TeamMembership;
    }

    public function isTeamCreator(): bool
    {
        $team = $this->getTeam();

        return $team instanceof Team && $team->isCreator($this);
    }
}
