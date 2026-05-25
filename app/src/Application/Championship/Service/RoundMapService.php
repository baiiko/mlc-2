<?php

declare(strict_types=1);

namespace App\Application\Championship\Service;

use App\Application\Championship\DTO\GbxMapDataDTO;
use App\Domain\Championship\Entity\RoundMap;
use App\Domain\Championship\Repository\MapRecordRepositoryInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class RoundMapService
{
    public function __construct(
        private readonly GbxParserService $gbxParser,
        private readonly MapRecordRepositoryInterface $mapRecordRepository,
        private readonly string $publicDir,
        private readonly string $matchSettingsPath,
    ) {
    }

    public function importFromGbxFile(RoundMap $map, UploadedFile $file): ?string
    {
        $data = $this->gbxParser->parseFile($file->getPathname());

        if (!$data instanceof GbxMapDataDTO) {
            return 'Impossible de parser le fichier GBX';
        }

        if ($data->uid) {
            $previousUid = $map->getUid();
            $roundId = $map->getRound()?->getId();

            if ($previousUid !== null && $previousUid !== $data->uid && $roundId !== null) {
                $this->mapRecordRepository->updateMapUidForRound($previousUid, $data->uid, $roundId);
            }

            $map->setUid($data->uid);
        }

        if ($data->name) {
            $map->setName($data->name);
        }

        if ($data->author) {
            $map->setAuthor($data->author);
        }

        if ($data->environment) {
            $map->setEnvironment($data->environment);
        }

        if ($data->authorTime) {
            $map->setAuthorTime($data->authorTime);
        }

        if ($data->goldTime) {
            $map->setGoldTime($data->goldTime);
        }

        if ($data->silverTime) {
            $map->setSilverTime($data->silverTime);
        }

        if ($data->bronzeTime) {
            $map->setBronzeTime($data->bronzeTime);
        }

        if ($data->thumbnail && $data->uid) {
            $thumbnailPath = $this->saveThumbnail($data->thumbnail, $data->uid);

            if ($thumbnailPath) {
                $map->setThumbnailPath($thumbnailPath);
            }
        }

        if ($data->uid) {
            $this->saveGbxFile($file, $map, $data->uid);
        }

        $map->setGbxFile(null);

        return null;
    }

    private function saveGbxFile(UploadedFile $file, RoundMap $map, string $uid): void
    {
        $mapsDir = \dirname(rtrim($this->matchSettingsPath, '/'));
        $round = $map->getRound();
        $season = $round?->getSeason();

        $relativeDir = ($season?->getId() ?? 'default') . '/' . ($round?->getName() ?? 'round');
        $destDir = $mapsDir . '/' . $relativeDir;

        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        $file->move($destDir, $uid . '.Challenge.Gbx');
    }

    private function saveThumbnail(string $base64Data, string $uid): ?string
    {
        $thumbnailDir = $this->publicDir . '/uploads/maps/thumbnails';

        if (!is_dir($thumbnailDir)) {
            mkdir($thumbnailDir, 0755, true);
        }

        $filename = $uid . '.jpg';
        $filepath = $thumbnailDir . '/' . $filename;

        $imageData = base64_decode($base64Data);

        if ($imageData === false) {
            return null;
        }

        if (file_put_contents($filepath, $imageData) === false) {
            return null;
        }

        return $filename;
    }
}
