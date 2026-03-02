<?php

declare(strict_types=1);

namespace App\Application\Championship\DTO;

final readonly class GbxMapDataDTO
{
    public function __construct(
        public ?string $uid,
        public ?string $name,
        public ?string $author,
        public ?string $environment,
        public ?string $mood,
        public ?int $authorTime,
        public ?int $goldTime,
        public ?int $silverTime,
        public ?int $bronzeTime,
        public ?string $thumbnail = null,
    ) {
    }

    public function formatAuthorTime(): ?string
    {
        return $this->formatTime($this->authorTime);
    }

    public function formatGoldTime(): ?string
    {
        return $this->formatTime($this->goldTime);
    }

    public function formatSilverTime(): ?string
    {
        return $this->formatTime($this->silverTime);
    }

    public function formatBronzeTime(): ?string
    {
        return $this->formatTime($this->bronzeTime);
    }

    private function formatTime(?int $milliseconds): ?string
    {
        if ($milliseconds === null || $milliseconds <= 0) {
            return null;
        }

        $minutes = floor($milliseconds / 60000);
        $seconds = floor(($milliseconds % 60000) / 1000);
        $ms = $milliseconds % 1000;

        if ($minutes > 0) {
            return \sprintf('%d:%02d.%03d', $minutes, $seconds, $ms);
        }

        return \sprintf('%d.%03d', $seconds, $ms);
    }
}
