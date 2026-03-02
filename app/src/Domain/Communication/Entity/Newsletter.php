<?php

declare(strict_types=1);

namespace App\Domain\Communication\Entity;

use App\Domain\Player\Entity\Player;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity]
#[ORM\Table(name: 'newsletter')]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false)]
class Newsletter
{
    use TimestampableEntity;
    use SoftDeleteableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $subject = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $content = null;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Player $sentBy = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $sentAt = null;

    #[ORM\Column(type: Types::INTEGER)]
    private int $recipientCount = 0;

    public function __toString(): string
    {
        return $this->subject ?? 'Nouvelle newsletter';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getSentBy(): ?Player
    {
        return $this->sentBy;
    }

    public function setSentBy(Player $sentBy): self
    {
        $this->sentBy = $sentBy;

        return $this;
    }

    public function getSentAt(): ?\DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function getRecipientCount(): int
    {
        return $this->recipientCount;
    }

    public function isSent(): bool
    {
        return $this->sentAt instanceof \DateTimeImmutable;
    }

    public function markAsSent(int $count): void
    {
        $this->sentAt = new \DateTimeImmutable();
        $this->recipientCount = $count;
    }
}
