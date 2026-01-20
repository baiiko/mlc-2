<?php

declare(strict_types=1);

namespace App\Application\Championship\Service;

use App\Application\Championship\DTO\GbxMapDataDTO;

class GbxParserService
{
    private const GBX_MAGIC_HEADER = "GBX\x06\x00";
    private const CHALLENGE_TYPES = ['00300024', '00300403'];

    public function parseFile(string $filePath): ?GbxMapDataDTO
    {
        if (!file_exists($filePath)) {
            return null;
        }

        $handle = @fopen($filePath, 'rb');
        if (!$handle) {
            return null;
        }

        try {
            return $this->parseHandle($handle);
        } finally {
            fclose($handle);
        }
    }

    public function parseContent(string $content): ?GbxMapDataDTO
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'gbx_');
        if (!$tempFile) {
            return null;
        }

        try {
            file_put_contents($tempFile, $content);
            return $this->parseFile($tempFile);
        } finally {
            @unlink($tempFile);
        }
    }

    private function parseHandle($handle): ?GbxMapDataDTO
    {
        // Check for magic GBX header
        fseek($handle, 0x00, SEEK_SET);
        $data = fread($handle, 5);
        if ($data !== self::GBX_MAGIC_HEADER) {
            return null;
        }

        // Skip "BUCR" | "BUCE"
        fseek($handle, 0x04, SEEK_CUR);

        // Get GBX type & check for Challenge
        $data = fread($handle, 4);
        $r = unpack('Ngbxtype', $data);
        $type = sprintf('%08X', $r['gbxtype']);
        if (!in_array($type, self::CHALLENGE_TYPES, true)) {
            return null;
        }

        // Get GBX version: 2/3 = TM/TMPowerUp, 4 = TMO/TMS/TMN, 5 = TMU/TMF
        fseek($handle, 0x04, SEEK_CUR);
        $data = fread($handle, 4);
        $r = unpack('Vversion', $data);
        $version = $r['version'];

        if ($version < 2 || $version > 5) {
            return null;
        }

        // Get Index table
        $len = [];
        for ($i = 1; $i <= $version; $i++) {
            $data = fread($handle, 8);
            $r = unpack('Nmark' . $i . '/Vlen' . $i, $data);
            $len[$i] = $r['len' . $i];
        }

        if ($version === 5) {
            $len[4] &= 0x7FFFFFFF;
            $len[5] &= 0x7FFFFFFF;
        }

        // Get count of Times/info entries
        $data = fread($handle, 1);
        $count = ord($data);

        // Read times
        fseek($handle, 0x04, SEEK_CUR);
        $data = fread($handle, 4);
        $r = unpack('Vbronze', $data);
        $bronzeTime = $r['bronze'];

        $data = fread($handle, 4);
        $r = unpack('Vsilver', $data);
        $silverTime = $r['silver'];

        $data = fread($handle, 4);
        $r = unpack('Vgold', $data);
        $goldTime = $r['gold'];

        $data = fread($handle, 4);
        $r = unpack('Vauthor', $data);
        $authorTime = $r['author'];

        // Skip remaining times/info entries
        if ($version >= 3) {
            fseek($handle, 0x04, SEEK_CUR); // coppers
        }
        if ($count >= 6) {
            fseek($handle, 0x08, SEEK_CUR); // multi + type
            if ($count >= 9) {
                fseek($handle, 0x04, SEEK_CUR);
            }
            if ($count >= 10) {
                fseek($handle, 0x04, SEEK_CUR);
            }
            if ($count >= 11) {
                fseek($handle, 0x04, SEEK_CUR);
            }
        }

        // Skip version block
        fseek($handle, 0x04, SEEK_CUR);
        fseek($handle, 0x05, SEEK_CUR);

        // Read strings
        $uid = $this->readGbxString($handle);
        fseek($handle, 0x04, SEEK_CUR);
        $envir = $this->readGbxString($handle);
        fseek($handle, 0x04, SEEK_CUR);
        $author = $this->readGbxString($handle);
        $name = $this->readGbxString($handle);
        fseek($handle, 0x01, SEEK_CUR);

        $mood = null;
        if ($version >= 3) {
            fseek($handle, 0x04, SEEK_CUR);
            $this->readGbxString($handle); // password
        }
        if ($version >= 4 && $count >= 8) {
            fseek($handle, 0x04, SEEK_CUR);
            $mood = $this->readGbxString($handle);
        }

        // Clean up map name (remove TM formatting codes if needed)
        $cleanName = $this->cleanMapName($name);

        // Try to extract thumbnail
        $thumbnail = $this->extractThumbnail($handle);

        return new GbxMapDataDTO(
            uid: $uid ?: null,
            name: $cleanName ?: null,
            author: $author ?: null,
            environment: $envir ?: null,
            mood: $mood ?: null,
            authorTime: $authorTime > 0 ? $authorTime : null,
            goldTime: $goldTime > 0 ? $goldTime : null,
            silverTime: $silverTime > 0 ? $silverTime : null,
            bronzeTime: $bronzeTime > 0 ? $bronzeTime : null,
            thumbnail: $thumbnail
        );
    }

    private function extractThumbnail($handle): ?string
    {
        // Read entire file content to search for JPEG
        $currentPos = ftell($handle);
        fseek($handle, 0, SEEK_END);
        $fileSize = ftell($handle);
        fseek($handle, 0, SEEK_SET);
        $content = fread($handle, $fileSize);
        fseek($handle, $currentPos, SEEK_SET);

        if (!$content) {
            return null;
        }

        // Look for JPEG markers (FFD8 start, FFD9 end)
        $jpegStart = strpos($content, "\xFF\xD8\xFF");
        if ($jpegStart === false) {
            return null;
        }

        $jpegEnd = strpos($content, "\xFF\xD9", $jpegStart);
        if ($jpegEnd === false) {
            return null;
        }

        // Extract JPEG data (include the end marker)
        $jpegData = substr($content, $jpegStart, $jpegEnd - $jpegStart + 2);

        // Return as base64 encoded data
        return base64_encode($jpegData);
    }

    private function readGbxString($handle): ?string
    {
        $data = fread($handle, 4);
        $result = unpack('Vlen', $data);
        $len = $result['len'];

        if ($len <= 0 || $len >= 0x10000) {
            return null;
        }

        $data = fread($handle, $len);
        return $data ?: null;
    }

    private function cleanMapName(string $name): string
    {
        // Remove TM color and formatting codes like $fff, $o, $s, $i, etc.
        return preg_replace('/\$[0-9a-fA-F]{3}|\$[lhpognitsw]/i', '', $name) ?? $name;
    }
}
