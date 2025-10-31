<?php
/**
 * ---------------------------------------------------------------------
 * ITSM-NG
 * Copyright (C) 2022 ITSM-NG and contributors.
 *
 * https://www.itsm-ng.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of ITSM-NG.
 *
 * ITSM-NG is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * ITSM-NG is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with ITSM-NG. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

include_once GLPI_ROOT . "/inc/based_config.php";
include_once GLPI_CONFIG_DIR . "/config_db.php";

use Gettext\Generator\MoGenerator;
use Gettext\Generator\PoGenerator;
use Gettext\Loader\PoLoader;

class PluginEdittraductionEdittraduction extends CommonDBTM
{
    static $rightname = "plugin_edittraduction_edittraduction";

    private const SESSION_NAMESPACE = "edittraduction";
    private const SESSION_LANGUAGE = "language";
    private const SESSION_CHANGES = "changes";
    private const SESSION_INDEX = "index";
    private const SESSION_ACTIVE = "active_entry";

    public static function canCreate()
    {
        return Session::haveRight(self::$rightname, CREATE);
    }

    public static function canView()
    {
        return Session::haveRight(self::$rightname, READ);
    }

    public function getSelectedLanguage(): string
    {
        $this->normaliseSession();
        if (!isset($_SESSION[self::SESSION_NAMESPACE][self::SESSION_LANGUAGE])) {
            $_SESSION[self::SESSION_NAMESPACE][self::SESSION_LANGUAGE] = "en_GB";
        }

        return $_SESSION[self::SESSION_NAMESPACE][self::SESSION_LANGUAGE];
    }

    public function setSelectedLanguage(string $language): void
    {
        $this->normaliseSession();
        $_SESSION[self::SESSION_NAMESPACE][self::SESSION_LANGUAGE] = $language;
        unset($_SESSION[self::SESSION_NAMESPACE][self::SESSION_ACTIVE]);
    }

    public function getTranslations(string $language): array
    {
        $this->normaliseSession();
        $path = $this->getFile($language);
        $poLoader = new PoLoader();
        $translations = $poLoader->loadFile($path);

        $items = [];
        $index = [];

        foreach ($translations as $translation) {
            if ($translation->getOriginal() === "") {
                continue;
            }

            $entryId = $this->buildEntryId($translation);
            $item = [
                "id" => $entryId,
                "original" => $translation->getOriginal(),
                "translation" => (string) $translation->getTranslation(),
                "context" => $translation->getContext() ?? "",
                "plural" => $translation->getPlural() ?? "",
                "plural_translations" => $translation->getPluralTranslations(),
            ];

            $items[] = $item;
            $index[$entryId] = [
                "context" => $item["context"],
                "original" => $item["original"],
                "plural" => $item["plural"],
                "translation" => $item["translation"],
            ];
        }

        $_SESSION[self::SESSION_NAMESPACE][self::SESSION_INDEX][$language] = $index;

        return $items;
    }

    public function getEntryMetadata(string $language, string $entryId): ?array
    {
        $this->normaliseSession();
        $this->ensureIndex($language);

        if (!isset($_SESSION[self::SESSION_NAMESPACE][self::SESSION_INDEX][$language][$entryId])) {
            return null;
        }

        return $_SESSION[self::SESSION_NAMESPACE][self::SESSION_INDEX][$language][$entryId];
    }

    public function stageChange(string $language, string $entryId, string $newValue): void
    {
        $this->normaliseSession();
        $this->ensureIndex($language);

        $metadata = $this->getEntryMetadata($language, $entryId);
        if ($metadata === null) {
            throw new InvalidArgumentException("Unknown translation entry");
        }

        $newValue = Html::cleanPostForTextArea($newValue);
        $newValue = is_string($newValue) ? trim($newValue) : "";

        $changes =& $_SESSION[self::SESSION_NAMESPACE][self::SESSION_CHANGES][$language];
        if (!isset($changes)) {
            $changes = [];
        }

        if ($newValue === $metadata["translation"]) {
            unset($changes[$entryId]);
            $this->setActiveEntry($entryId);
            return;
        }

        $changes[$entryId] = [
            "context" => $metadata["context"],
            "original" => $metadata["original"],
            "plural" => $metadata["plural"],
            "previous" => $metadata["translation"],
            "updated" => $newValue,
        ];

        $_SESSION[self::SESSION_NAMESPACE][self::SESSION_INDEX][$language][$entryId]["translation"] = $newValue;
        $this->setActiveEntry($entryId);
    }

    public function discardChange(string $language, string $entryId): void
    {
        $this->normaliseSession();
        $changes =& $_SESSION[self::SESSION_NAMESPACE][self::SESSION_CHANGES][$language];
        if (isset($changes[$entryId])) {
            unset($changes[$entryId]);
            $this->setActiveEntry($entryId);
        }
    }

    public function clearChanges(string $language): void
    {
        $this->normaliseSession();
        unset($_SESSION[self::SESSION_NAMESPACE][self::SESSION_CHANGES][$language]);
    }

    public function getStagedChanges(string $language): array
    {
        $this->normaliseSession();
        return $_SESSION[self::SESSION_NAMESPACE][self::SESSION_CHANGES][$language] ?? [];
    }

    public function setActiveEntry(?string $entryId): void
    {
        $this->normaliseSession();
        if ($entryId === null) {
            unset($_SESSION[self::SESSION_NAMESPACE][self::SESSION_ACTIVE]);
            return;
        }

        $_SESSION[self::SESSION_NAMESPACE][self::SESSION_ACTIVE] = $entryId;
    }

    public function getActiveEntry(): ?string
    {
        $this->normaliseSession();
        return $_SESSION[self::SESSION_NAMESPACE][self::SESSION_ACTIVE] ?? null;
    }

    public function applyChanges(string $language): void
    {
        $this->normaliseSession();
        $changes = $this->getStagedChanges($language);

        if (empty($changes)) {
            return;
        }

        $path = $this->getFile($language);
        if (!is_writable($path)) {
            throw new RuntimeException(
                sprintf(
                    __("The %1$s translation file is not writable", "edittraduction"),
                    $language
                )
            );
        }

        $poLoader = new PoLoader();
        $translations = $poLoader->loadFile($path);

        foreach ($changes as $change) {
            $entry = $this->findTranslation($translations, $change["context"], $change["original"], $change["plural"]);
            if ($entry === null) {
                continue;
            }
            $entry->translate($change["updated"]);
        }

        $poGenerator = new PoGenerator();
        $poGenerator->generateFile($translations, $path);

        $this->updateMoFile($path);

        unset($_SESSION[self::SESSION_NAMESPACE][self::SESSION_CHANGES][$language]);
    }

    public function getFile($lang)
    {
        return GLPI_ROOT . "/locales/$lang.po";
    }

    public function updateMoFile(string $path): bool
    {
        try {
            $poLoader = new PoLoader();
            $translations = $poLoader->loadFile($path);

            $moFile = substr($path, 0, -3) . ".mo";
            $moGenerator = new MoGenerator();
            $moGenerator->generateFile($translations, $moFile);

            $translation_cache = Config::getCache("cache_trans");
            $translation_cache->clear();

            return true;
        } catch (Exception $e) {
            error_log("Failed to compile MO file: " . $e->getMessage());
            return false;
        }
    }

    public function upadteMoFile($path)
    {
        return $this->updateMoFile($path);
    }

    private function normaliseSession(): void
    {
        if (!isset($_SESSION[self::SESSION_NAMESPACE])) {
            $_SESSION[self::SESSION_NAMESPACE] = [];
        }

        if (!isset($_SESSION[self::SESSION_NAMESPACE][self::SESSION_CHANGES])) {
            $_SESSION[self::SESSION_NAMESPACE][self::SESSION_CHANGES] = [];
        }

        if (!isset($_SESSION[self::SESSION_NAMESPACE][self::SESSION_INDEX])) {
            $_SESSION[self::SESSION_NAMESPACE][self::SESSION_INDEX] = [];
        }

        $changes = &$_SESSION[self::SESSION_NAMESPACE][self::SESSION_CHANGES];
        if (!empty($changes)) {
            $firstLanguage = reset($changes);
            if (!is_array($firstLanguage)) {
                $_SESSION[self::SESSION_NAMESPACE][self::SESSION_CHANGES] = [];
            } else {
                $firstEntry = reset($firstLanguage);
                if (!is_array($firstEntry) || !array_key_exists("updated", $firstEntry)) {
                    $_SESSION[self::SESSION_NAMESPACE][self::SESSION_CHANGES] = [];
                }
            }
        }
    }

    private function ensureIndex(string $language): void
    {
        if (!isset($_SESSION[self::SESSION_NAMESPACE][self::SESSION_INDEX][$language])) {
            $this->getTranslations($language);
        }
    }

    private function buildEntryId($translation): string
    {
        $context = $translation->getContext() ?? "";
        $original = $translation->getOriginal() ?? "";
        $plural = $translation->getPlural() ?? "";

        return sha1($context . "\x1f" . $original . "\x1f" . $plural);
    }

    private function findTranslation($collection, string $context, string $original, string $plural)
    {
        $entry = $collection->find($context === "" ? null : $context, $original);

        if ($entry !== null) {
            return $entry;
        }

        foreach ($collection as $translation) {
            if (
                ($translation->getContext() ?? "") === $context &&
                $translation->getOriginal() === $original &&
                ($translation->getPlural() ?? "") === $plural
            ) {
                return $translation;
            }
        }

        return null;
    }
}