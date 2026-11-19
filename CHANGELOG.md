# Changelog - plg_system_fgadminpagecustom

## 1.4.2 (2026-11-19)
- FIX: "Open first submenu item" did nothing in the standard Atum sidebar, because Atum's component parents carry a real link (`index.php?option=...`) instead of `#`. Parent items are now recognized by their toggle (`has-arrow` / `data-bs-toggle`), so both the Atum sidebar and the Phoca Top Menu work.

## 1.4.1 (2026-11-19)
- NEW: option "Open first submenu item" (Behavior tab, off by default). A click on a component in the Components menu opens its first submenu item directly, as in Joomla 3.10. Works in the Atum sidebar and in the Phoca Top Menu module; it only affects direct children of the Components item, ignores modified clicks (Ctrl/Shift/middle button) and leaves touch devices on the plain expand behaviour.

## 1.4.0 (2026-10-30)
- CHANGE: internal restructuring, no visual change. The 940-line plugin class is split into `Helper/CssBuilder` (all CSS), `Helper/LogoReplacer` (header logo swap), `Helper/ImportExport` (settings form data) next to `Helper/ColorHelper` and `Helper/SettingsSanitizer`; the plugin class only wires events. The generated CSS and the logo replacement were compared with the previous version for 16 configuration/markup combinations and are identical.
- Test suite (`php tests/run.php`, 55 assertions) now also covers the logo replacer, the import/export shape handling and the CSS builder.

## 1.3.6 (2026-10-30)
- FIX: settings import now validates every value (colors, radii 0-40, logo height 16-120, shadow presets, media paths); invalid values are skipped and listed in a warning instead of being stored.
- CHANGE: color maths moved to `Helper/ColorHelper` and import validation to `Helper/SettingsSanitizer`; both are covered by plain-PHP tests in `tests/run.php`.

## 1.3.5 (2026-10-26)
- NEW: larger page title in the header and taller dashboard quick icon tiles.

## 1.3.4 (2026-10-26)
- FIX: dashboard cards and the quick icon container blended into the page background; cards are white again (light scheme) so the tinted tiles and card headers stand out.

## 1.3.3 (2026-10-26)
- FIX: plain links (tables, dashboard lists) stayed blue because Bootstrap 5.3 reads link colors from the `--link-color-rgb` tokens; those are now set together with the hex variables.

## 1.3.2 (2026-10-26)
- FIX: the page title in the header was invisible (heading color equalled the header color); headings inside the header are no longer recolored.
- FIX: links in tables (dashboard modules, lists) now use the brand color.
- NEW: dashboard quick icons (including the "+" strip), dropdown highlights, tab states, header version badge and choices.js items follow the brand/header colors through the corresponding Atum tokens.

## 1.3.1 (2026-10-26)
- FIX: primary, success, danger and secondary buttons (toolbar included) now also get direct color declarations, so they stay solid and readable regardless of how the template reads its variables.
- The generated CSS starts with a version comment, which makes it easy to check which version is active.

## 1.3.0 (2026-10-26)
- FIX: text on primary toolbar buttons (Versions, Preview, ...) was unreadable; every button variant now maps Atum's per-variant colors onto Bootstrap's `--btn-*` variables, so toolbar and form buttons are solid and readable.
- NEW: in the light scheme the surrounding palette is derived from the brand and header colors: page background (unless set explicitly), text and heading colors, card headers, toolbar, quick icons and the neutral (secondary) buttons.

## 1.2.0 (2026-10-26)
- NEW: optional separate header color (defaults to the brand color); text and icon colors are calculated from it.
- NEW: the sidebar follows the header color by default ("Sidebar follows header"); an explicit sidebar color still wins.
- CHANGE: the active tab is filled with the brand color instead of being underlined.

## 1.1.0 (2026-10-26)
- NEW: the brand color now also styles tabs, nav pills, pagination, dropdown highlights, progress bars, outline-primary buttons, table row hover and header, card headers, the toolbar "Options" button, header icon circles and choices.js dropdowns.
- NEW: optional sidebar color (text color is calculated automatically) and page background color (light scheme).
- NEW: both colors are part of the settings export/import.

## 1.0.1 (2026-10-26)
- FIX: the header logos were not replaced. The plugin relied on specific class names; it now finds the logo images inside the page header by any "logo" hint in their class or file name, and falls back to the first two header images.

## 1.0.0 (2026-10-26)
- Initial release.
- Brand color: one color for the header bar, sidebar menu highlights, primary buttons (hover is a darker shade), links and the focus ring of form fields, with automatic text contrast (WCAG). Links use the brand color only where it is readable on white; the dark color scheme keeps Atum's own link color.
- Header logos: custom logo for the expanded sidebar, optional small logo for the collapsed one, optional fixed height.
- Shape: corner radius for fields and buttons (grouped fields and buttons keep a flat joint) and for cards, option panels, dropdowns and dialogs; shadow presets for primary buttons and cards.
- Recovery switch: `?fgapc=off` switches all customization off for the rest of the session, `?fgapc=on` switches it back on.
- Export / Import of the settings as JSON.
