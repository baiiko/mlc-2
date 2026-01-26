<?php

declare(strict_types=1);

namespace App\Domain\Championship\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity]
#[ORM\Table(name: 'map_record')]
#[ORM\UniqueConstraint(name: 'unique_map_record', columns: ['map_uid', 'player_login', 'laps'])]
class MapRecord
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private string $mapUid;

    #[ORM\Column(length: 100)]
    private string $playerLogin;

    #[ORM\Column]
    private int $laps;

    #[ORM\Column]
    private int $time;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $player = null;

    /** @var array<int, int> */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $checkpoints = null;

    public function __construct(string $mapUid = '', string $playerLogin = '', int $laps = 1, int $time = 0)
    {
        $this->mapUid = $mapUid;
        $this->playerLogin = $playerLogin;
        $this->laps = $laps;
        $this->time = $time;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMapUid(): string
    {
        return $this->mapUid;
    }

    public function setMapUid(string $mapUid): self
    {
        $this->mapUid = $mapUid;

        return $this;
    }

    public function getPlayerLogin(): string
    {
        return $this->playerLogin;
    }

    public function setPlayerLogin(string $playerLogin): self
    {
        $this->playerLogin = $playerLogin;

        return $this;
    }

    public function getLaps(): int
    {
        return $this->laps;
    }

    public function setLaps(int $laps): self
    {
        $this->laps = $laps;

        return $this;
    }

    public function getTime(): int
    {
        return $this->time;
    }

    public function setTime(int $time): self
    {
        $this->time = $time;

        return $this;
    }

    /**
     * @return array<int, int>|null
     */
    public function getCheckpoints(): ?array
    {
        return $this->checkpoints;
    }

    /**
     * @param array<int, int>|null $checkpoints
     */
    public function setCheckpoints(?array $checkpoints): self
    {
        $this->checkpoints = $checkpoints;

        return $this;
    }

    public function getPlayer(): ?string
    {
        return $this->player;
    }

    public function setPlayer(?string $player): self
    {
        $this->player = $player;

        return $this;
    }

    public function formatTime(): ?string
    {
        if ($this->time <= 0) {
            return null;
        }

        $minutes = (int) floor($this->time / 60000);
        $seconds = (int) floor(($this->time % 60000) / 1000);
        $ms = $this->time % 1000;

        if ($minutes > 0) {
            return sprintf('%d:%02d.%03d', $minutes, $seconds, $ms);
        }

        return sprintf('%d.%03d', $seconds, $ms);
    }

    public function getLapsLabel(): string
    {
        return match ($this->laps) {
            1 => '1 tour',
            5 => '5 tours',
            10 => '10 tours',
            default => $this->laps . ' tours',
        };
    }

    public function __toString(): string
    {
        return sprintf('%s - %s (%s)', $this->getLapsLabel(), $this->formatTime() ?? '-', $this->playerLogin);
    }
}
