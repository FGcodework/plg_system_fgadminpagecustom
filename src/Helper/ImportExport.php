<?php

/**
 * @package     plg_system_fgadminpagecustom
 * @license     GNU General Public License version 2 or later
 *
 * Shape-preserving read/write of plugin params inside the form data of the
 * plugin edit form (settings export / import).
 */

declare(strict_types=1);

namespace FG\Plugin\System\AdminPageCustom\Helper;

use Joomla\Registry\Registry;

\defined('_JEXEC') or die;

final class ImportExport
{
    /**
     * Plugin params in any of the shapes Joomla hands over, as a plain array.
     *
     * @param mixed $paramsData
     * @return array<string, mixed>
     */
    public static function paramsToArray($paramsData): array
    {
        if ($paramsData instanceof Registry) {
            return $paramsData->toArray();
        }

        if (\is_string($paramsData)) {
            $decoded = $paramsData === '' ? [] : json_decode($paramsData, true);

            return \is_array($decoded) ? $decoded : [];
        }

        if (\is_object($paramsData)) {
            return (array) $paramsData;
        }

        return \is_array($paramsData) ? $paramsData : [];
    }

    /**
     * Writes each key/value from $values onto $data->params in place,
     * keeping whatever shape `params` already has (Registry, array, object
     * or raw JSON string); see plg_system_fgadminlogincustom for why a shape
     * must never be coerced into another one.
     *
     * @param object|array<string, mixed> $data
     * @param array<string, mixed>        $values
     */
    public static function applyValuesToData(&$data, array $values): void
    {
        $isDataArray = \is_array($data);

        if (!$isDataArray && !\is_object($data)) {
            return;
        }

        $current = $isDataArray ? ($data['params'] ?? null) : ($data->params ?? null);
        $updated = self::mergeIntoParams($current, $values);

        if ($updated === null) {
            return;
        }

        if ($isDataArray) {
            $data['params'] = $updated;
        } else {
            $data->params = $updated;
        }
    }

    /**
     * @param array<string, mixed> $values
     * @return Registry|array<string, mixed>|object|string|null
     */
    public static function mergeIntoParams($current, array $values)
    {
        if ($current instanceof Registry) {
            foreach ($values as $key => $value) {
                $current->set((string) $key, $value);
            }

            return $current;
        }

        if (\is_string($current)) {
            $decoded = $current === '' ? [] : json_decode($current, true);

            if (!\is_array($decoded)) {
                $decoded = [];
            }

            foreach ($values as $key => $value) {
                $decoded[(string) $key] = $value;
            }

            return json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        if (\is_array($current)) {
            foreach ($values as $key => $value) {
                $current[(string) $key] = $value;
            }

            return $current;
        }

        if (\is_object($current)) {
            foreach ($values as $key => $value) {
                $current->{$key} = $value;
            }

            return $current;
        }

        if ($current === null) {
            return $values;
        }

        return null;
    }
}
