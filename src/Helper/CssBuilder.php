<?php

/**
 * @package     plg_system_fgadminpagecustom
 * @license     GNU General Public License version 2 or later
 *
 * Builds the inline CSS of the plugin from its parameters. No Joomla
 * dependency besides the Registry, so the output can be compared and tested.
 */

declare(strict_types=1);

namespace FG\Plugin\System\AdminPageCustom\Helper;

use Joomla\Registry\Registry;

\defined('_JEXEC') or die;

final class CssBuilder
{
    private Registry $params;

    public function __construct(Registry $params)
    {
        $this->params = $params;
    }

    /**
     * Builds the inline CSS from the plugin parameters ('' when nothing is set).
     */
    public function build(): string
    {
        $css = array_merge(
            $this->buildBrandCss(),
            $this->buildLogoCss(),
            $this->buildShapeCss()
        );

        return implode("\n", array_filter($css, static fn (string $rule): bool => $rule !== ''));
    }

    /**
     * Brand color: Atum exposes its colors as CSS custom properties, so
     * redefining them restyles the header, the sidebar highlights, the
     * primary buttons and the links without touching any component markup.
     * "html:root" outranks Atum's own ":root" declarations; the extra
     * selectors do the same for Atum's dark scheme.
     *
     * @return string[]
     */
    private function buildBrandCss(): array
    {
        $brand = ColorHelper::clean((string) $this->params->get('brand_color', ''));

        if ($brand === '') {
            return $this->buildSurfaceCss();
        }

        $text  = ColorHelper::contrastText($brand);
        $hover = ColorHelper::shade($brand, -0.15);

        // The header may have its own color; it defaults to the brand color.
        $header = ColorHelper::clean((string) $this->params->get('header_color', '')) ?: $brand;
        $hText  = ColorHelper::contrastText($header);
        $onDark = $hText === '#ffffff';

        // Header pill (frontend link, user menu): a translucent tint of the
        // text color, so it stays visible on any brand color.
        $pill      = $onDark ? 'rgba(255, 255, 255, 0.16)' : 'rgba(0, 0, 0, 0.10)';
        $pillHover = $onDark ? 'rgba(255, 255, 255, 0.26)' : 'rgba(0, 0, 0, 0.18)';

        $rgb = implode(', ', ColorHelper::toRgb($brand));

        $vars = [
            'header-bg'                         => $header,
            'header-logo-bg'                    => ColorHelper::shade($header, -0.2),
            'header-item-content-bg'            => $pill,
            'header-item-content-bg-hvr'        => $pillHover,
            'sidebar-toggle-bg'                 => $header,
            'sidebar-item-bg-hvr'               => $brand,
            'main-nav-item-title-hvr-bg'        => $brand,
            'main-nav-mm-active-bg'             => $brand,
            'main-nav-mm-active-quicktask-bg'   => $brand,
            'sidebar-item-color-hvr'            => $text,
            'primary'                           => $brand,
            'primary-rgb'                       => $rgb,
            'btn-primary-bg'                    => $brand,
            'btn-primary-border'                => $brand,
            'btn-primary-color'                 => $text,
            'btn-primary-bg-hvr'                => $hover,
            'atum-btn-primary-bg'               => $brand,
            'atum-btn-primary-bg-hvr'           => $hover,
            'form-check-input-checked-bg-color' => $brand,
            'progress-bar-bg'                   => $brand,
            // Further Atum tokens (names taken from a tested Atum theme set).
            'dropdown-header-bg'                => $header,
            'header-item-content-jversion-bg'   => $header,
            'header-item-content-jversion-color' => $hText,
            'atum-dropdown-link-hover-bg'       => $brand,
            'atum-dropdown-link-hover-color'    => $text,
            'dropdown-item-bg-hvr'              => $brand,
            'dropdown-item-color-hvr'           => $text,
            'file-selector-btn-bg-hvr'          => $brand,
            'joomla-tablist-btn-aria-exp-bg'    => $brand,
            'joomla-tablist-btn-aria-exp-aft-bg' => $brand,
            'joomla-tablist-btn-aria-exp'       => $text,
            'joomla-tab-btn-hvr'                => $brand,
            'choices-list-multiple-item-bg'     => $brand,
            'choices-list-multiple-item'        => $text,
            'template-quickicon-bg-hvr'         => $brand,
            'quickicon-linkadd-bg-hvr'          => $brand,
            'quickicon-color-hvr'               => $text,
            'quickicon-link-hvr'                => $text,
        ];

        $declarations = [];

        foreach ($vars as $name => $value) {
            $declarations[] = '--' . $name . ': ' . $value;
        }

        $all = 'html:root, html:root[data-color-scheme="dark"], html:root[data-bs-theme="dark"]';

        $css = [$all . ' { ' . implode('; ', $declarations) . '; }'];

        // Links: only in the light scheme and only where the brand color is
        // readable on white (WCAG 3:1); the dark scheme keeps Atum's own.
        if (ColorHelper::contrast($brand, '#ffffff') >= 3.0) {
            $linkHover = ColorHelper::shade($brand, -0.2);

            $css[] = 'html:root:not([data-color-scheme="dark"]):not([data-bs-theme="dark"]) { '
                . '--link-color: ' . $brand . '; --template-link-color: ' . $brand . '; --table-link-color: ' . $brand . ';'
                . ' --link-hover-color: ' . $linkHover . '; --template-link-hover-color: ' . $linkHover . ';'
                . ' --link-color-rgb: ' . implode(', ', ColorHelper::toRgb($brand)) . ';'
                . ' --link-color-rgb-hvr: ' . implode(', ', ColorHelper::toRgb($linkHover)) . '; }';
        }

        // Atum's header text is white. Only a light brand color needs dark
        // text; dropdown menus (user menu etc.) keep their own colors.
        if (!$onDark) {
            $css[] = 'html:root body #header :is(.header-item-content, .header-item-content > a,'
                . ' .header-item-text, .page-title, .header-title):not(.dropdown-menu *)'
                . ' { color: ' . $hText . ' !important; }';
        }

        // Focus ring of form fields in the brand color.
        $css[] = 'html:root body .form-control:focus, html:root body .form-select:focus'
            . ' { border-color: ' . $brand . ' !important;'
            . ' box-shadow: 0 0 0 0.2rem ' . ColorHelper::toRgba($brand, 25) . ' !important; }';

        return array_merge(
            $css,
            $this->buildButtonCss($brand, $text, $hover),
            $this->buildHarmonyCss($brand, $header),
            $this->buildComponentCss($brand, $text, $hover, $header),
            $this->buildSurfaceCss($header)
        );
    }

    /**
     * Maps Atum's per-variant button colors onto Bootstrap's --btn-* variables
     * (the way Atum's own .btn rules read them), so every button variant is
     * solid and readable, and the primary buttons carry the brand color.
     *
     * @return string[]
     */
    private function buildButtonCss(string $brand, string $text, string $hover): array
    {
        $b   = 'html:root body ';
        $css = [];

        $css[] = $b . '.btn-primary { --btn-color: ' . $text . '; --btn-bg: ' . $brand . '; --btn-border-color: ' . $brand
            . '; --btn-hover-color: ' . $text . '; --btn-hover-bg: ' . $hover . '; --btn-hover-border-color: ' . $hover
            . '; --btn-active-color: ' . $text . '; --btn-active-bg: ' . $hover . '; --btn-active-border-color: ' . $hover
            . '; --btn-disabled-color: ' . $text . '; --btn-disabled-bg: ' . $brand . '; --btn-disabled-border-color: ' . $brand . '; }';

        foreach (['secondary' => '', 'success' => '#fff', 'danger' => '#fff', 'warning' => '', 'info' => ''] as $name => $fallback) {
            $c    = 'var(--btn-' . $name . '-color' . ($fallback !== '' ? ',' . $fallback : '') . ')';
            $ch   = 'var(--btn-' . $name . '-color-hvr' . ($fallback !== '' ? ',' . $fallback : '') . ')';
            $bg   = 'var(--btn-' . $name . '-bg)';
            $bgh  = 'var(--btn-' . $name . '-bg-hvr)';
            $css[] = $b . '.btn-' . $name . ' { --btn-color: ' . $c . '; --btn-bg: ' . $bg . '; --btn-border-color: ' . $bg
                . '; --btn-hover-color: ' . $ch . '; --btn-hover-bg: ' . $bgh . '; --btn-hover-border-color: ' . $bgh
                . '; --btn-active-color: ' . $ch . '; --btn-active-bg: ' . $bgh . '; --btn-active-border-color: ' . $bgh
                . '; --btn-disabled-color: ' . $c . '; --btn-disabled-bg: ' . $bg . '; --btn-disabled-border-color: ' . $bg . '; }';
        }

        // Direct declarations as a safety net: they do not depend on how the
        // template reads its variables.
        $css[] = $b . '.btn-primary { background-color: ' . $brand . ' !important; border-color: ' . $brand
            . ' !important; color: ' . $text . ' !important; }';
        $css[] = $b . '.btn-primary:is(:hover, :focus-visible, :active, .active) { background-color: ' . $hover
            . ' !important; border-color: ' . $hover . ' !important; color: ' . $text . ' !important; }';

        foreach (['success' => ['#198754', '#157347'], 'danger' => ['#dc3545', '#bb2d3b']] as $name => [$bg, $bgh]) {
            $css[] = $b . '.btn-' . $name . ' { background-color: var(--btn-' . $name . '-bg, ' . $bg . ') !important;'
                . ' border-color: var(--btn-' . $name . '-bg, ' . $bg . ') !important; color: #fff !important; }';
            $css[] = $b . '.btn-' . $name . ':is(:hover, :focus-visible, :active, .active) { background-color: var(--btn-'
                . $name . '-bg-hvr, ' . $bgh . ') !important; border-color: var(--btn-' . $name . '-bg-hvr, ' . $bgh . ') !important; }';
        }

        $css[] = $b . '.btn-secondary { background-color: var(--btn-secondary-bg) !important;'
            . ' border-color: var(--btn-secondary-bg) !important; color: var(--btn-secondary-color) !important; }';
        $css[] = $b . '.btn-secondary:is(:hover, :focus-visible, :active, .active) { background-color: var(--btn-secondary-bg-hvr,'
            . ' var(--btn-secondary-bg)) !important; color: var(--btn-secondary-color-hvr, var(--btn-secondary-color)) !important; }';

        // Icons inside solid buttons follow the button text color.
        $css[] = $b . '.btn:is(.btn-primary, .btn-secondary, .btn-success, .btn-info, .btn-warning, .btn-danger)'
            . ' > :is([class^="icon-"], [class*=" icon-"], [class^="fa-"], [class*=" fa-"]) { color: inherit !important; }';

        return $css;
    }

    /**
     * Light scheme only: derives the surrounding palette from the brand and
     * header colors - page background, text and heading colors, card headers,
     * toolbar, quick icons and the neutral (secondary) buttons.
     *
     * @return string[]
     */
    private function buildHarmonyCss(string $brand, string $header): array
    {
        $light = 'html:root:not([data-color-scheme="dark"]):not([data-bs-theme="dark"])';
        $page  = ColorHelper::clean((string) $this->params->get('page_bg', ''));
        $auto  = $page === '';
        $page  = $page ?: ColorHelper::shade($brand, 0.94);

        $ink  = ColorHelper::contrast($header, '#ffffff') >= 7.0 ? ColorHelper::shade($header, -0.1) : '#2b2f33';
        $head = ColorHelper::contrast($header, '#ffffff') >= 4.5 ? $header : ColorHelper::shade($brand, -0.45);
        $sec  = ColorHelper::shade($brand, 0.88);
        $secH = ColorHelper::shade($brand, 0.78);

        $vars = [
            'body-color'                 => $ink,
            'headings-color'             => $head,
            'card-header-bg'             => $page,
            'card-header-color'          => $ink,
            'atum-subhead-bg'            => $page,
            'template-quickicon-bg'      => $page,
            'card-bg'                    => '#ffffff',
            'card-body-bg'               => '#ffffff',
            'quickicon-bg'               => '#ffffff',
            'quickicon-linkadd-bg'       => $page,
            'template-quickicon-color'   => $head,
            'quickicon-color'            => $head,
            'quickicon-linkadd-color'    => $head,
            'options-form-legend-color'  => $head,
            'nav-header-color'           => $head,
            'options-form-color'         => $ink,
            'btn-secondary-bg'           => $sec,
            'btn-secondary-border'       => $sec,
            'btn-secondary-color'        => $ink,
            'btn-secondary-bg-hvr'       => $secH,
            'btn-secondary-color-hvr'    => $ink,
        ];

        $declarations = [];

        foreach ($vars as $name => $value) {
            $declarations[] = '--' . $name . ': ' . $value;
        }

        $css = [$light . ' { ' . implode('; ', $declarations) . '; }'];

        if ($auto) {
            $css[] = $light . ' { --body-bg: #ffffff; --admin-background: ' . $page . '; }';
            $css[] = $light . ' body.admin { background-color: ' . $page . '; }';
        }

        $css[] = $light . ' body.admin { color: var(--body-color); }';
        $css[] = $light . ' :is(h1, h2, h3, h4, h5, h6):not(#header *) { color: var(--headings-color); }';
        $css[] = $light . ' .card { background-color: var(--card-bg); }';
        $css[] = $light . ' .card-header { background-color: var(--card-header-bg); color: var(--card-header-color); }';

        return $css;
    }

    /**
     * Brand color on the common Bootstrap/Atum components: tabs, pills,
     * pagination, dropdown highlights, outline buttons, tables, card headers,
     * toolbar and header icons.
     *
     * @return string[]
     */
    private function buildComponentCss(string $brand, string $text, string $hover, string $header): array
    {
        $b = 'html:root body ';
        $t = ColorHelper::toRgba($brand, 10);

        return [
            // Header: icon circles and the bar behind the items follow the brand.
            $b . '.header .header-items { background: var(--header-bg); }',
            $b . '.header .header-item-icon > * { background: var(--header-logo-bg); color: ' . ColorHelper::contrastText(ColorHelper::shade($header, -0.2)) . '; }',
            $b . '.sidebar-toggle a { color: ' . ColorHelper::contrastText($header) . '; }',
            // Toolbar "Options" button as a primary button.
            $b . '.subhead .btn.button-options { --subhead-btn-bg: ' . $brand . '; --subhead-btn-bg-hvr: ' . $hover
                . '; --subhead-btn-border: ' . $brand . '; color: ' . $text . '; }',
            // Tabs: the active tab is filled with the brand color.
            $b . 'joomla-tab :is(button[role="tab"], button[role="region"]):is([aria-selected="true"], [aria-expanded="true"])'
                . ' { background-color: ' . $brand . '; color: ' . $text . '; box-shadow: none; }',
            $b . 'joomla-tab :is(button[role="tab"], button[role="region"]):is(:hover, :focus-visible)'
                . ' { background-color: ' . $hover . '; color: ' . $text . '; }',
            // Pills, tabs, pagination, dropdowns, progress, outline buttons.
            $b . '.nav-pills { --nav-pills-link-active-bg: ' . $brand . '; --nav-pills-link-active-color: ' . $text . '; }',
            $b . '.nav-pills .nav-link.active { background-color: ' . $brand . '; color: ' . $text . '; }',
            $b . '.nav-tabs { --nav-tabs-link-hover-border-color: ' . $brand . '; }',
            $b . '.pagination { --pagination-active-bg: ' . $brand . '; --pagination-active-border-color: ' . $brand
                . '; --pagination-active-color: ' . $text . '; }',
            $b . '.dropdown-menu { --dropdown-link-active-bg: ' . $brand . '; --dropdown-link-active-color: ' . $text . '; }',
            $b . '.progress { --progress-bar-bg: ' . $brand . '; }',
            $b . '.btn-outline-primary { --btn-color: ' . $brand . '; --btn-border-color: ' . $brand
                . '; --btn-hover-color: ' . $text . '; --btn-hover-bg: ' . $brand . '; --btn-hover-border-color: ' . $brand
                . '; --btn-active-color: ' . $text . '; --btn-active-bg: ' . $hover . '; --btn-active-border-color: ' . $hover . '; }',
            // Larger page title in the header; roomier dashboard quick icons.
            $b . '#header :is(.page-title, h1) { font-size: 1.5rem; }',
            $b . '.quick-icons :is(.quickicon-single, .quickicon-group) { min-block-size: 6rem; }',
            $b . '.quick-icons .quickicon > a { min-block-size: 6rem; padding-block: 0.5rem; }',
            // Tables, card headers, choices.js dropdowns.
            $b . '.table-hover { --table-hover-bg: ' . $t . '; }',
            $b . '.table > thead > tr > th { border-bottom-color: ' . $brand . '; }',
            $b . '.choices .choices__list--dropdown .choices__item--selectable.is-highlighted { background-color: '
                . ColorHelper::toRgba($brand, 14) . '; }',
        ];
    }

    /**
     * Optional sidebar and page background colors.
     *
     * @return string[]
     */
    private function buildSurfaceCss(string $header = ''): array
    {
        $css     = [];
        $sidebar = ColorHelper::clean((string) $this->params->get('sidebar_color', ''));
        $page    = ColorHelper::clean((string) $this->params->get('page_bg', ''));

        // Without an explicit sidebar color, the sidebar follows the header
        // color (when a brand or header color is set and the option is on).
        if ($sidebar === '' && $header !== '' && (int) $this->params->get('sidebar_follow', 1) === 1) {
            $sidebar = $header;
        }

        if ($sidebar !== '') {
            $text = ColorHelper::contrastText($sidebar);
            $edge = ColorHelper::shade($sidebar, -0.2);

            $css[] = 'html:root, html:root[data-color-scheme="dark"], html:root[data-bs-theme="dark"] { '
                . '--template-sidebar-bg: ' . $sidebar . '; --main-nav-ul-bg: ' . $edge . '; --sidebarwrapper-bg: ' . $sidebar . '; }';
            $css[] = 'html:root body .sidebar-wrapper, html:root body #sidebar-wrapper { background-color: ' . $sidebar . '; }';

            // Atum assumes a dark sidebar with white text: a light sidebar needs dark text.
            if ($text !== '#ffffff') {
                $css[] = 'html:root body .sidebar-wrapper :is(.main-nav a, .main-nav .sidebar-item-title, .sidebar-toggle a,'
                    . ' .menu-collapse-arrow, .menu-quicktask a):not(.mm-active):not(:hover):not(:focus)'
                    . ' { color: ' . $text . '; }';
            }
        }

        if ($page !== '') {
            $css[] = 'html:root:not([data-color-scheme="dark"]):not([data-bs-theme="dark"]) { --body-bg: ' . $page . '; }';
            $css[] = 'html:root:not([data-color-scheme="dark"]):not([data-bs-theme="dark"]) body.admin { background-color: ' . $page . '; }';
        }

        return $css;
    }

    /**
     * Optional fixed height for the header logos.
     *
     * @return string[]
     */
    private function buildLogoCss(): array
    {
        $height = (int) trim((string) $this->params->get('logo_height', ''));

        if ($height <= 0) {
            return [];
        }

        $height = min(120, max(16, $height));

        return [
            'html:root body #header .logo img { height: ' . $height . 'px !important; width: auto !important;'
                . ' max-width: none !important; }',
        ];
    }

    /**
     * Corner radius (fields, buttons, panels) and shadow presets.
     * Input groups and button groups keep a flat joint: only their outer
     * corners are rounded.
     *
     * @return string[]
     */
    private function buildShapeCss(): array
    {
        $css       = [];
        $ctrl      = trim((string) $this->params->get('ctrl_radius', ''));
        $panel     = trim((string) $this->params->get('card_radius', ''));
        $btnShadow = (string) $this->params->get('btn_shadow', 'none');
        $cardShadow = (string) $this->params->get('card_shadow', 'none');
        $p         = 'html:root body ';

        if ($ctrl !== '' && is_numeric($ctrl)) {
            $r = max(0, min(40, (int) $ctrl)) . 'px';

            $css[] = $p . '.btn, ' . $p . '.form-control, ' . $p . '.form-select, ' . $p . '.input-group-text'
                . ' { border-radius: ' . $r . ' !important; }';
            $css[] = $p . '.input-group > :not(:first-child), ' . $p . '.btn-group > :not(:first-child)'
                . ' { border-start-start-radius: 0 !important; border-end-start-radius: 0 !important; }';
            $css[] = $p . '.input-group > :not(:last-child), ' . $p . '.btn-group > :not(:last-child)'
                . ' { border-start-end-radius: 0 !important; border-end-end-radius: 0 !important; }';
        }

        if ($panel !== '' && is_numeric($panel)) {
            $r = max(0, min(40, (int) $panel)) . 'px';

            $css[] = $p . '.card, ' . $p . '.modal-content, ' . $p . '.dropdown-menu, ' . $p . 'fieldset.options-form'
                . ' { border-radius: ' . $r . ' !important; }';
        }

        $btnValues = [
            'subtle' => '0 1px 2px rgba(0, 0, 0, 0.2)',
            'medium' => '0 3px 8px rgba(0, 0, 0, 0.22)',
            'strong' => '0 6px 16px rgba(0, 0, 0, 0.3)',
        ];

        if (isset($btnValues[$btnShadow])) {
            $css[] = $p . '.btn-primary { box-shadow: ' . $btnValues[$btnShadow] . ' !important; }';
        }

        $cardValues = [
            'subtle' => '0 1px 3px rgba(0, 0, 0, 0.15)',
            'medium' => '0 4px 12px rgba(0, 0, 0, 0.18)',
            'strong' => '0 12px 32px rgba(0, 0, 0, 0.28)',
        ];

        if (isset($cardValues[$cardShadow])) {
            $css[] = $p . '.card { box-shadow: ' . $cardValues[$cardShadow] . ' !important; }';
        }

        return $css;
    }
}
