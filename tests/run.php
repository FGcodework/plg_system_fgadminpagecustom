<?php

/**
 * Plain-PHP tests (no PHPUnit needed): php tests/run.php
 */

declare(strict_types=1);

define('_JEXEC', 1);

require __DIR__ . '/../src/Helper/ColorHelper.php';
require __DIR__ . '/../src/Helper/SettingsSanitizer.php';
require __DIR__ . '/../src/Helper/MenuScript.php';
require __DIR__ . '/../src/Helper/LogoReplacer.php';
require __DIR__ . '/../src/Helper/ImportExport.php';
require __DIR__ . '/../src/Helper/CssBuilder.php';

require __DIR__ . '/registry_stub.php';

use FG\Plugin\System\AdminPageCustom\Helper\ColorHelper as C;
use FG\Plugin\System\AdminPageCustom\Helper\SettingsSanitizer as S;

$fail = 0;
$n    = 0;
$eq   = static function ($actual, $expected, string $name) use (&$fail, &$n): void {
    $n++;

    if ($actual !== $expected) {
        $fail++;
        echo "FAIL $name: expected " . var_export($expected, true) . ', got ' . var_export($actual, true) . "\n";
    }
};

// ColorHelper
$eq(C::clean(' #ABC '), '#abc', 'clean short');
$eq(C::clean('#8B5A2B'), '#8b5a2b', 'clean lower');
$eq(C::clean('#12345'), '', 'clean bad length');
$eq(C::clean('red'), '', 'clean named');
$eq(C::clean('#8b5a2bcc'), '#8b5a2bcc', 'clean alpha');
$eq(C::toRgb('#fff'), [255, 255, 255], 'rgb short');
$eq(C::toRgb('#8b5a2b'), [139, 90, 43], 'rgb long');
$eq(C::contrastText('#000000'), '#ffffff', 'text on black');
$eq(C::contrastText('#ffffff'), '#000000', 'text on white');
$eq(C::contrastText('#44362a'), '#ffffff', 'text on brown');
$eq(round(C::contrast('#000000', '#ffffff'), 2), 21.0, 'contrast max');
$eq(round(C::contrast('#777777', '#777777'), 2), 1.0, 'contrast min');
$eq(C::shade('#808080', -1.0), '#000000', 'shade black');
$eq(C::shade('#808080', 1.0), '#ffffff', 'shade white');
$eq(C::shade('#ff0000', 0.0), '#ff0000', 'shade none');
$eq(C::toRgba('#ff0000', 50), 'rgba(255, 0, 0, 0.50)', 'rgba');
$eq(C::toRgba('#ff0000', 500), 'rgba(255, 0, 0, 1.00)', 'rgba clamp');

// SettingsSanitizer
$r = S::sanitize([
    'brand_color' => '#8B5A2B', 'header_color' => 'url(x)', 'page_bg' => '',
    'ctrl_radius' => '12', 'card_radius' => '99', 'logo_height' => '40',
    'btn_shadow' => 'medium', 'card_shadow' => 'huge', 'sidebar_follow' => '1',
    'logo' => 'images/a.png#joomlaImage://x', 'logo_small' => '"><script>', 'unknown' => 'x',
]);
$eq($r['values']['brand_color'], '#8b5a2b', 'san color');
$eq($r['values']['page_bg'], '', 'san empty');
$eq($r['values']['ctrl_radius'], 12, 'san radius');
$eq($r['values']['logo_height'], 40, 'san height');
$eq($r['values']['btn_shadow'], 'medium', 'san shadow');
$eq($r['values']['sidebar_follow'], 1, 'san follow');
$eq($r['values']['logo'], 'images/a.png#joomlaImage://x', 'san logo');
$eq(isset($r['values']['unknown']), false, 'san unknown dropped');
$eq($r['rejected'], ['header_color', 'card_radius', 'card_shadow', 'logo_small'], 'san rejected');
$eq(S::sanitize(['logo' => 'javascript:alert(1)'])['rejected'], ['logo'], 'san js scheme');
$eq(S::sanitize(['ctrl_radius' => '-3'])['rejected'], ['ctrl_radius'], 'san negative');
$eq(S::sanitize(['brand_color' => ['x']])['rejected'], ['brand_color'], 'san array');

// LogoReplacer
use FG\Plugin\System\AdminPageCustom\Helper\LogoReplacer as L;
use FG\Plugin\System\AdminPageCustom\Helper\ImportExport as IE;
use FG\Plugin\System\AdminPageCustom\Helper\CssBuilder as B;
use Joomla\Registry\Registry;

$logos = ['expanded' => '/m/big.png', 'collapsed' => '/m/small.png'];

$out = L::replace('<header id="header"><div class="logo"><img src="a.svg" class="logo" alt=""><img src="b.svg" class="logo-collapsed"></div><div><img src="/avatar.png"></div></header><main><img src="/x/logo.png"></main>', $logos);
$eq(substr_count((string) $out, '/m/big.png'), 1, 'logo expanded');
$eq(substr_count((string) $out, '/m/small.png'), 1, 'logo collapsed');
$eq(str_contains((string) $out, '/avatar.png') && str_contains((string) $out, '/x/logo.png'), true, 'logo leaves other images');

$out = L::replace('<header><img class="logo-large" src="a.svg" srcset="a.svg 1x"><img class="logo-small" src="b.svg"></header>', $logos);
$eq(str_contains((string) $out, 'srcset'), false, 'logo srcset removed');
$out = L::replace('<header><a href="/"><img src="/a.svg"></a><img src="/b.svg"><img src="/avatar.png"></header>', $logos);
$eq(substr_count((string) $out, '/m/'), 2, 'logo fallback first two');
$eq(str_contains((string) $out, '/avatar.png'), true, 'logo fallback keeps third');
$eq(L::replace('<main><img src="/logo.png"></main>', $logos), null, 'logo no header');
$eq(L::replace('<header>no images</header>', $logos), null, 'logo no images');
$eq(L::replace('<header><img src="logo.png"></header>', ['expanded' => '', 'collapsed' => '']), null, 'logo nothing configured');
$eq(str_contains((string) L::replace('<header><img src="logo.png"></header>', ['expanded' => '/a$1\\b.png', 'collapsed' => '']), '/a$1\\b.png'), true, 'logo special chars literal');

// ImportExport
$eq(IE::paramsToArray('{"a":1}'), ['a' => 1], 'params json');
$eq(IE::paramsToArray('garbage'), [], 'params bad json');
$eq(IE::paramsToArray(new Registry(['a' => 2])), ['a' => 2], 'params registry');
$eq(IE::paramsToArray(null), [], 'params null');

$data = ['params' => '{"keep":1}'];
IE::applyValuesToData($data, ['brand_color' => '#fff']);
$eq(json_decode($data['params'], true), ['keep' => 1, 'brand_color' => '#fff'], 'apply keeps json string shape');
$data = (object) ['params' => new Registry(['keep' => 1])];
IE::applyValuesToData($data, ['x' => 5]);
$eq($data->params instanceof Registry && $data->params->get('x') === 5 && $data->params->get('keep') === 1, true, 'apply keeps Registry shape');
$data = ['params' => ['keep' => 1]];
IE::applyValuesToData($data, ['x' => 5]);
$eq($data['params'], ['keep' => 1, 'x' => 5], 'apply keeps array shape');

// CssBuilder
$eq((new B(new Registry([])))->build(), '', 'css empty');
$css = (new B(new Registry(['brand_color' => '#8b5a2b'])))->build();
$eq(str_contains($css, '--header-bg: #8b5a2b'), true, 'css brand header');
$eq(str_contains($css, '--table-link-color: #8b5a2b'), true, 'css brand links');
$eq(str_contains($css, '#header :is(.header-item-content'), false, 'css dark brand no header text override');
$eq(str_contains((new B(new Registry(['brand_color' => '#f5d000'])))->build(), '#header :is(.header-item-content'), true, 'css light brand header text override');
$eq(str_contains((new B(new Registry(['header_color' => '#112233'])))->build(), '--header-bg'), false, 'css header without brand is ignored');
$eq(str_contains((new B(new Registry(['ctrl_radius' => '99'])))->build(), 'border-radius: 40px'), true, 'css radius clamped');
$eq(str_contains((new B(new Registry(['sidebar_color' => '#eeeeee'])))->build(), '.sidebar-wrapper'), true, 'css sidebar only');
$eq(str_contains((new B(new Registry(['logo_height' => '500'])))->build(), 'height: 120px'), true, 'css logo height clamped');

$eq(S::sanitize(['menu_open_first' => '1'])['values']['menu_open_first'], 1, 'san menu_open_first');
$eq(S::sanitize(['menu_open_first' => '2'])['rejected'], ['menu_open_first'], 'san menu_open_first invalid');
$js = \FG\Plugin\System\AdminPageCustom\Helper\MenuScript::build();
$eq(str_contains($js, 'fgapcMenuOpenFirst') && !str_contains($js, '<script'), true, 'menu script shape');

echo $fail === 0 ? "OK: $n assertions passed\n" : "$fail of $n FAILED\n";
exit($fail === 0 ? 0 : 1);
