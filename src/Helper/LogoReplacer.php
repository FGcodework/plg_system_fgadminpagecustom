<?php

/**
 * @package     plg_system_fgadminpagecustom
 * @license     GNU General Public License version 2 or later
 *
 * Swaps the logo images inside the page header of the rendered HTML.
 */

declare(strict_types=1);

namespace FG\Plugin\System\AdminPageCustom\Helper;

\defined('_JEXEC') or die;

final class LogoReplacer
{
    /**
     * Returns the body with the header logos replaced, or null when nothing changed.
     *
     * @param array{expanded: string, collapsed: string} $logos Absolute URLs ('' = leave as is)
     */
    public static function replace(string $body, array $logos): ?string
    {
        // Work only inside the page header, never on the rest of the page.
        if (preg_match('#<header\b[^>]*>.*?</header>#is', $body, $header, PREG_OFFSET_CAPTURE) !== 1) {
            return null;
        }

        [$block, $start] = $header[0];

        if (preg_match_all('#<img\b[^>]*>#i', $block, $images, PREG_OFFSET_CAPTURE) < 1) {
            return null;
        }

        // Logo images are recognised by "logo" in their class or file name;
        // a header without any such hint falls back to its first two images.
        $candidates = array_values(array_filter(
            $images[0],
            static fn (array $img): bool => preg_match('#logo#i', $img[0]) === 1
        ));

        if ($candidates === []) {
            $candidates = \array_slice($images[0], 0, 2);
        }

        $offset  = 0;
        $changed = false;

        foreach ($candidates as $index => [$tag, $pos]) {
            $small = preg_match('#collapsed|small|mini#i', $tag) === 1
                || (!preg_match('#expanded|large#i', $tag) && $index > 0);
            $url   = $logos[$small ? 'collapsed' : 'expanded'];

            if ($url === '') {
                continue;
            }

            $src = strtr(htmlspecialchars($url, ENT_QUOTES, 'UTF-8'), ['\\' => '\\\\', '$' => '\\$']);
            $new = preg_replace('#\s(?:srcset|sizes)=("[^"]*"|\'[^\']*\')#i', '', $tag) ?? $tag;
            $new = preg_match('#\ssrc=("[^"]*"|\'[^\']*\')#i', $new) === 1
                ? (preg_replace('#\ssrc=("[^"]*"|\'[^\']*\')#i', ' src="' . $src . '"', $new, 1) ?? $new)
                : (preg_replace('#^<img\b#i', '<img src="' . $src . '"', $new, 1) ?? $new);

            $block   = substr($block, 0, $pos + $offset) . $new . substr($block, $pos + $offset + \strlen($tag));
            $offset += \strlen($new) - \strlen($tag);
            $changed = true;
        }

        if ($changed) {
            return substr($body, 0, $start) . $block . substr($body, $start + \strlen($header[0][0]));
        }

        return null;
    }
}
