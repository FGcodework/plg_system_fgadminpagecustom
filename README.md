> **DEPRECATED.** This plugin is no longer developed. It has been replaced by **[FG Admin Customizer](https://github.com/FGcodework/plg_system_fgadmincustom)**, which combines the login page and the administrator styling in one plugin and takes over the settings of this one. This repository is archived and kept only so that existing update links keep working.

<h1 align="center">FG Admin Page Customizer plugin for Joomla</h1>

<p align="center">
  <img src="https://img.shields.io/github/v/release/FGcodework/plg_system_fgadminpagecustom?color=FF6B4A&label=release" alt="Latest release">
  <img src="https://img.shields.io/badge/Joomla-5.x%20%7C%206.x-blue.svg?logo=joomla&logoColor=white" alt="Joomla">
  <img src="https://img.shields.io/badge/PHP-8.1%2B-purple.svg?logo=php&logoColor=white" alt="PHP">
  <img src="https://img.shields.io/badge/license-GPLv2%2B-green.svg" alt="License">
  <img src="https://img.shields.io/github/downloads/FGcodework/plg_system_fgadminpagecustom/total?cacheSeconds=3600&color=brown" alt="Downloads">
  <a href="https://ko-fi.com/fgcodework"><img src="https://img.shields.io/badge/support-Ko--fi-F16061.svg?logo=ko-fi&logoColor=white" alt="Support on Ko-fi"></a>
</p>

A Joomla 5/6 system plugin that gives the **logged-in administrator** (the
Atum template) its own look from a single brand color - header bar, sidebar
highlights, primary buttons, links and field focus ring - plus your own
header logos and a corner radius and shadow for fields, buttons and panels.
It only sets Atum's own CSS custom properties and a few safe overrides; it
never touches the markup of the pages themselves.

Part of the **FG** series of Joomla extensions. The sibling plugin
[FG Admin Login Customizer](https://github.com/FGcodework/plg_system_fgadminlogincustom)
styles the login page before sign-in; this one takes over once you are
logged in. The two do not overlap and work well together.

## Features

- **Brand color**: one color sets the header bar, sidebar menu highlights,
  primary buttons (hover is a darker shade), links and the focus ring of the
  form fields. Text colors on top of it are calculated automatically
  (WCAG contrast); links use the brand color only where it stays readable
  on white, and the dark color scheme keeps Atum's own link color
- **Header logos**: replace the logo of the expanded sidebar and, optionally,
  the small logo of the collapsed one, with an optional fixed height
- **Shape**: corner radius for fields and buttons (grouped fields and
  buttons keep a flat joint) and for cards, option panels, dropdowns and
  dialogs; shadow presets for primary buttons and cards
- **Both color schemes**: works with Atum's light, dark and "follow the
  system" setting
- **Recovery switch**: `?fgapc=off` switches every customization off for the
  rest of the session (see below)
- **Export / Import** of the settings as JSON, to copy a look to other sites

## Installation

1. Download the latest release (`.zip`) from the
   [Releases](https://github.com/FGcodework/plg_system_fgadminpagecustom/releases) tab.
2. In the Joomla administrator go to **System → Install → Extensions** and
   upload the downloaded `.zip`.
3. **System → Manage → Plugins** → find "System - FG Admin Page Customizer"
   and publish it.
4. Open the plugin and pick a brand color; save and reload the page.

## Plugin settings

| Tab | What it controls |
| --- | --- |
| **Plugin** | Extension info and a short "Support this plugin" note - no settings on this tab. |
| **Brand & Logo** | Brand color, header logo (expanded), small header logo (collapsed), logo height. |
| **Shape** | Corner radius of fields/buttons and of panels, shadow of primary buttons and of cards. |
| **Export / Import** | Read-only JSON export of the current settings; paste JSON here + Save (twice) to import from another site. |

Every field left empty keeps the Atum template's own styling - nothing is
forced unless you set it.

## Recovery switch

If a setting ever makes the administrator hard to use, open any
administrator page with `?fgapc=off` added to the address, for example
`/administrator/index.php?option=com_plugins&fgapc=off`. Every customization
of this plugin is then switched off for the rest of your session (nothing is
saved or changed), so you can open the plugin and fix the setting. Add
`?fgapc=on` to switch it back on.

## Requirements

- Joomla 5 or 6
- PHP 8.1+
- The Atum administrator template (or a child template of it)

## License

GNU General Public License v2.0 or later - see [LICENSE](LICENSE).

## Support this project

This plugin is free, open source, and always will be - no feature is locked behind a paywall. If it has saved you time, a one-off tip on Ko-fi is welcome, but entirely optional.

<a href="https://ko-fi.com/fgcodework"><img src="https://storage.ko-fi.com/cdn/kofi3.png?v=4" alt="ko-fi" width="180"></a>

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## Development

The color maths, the logo replacer, the import/export handling and the CSS builder have no Joomla dependency and are covered by plain-PHP tests:

```
php tests/run.php
```
