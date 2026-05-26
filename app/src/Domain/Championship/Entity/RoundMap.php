<?php

declare(strict_types=1);

namespace App\Domain\Championship\Entity;

use App\Domain\Championship\Validator\UniqueSurpriseMap;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity]
#[ORM\Table(name: 'round_map')]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false)]
#[UniqueSurpriseMap]
class RoundMap
{
    use TimestampableEntity;
    use SoftDeleteableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Round::class, inversedBy: 'maps')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Round $round = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $uid = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $author = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $environment = null;

    #[ORM\Column(nullable: true)]
    private ?int $authorTime = null;

    #[ORM\Column(nullable: true)]
    private ?int $goldTime = null;

    #[ORM\Column(nullable: true)]
    private ?int $silverTime = null;

    #[ORM\Column(nullable: true)]
    private ?int $bronzeTime = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $isSurprise = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $thumbnailPath = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $karma = null;

    /** Virtual property for file upload (not persisted) */
    private mixed $gbxFile = null;

    public function __construct(?Round $round = null, ?string $name = null, ?string $uid = null)
    {
        $this->round = $round;
        $this->name = $name;
        $this->uid = $uid;
    }

    public function __toString(): string
    {
        $suffix = $this->isSurprise ? ' (Surprise)' : '';

        return ($this->name ?? '') . $suffix;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRound(): ?Round
    {
        return $this->round;
    }

    public function setRound(?Round $round): self
    {
        $this->round = $round;

        return $this;
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

    public function getUid(): ?string
    {
        return $this->uid;
    }

    public function setUid(?string $uid): self
    {
        $this->uid = $uid;

        return $this;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function setAuthor(?string $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function getEnvironment(): ?string
    {
        return $this->environment;
    }

    public function setEnvironment(?string $environment): self
    {
        $this->environment = $environment;

        return $this;
    }

    public function getAuthorTime(): ?int
    {
        return $this->authorTime;
    }

    public function setAuthorTime(?int $authorTime): self
    {
        $this->authorTime = $authorTime;

        return $this;
    }

    public function getGoldTime(): ?int
    {
        return $this->goldTime;
    }

    public function setGoldTime(?int $goldTime): self
    {
        $this->goldTime = $goldTime;

        return $this;
    }

    public function getSilverTime(): ?int
    {
        return $this->silverTime;
    }

    public function setSilverTime(?int $silverTime): self
    {
        $this->silverTime = $silverTime;

        return $this;
    }

    public function getBronzeTime(): ?int
    {
        return $this->bronzeTime;
    }

    public function setBronzeTime(?int $bronzeTime): self
    {
        $this->bronzeTime = $bronzeTime;

        return $this;
    }

    public function formatAuthorTime(): ?string
    {
        return $this->formatTime($this->authorTime);
    }

    public function isSurprise(): bool
    {
        return $this->isSurprise;
    }

    public function setIsSurprise(bool $isSurprise): self
    {
        $this->isSurprise = $isSurprise;

        return $this;
    }

    public function getThumbnailPath(): ?string
    {
        return $this->thumbnailPath;
    }

    public function setThumbnailPath(?string $thumbnailPath): self
    {
        $this->thumbnailPath = $thumbnailPath;

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getKarma(): ?array
    {
        return $this->karma;
    }

    /**
     * @param array<string, mixed>|null $karma
     */
    public function setKarma(?array $karma): self
    {
        $this->karma = $karma;

        return $this;
    }

    /**
     * @return array{total: int, positive: int, negative: int, percentage: int}|null
     */
    public function getKarmaStats(): ?array
    {
        if ($this->karma === null || $this->karma === []) {
            return null;
        }

        $positive = 0;
        $negative = 0;

        foreach ($this->karma as $vote) {
            if ((int) $vote > 0) {
                ++$positive;
            } elseif ((int) $vote < 0) {
                ++$negative;
            }
        }

        $total = $positive + $negative;

        if ($total === 0) {
            return null;
        }

        return [
            'total' => $total,
            'positive' => $positive,
            'negative' => $negative,
            'percentage' => (int) round($positive * 100 / $total),
        ];
    }

    public function getGbxFile(): mixed
    {
        return $this->gbxFile;
    }

    public function setGbxFile(mixed $gbxFile): self
    {
        $this->gbxFile = $gbxFile;

        return $this;
    }

    private function formatTime(?int $milliseconds): ?string
    {
        if ($milliseconds === null || $milliseconds <= 0) {
            return null;
        }

        $minutes = (int) floor($milliseconds / 60000);
        $seconds = (int) floor(($milliseconds % 60000) / 1000);
        $ms = $milliseconds % 1000;

        if ($minutes > 0) {
            return \sprintf('%d:%02d.%03d', $minutes, $seconds, $ms);
        }

        return \sprintf('%d.%03d', $seconds, $ms);
    }
}
