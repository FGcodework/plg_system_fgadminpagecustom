<?php

/**
 * @package     plg_system_fgadminpagecustom
 * @version     1.4.3
 * @license     GNU General Public License version 2 or later
 *
 * Small admin-side script: a click on a component's parent menu item
 * (Components -> component) opens the component's first submenu item,
 * like Joomla 3.10 did. Works in the Atum sidebar and in the Phoca Top Menu.
 */

declare(strict_types=1);

namespace FG\Plugin\System\AdminPageCustom\Helper;

\defined('_JEXEC') or die;

final class MenuScript
{
    /**
     * @return string JavaScript (no <script> tags)
     */
    public static function build(): string
    {
        return <<<'JS'
(function () {
  'use strict';
  if (window.fgapcMenuOpenFirst) { return; }
  window.fgapcMenuOpenFirst = true;

  var MENU = 'nav, #menu, #sidebar-wrapper, .main-nav-container';

  function isRealLink(a) {
    var h = a.getAttribute('href');
    if (!h || h === '#' || /^\s*javascript:/i.test(h)) { return false; }
    if (a.hasAttribute('data-bs-toggle') || a.classList.contains('has-arrow')) { return false; }
    return !a.closest('.menu-quicktask');
  }

  // On window, capture phase: Bootstrap registers its delegated handlers on document
  // in the capture phase, so only window runs reliably before them.
  window.addEventListener('click', function (e) {
    if (e.button !== 0 || e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) { return; }
    if (!e.target || !e.target.closest) { return; }

    // Touch devices keep the plain expand behaviour (no way to reach the other items otherwise).
    if (window.matchMedia && window.matchMedia('(hover: none)').matches) { return; }

    // The click may land on the link or on the padding of its <li> (Phoca flyout rows).
    var a = e.target.closest('a');
    var li = a ? a.closest('li') : e.target.closest('li');
    if (!li || !li.closest(MENU)) { return; }
    if (!a) { a = li.querySelector(':scope > a'); }
    if (!a || a.closest('li') !== li) { return; }

    // Parent toggles only: Phoca uses href="#" + data-bs-toggle, Atum a.has-arrow (with a real href).
    if (!a.classList.contains('has-arrow') && !a.hasAttribute('data-bs-toggle')) { return; }

    var sub = li.querySelector(':scope > ul');
    if (!sub || sub.contains(e.target)) { return; }

    // Only direct children of the top-level "Components" item (identified by its puzzle icon).
    var top = li.parentElement ? li.parentElement.closest('li') : null;
    var topLink = top ? top.querySelector(':scope > a') : null;
    if (!topLink || !topLink.querySelector('[class*="puzzle-piece"]')) { return; }

    var links = sub.querySelectorAll('a[href]');
    for (var i = 0; i < links.length; i++) {
      if (isRealLink(links[i])) {
        e.preventDefault();
        e.stopPropagation();
        window.location.href = links[i].href;
        return;
      }
    }
  }, true);
})();
JS;
    }
}
