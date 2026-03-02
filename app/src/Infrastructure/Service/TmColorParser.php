<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

/**
 * Parse les codes couleur TrackMania et les convertit en HTML.
 *
 * Codes supportés :
 * - $xxx : couleur (ex: $f00 = rouge)
 * - $o, $w : gras (wide/bold)
 * - $i : italique
 * - $n : narrow (condensé)
 * - $t : majuscules (caps)
 * - $s : ombre (ignoré en HTML)
 * - $u : souligné
 * - $g : reset couleur
 * - $z : reset tous les styles
 * - $$ : caractère $ littéral
 */
final class TmColorParser
{
    /**
     * Convertit une chaîne avec codes TM en HTML.
     */
    public static function toHtml(string $text): string
    {
        $text = str_replace('$$', "\x00DOLLAR\x00", $text);

        $color = null;
        $bold = false;
        $italic = false;
        $narrow = false;
        $caps = false;
        $underline = false;

        $chunks = explode('$', $text);
        $result = [htmlspecialchars($chunks[0])];

        for ($i = 1; $i < count($chunks); $i++) {
            $chunk = $chunks[$i];
            $match = '';

            // Couleur 6 ou 3 caractères (priorité à 6)
            if (preg_match('/^([0-9a-f]{3})/i', $chunk, $matches)) {
                $match = $matches[1];
                $color = self::normalizeColor($match);
            }
            // Gras (wide/bold)
            elseif (preg_match('/^[owOW]/', $chunk, $matches)) {
                $match = $matches[0];
                $bold = true;
                $narrow = false;
            }
            // Italique
            elseif (preg_match('/^[iI]/', $chunk, $matches)) {
                $match = $matches[0];
                $italic = true;
            }
            // Narrow
            elseif (preg_match('/^[nN]/', $chunk, $matches)) {
                $match = $matches[0];
                $narrow = true;
                $bold = false;
            }
            // Caps
            elseif (preg_match('/^[tT]/', $chunk, $matches)) {
                $match = $matches[0];
                $caps = true;
            }
            // Souligné
            elseif (preg_match('/^[uU]/', $chunk, $matches)) {
                $match = $matches[0];
                $underline = true;
            }
            // Shadow (ignoré)
            elseif (preg_match('/^[sS]/', $chunk, $matches)) {
                $match = $matches[0];
            }
            // Medium (reset width)
            elseif (preg_match('/^[mM]/', $chunk, $matches)) {
                $match = $matches[0];
                $bold = false;
                $narrow = false;
            }
            // Reset couleur
            elseif (preg_match('/^[gG]/', $chunk, $matches)) {
                $match = $matches[0];
                $color = null;
            }
            // Reset tout
            elseif (preg_match('/^[zZ]/', $chunk, $matches)) {
                $match = $matches[0];
                $color = null;
                $bold = false;
                $italic = false;
                $narrow = false;
                $caps = false;
                $underline = false;
            }
            // Liens (ignorés, on garde juste le texte)
            elseif (preg_match('/^[lLhH]/', $chunk, $matches)) {
                continue;
            }

            $content = substr($chunk, strlen($match));
            if ($caps) {
                $content = strtoupper($content);
            }
            $content = htmlspecialchars($content);

            if ($content !== '') {
                $result[] = self::wrapWithStyles($content, $color, $bold, $italic, $narrow, $underline);
            }
        }

        $html = implode('', $result);
        return str_replace("\x00DOLLAR\x00", '$', $html);
    }

    /**
     * Supprime tous les codes TM et retourne le texte brut.
     */
    public static function stripColors(string $text): string
    {
        $text = str_replace('$$', "\x00DOLLAR\x00", $text);

        // Supprime les codes couleur (exactement 3 ou 6 caractères hex)
        $text = preg_replace('/\$([0-9a-f]{3})/i', '', $text);
        $text = preg_replace('/\$[iwonstmgzlhau]/i', '', $text);

        return str_replace("\x00DOLLAR\x00", '$', $text);
    }

    /**
     * Normalise une couleur TM en code hex 6 caractères.
     */
    private static function normalizeColor(string $color): string
    {
        $color = strtolower($color);

        if (strlen($color) === 3) {
            return $color[0] . $color[0] . $color[1] . $color[1] . $color[2] . $color[2];
        }

        if (strlen($color) < 6) {
            $color = str_pad($color, 6, '8');
        }

        return substr($color, 0, 6);
    }

    /**
     * Enveloppe le contenu dans un span avec les styles appropriés.
     */
    private static function wrapWithStyles(
        string $content,
        ?string $color,
        bool $bold,
        bool $italic,
        bool $narrow,
        bool $underline
    ): string {
        $styles = [];

        if ($color !== null) {
            $styles[] = 'color:#' . $color;
        }
        if ($bold) {
            $styles[] = 'font-weight:bold';
        }
        if ($italic) {
            $styles[] = 'font-style:italic';
        }
        if ($narrow) {
            $styles[] = 'letter-spacing:-0.1em';
            $styles[] = 'font-size:smaller';
        }
        if ($underline) {
            $styles[] = 'text-decoration:underline';
        }

        if (empty($styles)) {
            return $content;
        }

        return '<span style="' . implode(';', $styles) . '">' . $content . '</span>';
    }
}
