<?php

/**
 * @package     plg_system_fgadminpagecustom
 * @version     1.4.3
 * @license     GNU General Public License version 2 or later
 *
 * Visual customization of the logged-in administrator (Atum template, Joomla 5/6):
 * one brand color, admin logos, corner radius and shadow presets.
 */

declare(strict_types=1);

namespace FG\Plugin\System\AdminPageCustom\Extension;

use FG\Plugin\System\AdminPageCustom\Helper\CssBuilder;
use FG\Plugin\System\AdminPageCustom\Helper\ImportExport;
use FG\Plugin\System\AdminPageCustom\Helper\LogoReplacer;
use FG\Plugin\System\AdminPageCustom\Helper\MenuScript;
use FG\Plugin\System\AdminPageCustom\Helper\SettingsSanitizer;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;

\defined('_JEXEC') or die;

final class AdminPageCustom extends CMSPlugin implements SubscriberInterface
{
    /** Session key of the recovery switch. */
    /** Shown in the generated CSS comment. */
    public const VERSION = '1.4.3';

    private const SAFE_STATE = 'plg_system_fgadminpagecustom.safe';

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onBeforeCompileHead'  => 'injectAssets',
            'onAfterRender'        => 'modifyBody',
            'onContentPrepareForm' => 'prepareAdminForm',
        ];
    }

    /**
     * Whether the current request is a normal logged-in Atum administrator
     * page (not the login screen - that is plg_system_fgadminlogincustom's
     * job - and not an iframe/popup view).
     */
    private function isAdminPage(): bool
    {
        $app = $this->getApplication();

        if (!$app instanceof CMSApplicationInterface || !$app->isClient('administrator')) {
            return false;
        }

        $identity = $app->getIdentity();

        if ($identity === null || $identity->guest) {
            return false;
        }

        if ($app->getInput()->getCmd('tmpl', '') === 'component') {
            return false;
        }

        // Atum itself or a child template of Atum.
        try {
            $template = $app->getTemplate(true);
            $names    = [(string) ($template->template ?? ''), (string) ($template->parent ?? '')];
        } catch (\Throwable $e) {
            return false;
        }

        return \in_array('atum', $names, true);
    }

    /**
     * Recovery switch. /administrator?fgapc=off switches every customization
     * of this plugin off for the rest of the session (nothing is saved);
     * ?fgapc=on switches it back on. The state lives in the session so that
     * it survives the click from the plugin list to the plugin's own settings.
     */
    private function isSafeMode(bool $announce = false): bool
    {
        $app   = $this->getApplication();
        $param = $app->getInput()->getCmd('fgapc', '');

        if ($param === 'off' || $param === 'on') {
            $app->setUserState(self::SAFE_STATE, $param === 'off' ? 1 : 0);

            if ($announce && $param === 'off') {
                $app->enqueueMessage(Text::_('PLG_SYSTEM_FGADMINPAGECUSTOM_SAFE_MODE_ACTIVE'), 'warning');
            }
        }

        return (int) $app->getUserState(self::SAFE_STATE, 0) === 1;
    }

    /**
     * Injects the inline CSS into the administrator pages.
     */
    public function injectAssets(Event $event): void
    {
        if (!$this->isAdminPage()) {
            return;
        }

        $doc = $this->getApplication()->getDocument();

        if (!$doc instanceof HtmlDocument || $this->isSafeMode(true)) {
            return;
        }

        $css = (new CssBuilder($this->params))->build();

        if ($css !== '') {
            $css = '/* FG Admin Page Customizer ' . self::VERSION . " */\n" . $css;
            $doc->getWebAssetManager()->addInlineStyle($css, ['name' => 'plg.system.fgadminpagecustom.style']);
        }

        if ((int) $this->params->get('menu_open_first', 0) === 1) {
            $doc->getWebAssetManager()->addInlineScript(
                MenuScript::build(),
                ['name' => 'plg.system.fgadminpagecustom.menu'],
                ['defer' => false]
            );
        }
    }

    /**
     * Server-side body modification (no flicker): swaps the sources of the
     * template's expanded and collapsed header logos.
     */
    public function modifyBody(Event $event): void
    {
        if (!$this->isAdminPage() || $this->isSafeMode()) {
            return;
        }

        $logos = [
            'expanded'  => $this->cleanMediaUrl((string) $this->params->get('logo', '')),
            'collapsed' => $this->cleanMediaUrl((string) $this->params->get('logo_small', '')),
        ];

        if ($logos['expanded'] === '' && $logos['collapsed'] === '') {
            return;
        }

        $app  = $this->getApplication();
        $body = $app->getBody();

        if (!\is_string($body) || $body === '') {
            return;
        }

        $newBody = LogoReplacer::replace($body, $logos);

        if ($newBody !== null) {
            $app->setBody($newBody);
        }
    }

    /**
     * Populates the read-only "Export" field with the plugin's current
     * settings as JSON and applies a previously pasted+saved "Import" JSON
     * onto the displayed field values (the admin still saves once more to
     * persist them).
     *
     * Values are written onto the `data` argument, never via
     * Form::setValue(): the caller binds `$data` onto the form right after
     * this event, which silently overwrites anything set on the Form object.
     */
    public function prepareAdminForm(Event $event): void
    {
        try {
            $this->doPrepareAdminForm($event);
        } catch (\Throwable $e) {
            // Never break the admin page around the form (see
            // plg_system_fgadminlogincustom for the history of this guard).
            return;
        }
    }

    /**
     * @see prepareAdminForm()
     */
    private function doPrepareAdminForm(Event $event): void
    {
        $form = $event->getArgument('form');
        $data = $event->getArgument('data');

        if (!$form instanceof Form || $form->getName() !== 'com_plugins.plugin') {
            return;
        }

        $isDataArray = \is_array($data);
        $element     = $isDataArray ? ($data['element'] ?? '') : ($data->element ?? '');
        $folder      = $isDataArray ? ($data['folder'] ?? '') : ($data->folder ?? '');

        if ($element !== 'fgadminpagecustom' || $folder !== 'system') {
            return;
        }

        $paramsData = $isDataArray ? ($data['params'] ?? null) : ($data->params ?? null);

        $params = ImportExport::paramsToArray($paramsData);

        $exportable = $params;
        unset($exportable['export_json'], $exportable['import_json']);
        ksort($exportable);

        $updates = [
            'export_json' => json_encode(
                $exportable,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            ),
        ];

        $importRaw = trim((string) ($params['import_json'] ?? ''));

        if ($importRaw !== '') {
            $imported = json_decode($importRaw, true);

            if (!\is_array($imported) || json_last_error() !== JSON_ERROR_NONE) {
                $this->getApplication()->enqueueMessage(
                    Text::_('PLG_SYSTEM_FGADMINPAGECUSTOM_IMPORT_INVALID_JSON'),
                    'warning'
                );
            } else {
                unset($imported['export_json'], $imported['import_json']);

                // Only this plugin's own settings are accepted, each one validated.
                $result   = SettingsSanitizer::sanitize($imported);
                $updates += $result['values'];

                $this->getApplication()->enqueueMessage(
                    Text::_('PLG_SYSTEM_FGADMINPAGECUSTOM_IMPORT_APPLIED'),
                    'message'
                );

                if ($result['rejected'] !== []) {
                    $this->getApplication()->enqueueMessage(
                        Text::sprintf('PLG_SYSTEM_FGADMINPAGECUSTOM_IMPORT_SKIPPED', implode(', ', $result['rejected'])),
                        'warning'
                    );
                }
            }

            $updates['import_json'] = '';
        }

        ImportExport::applyValuesToData($data, $updates);
    }

    /**
     * Strips the #joomlaImage adapter fragment from a media field value and
     * returns an absolute URL, or an empty string.
     */
    private function cleanMediaUrl(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        $hashPos = strpos($value, '#');

        if ($hashPos !== false) {
            $value = substr($value, 0, $hashPos);
        }

        if ($value === '') {
            return '';
        }

        if (preg_match('#^(https?:)?//#i', $value) === 1) {
            return $value;
        }

        return Uri::root(true) . '/' . ltrim($value, '/');
    }
}
