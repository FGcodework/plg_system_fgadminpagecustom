<?php

/**
 * @package     plg_system_fgadminpagecustom
 * @license     GNU General Public License version 2 or later
 *
 * Validates settings values coming from an imported JSON, key by key.
 */

declare(strict_types=1);

namespace FG\Plugin\System\AdminPageCustom\Helper;

\defined('_JEXEC') or die;

final class SettingsSanitizer
{
    private const COLORS  = ['brand_color', 'header_color', 'sidebar_color', 'page_bg'];
    private const MEDIA   = ['logo', 'logo_small'];
    private const SHADOWS = ['none', 'subtle', 'medium', 'strong'];

    /** @var array<string, array{0: int, 1: int}> */
    private const RANGES = [
        'logo_height' => [16, 120],
        'ctrl_radius' => [0, 40],
        'card_radius' => [0, 40],
    ];

    /**
     * Names of all settings an import may set.
     *
     * @return string[]
     */
    public static function keys(): array
    {
        return array_merge(
            self::COLORS,
            self::MEDIA,
            array_keys(self::RANGES),
            ['sidebar_follow', 'menu_open_first', 'btn_shadow', 'card_shadow']
        );
    }

    /**
     * Returns the valid values (normalised) and the names of the rejected keys.
     *
     * @param array<string, mixed> $input
     * @return array{values: array<string, string|int>, rejected: string[]}
     */
    public static function sanitize(array $input): array
    {
        $values   = [];
        $rejected = [];

        foreach ($input as $key => $value) {
            $key = (string) $key;

            if (!\in_array($key, self::keys(), true)) {
                continue;
            }

            $clean = self::cleanValue($key, $value);

            if ($clean === null) {
                $rejected[] = $key;
                continue;
            }

            $values[$key] = $clean;
        }

        return ['values' => $values, 'rejected' => $rejected];
    }

    /**
     * @param mixed $value
     * @return string|int|null null = invalid
     */
    private static function cleanValue(string $key, $value)
    {
        if (!\is_scalar($value) && $value !== null) {
            return null;
        }

        $text = trim((string) $value);

        if (\in_array($key, self::COLORS, true)) {
            return $text === '' ? '' : (ColorHelper::clean($text) ?: null);
        }

        if (\in_array($key, self::MEDIA, true)) {
            // A path or URL: no markup, quotes, backslashes or script schemes.
            if ($text === '') {
                return '';
            }

            if (\strlen($text) > 500 || preg_match('/[<>"\'\\\\\x00-\x1f]/', $text) === 1
                || preg_match('#^\s*(javascript|data|vbscript):#i', $text) === 1) {
                return null;
            }

            return $text;
        }

        if (isset(self::RANGES[$key])) {
            if ($text === '') {
                return '';
            }

            if (preg_match('/^\d{1,3}$/', $text) !== 1) {
                return null;
            }

            [$min, $max] = self::RANGES[$key];
            $number      = (int) $text;

            return $number >= $min && $number <= $max ? $number : null;
        }

        if ($key === 'sidebar_follow' || $key === 'menu_open_first') {
            return $text === '0' || $text === '1' ? (int) $text : null;
        }

        // btn_shadow / card_shadow
        return \in_array($text, self::SHADOWS, true) ? $text : null;
    }
}
