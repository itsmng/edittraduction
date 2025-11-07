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

include "../../../inc/includes.php";

Session::checkRight("plugin_edittraduction_edittraduction", UPDATE);

$plugin = new Plugin();
$edittraduction = new PluginEdittraductionEdittraduction();

if ($plugin->isActivated("edittraduction")) {
    Html::header(
        __("Translation editor", "edittraduction"),
        $_SERVER["PHP_SELF"],
        "tools",
        "PluginEdittraductionConfig",
        "edittraduction",
    );

    $language = $edittraduction->getSelectedLanguage();

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $action = $_POST["action"] ?? "";
        switch ($action) {
            case "select-language":
                $newLanguage = $_POST["language"] ?? "";
                if ($newLanguage !== "") {
                    $edittraduction->setSelectedLanguage($newLanguage);
                    $language = $newLanguage;
                }
                Html::redirect($_SERVER["REQUEST_URI"]);
                exit();
            case "stage-change":
                $entryId = $_POST["entry_id"] ?? "";
                $newValue = $_POST["translated_value"] ?? "";
                if ($entryId !== "") {
                    try {
                        $edittraduction->stageChange(
                            $language,
                            $entryId,
                            $newValue,
                        );
                        $message = sprintf(
                            __(
                                'The %1$s translation has been modified with success',
                                "edittraduction",
                            ),
                            Dropdown::getLanguageName($language),
                        );
                        Session::addMessageAfterRedirect($message, true, INFO);
                    } catch (Throwable $exception) {
                        Session::addMessageAfterRedirect(
                            $exception->getMessage(),
                            true,
                            ERROR,
                        );
                    }
                }
                Html::redirect($_SERVER["REQUEST_URI"]);
                exit();
            case "discard-change":
                $entryId = $_POST["entry_id"] ?? "";
                if ($entryId !== "") {
                    $edittraduction->discardChange($language, $entryId);
                    Session::addMessageAfterRedirect(
                        __("Pending change removed", "edittraduction"),
                        true,
                        INFO,
                    );
                }
                Html::redirect($_SERVER["REQUEST_URI"]);
                exit();
            case "discard-all":
                $edittraduction->clearChanges($language);
                Session::addMessageAfterRedirect(
                    __("All pending changes cleared", "edittraduction"),
                    true,
                    INFO,
                );
                Html::redirect($_SERVER["REQUEST_URI"]);
                exit();
            case "commit-changes":
                try {
                    $edittraduction->applyChanges($language);
                    $message = sprintf(
                        __(
                            'The %1$s translation has been modified with success',
                            "edittraduction",
                        ),
                        Dropdown::getLanguageName($language),
                    );
                    Session::addMessageAfterRedirect($message, true, INFO);
                } catch (Throwable $exception) {
                    Session::addMessageAfterRedirect(
                        $exception->getMessage(),
                        true,
                        ERROR,
                    );
                }
                Html::redirect($_SERVER["REQUEST_URI"]);
                exit();
        }
    }

    $language = $edittraduction->getSelectedLanguage();
    $languageChoices = Dropdown::getLanguages();
    $translations = $edittraduction->getTranslations($language);
    $stagedChanges = $edittraduction->getStagedChanges($language);
    $filePath = $edittraduction->getFile($language);
    $isWritable = is_writable($filePath);

    $clientTranslations = [];
    foreach ($translations as $translation) {
        $id = $translation["id"];
        $clientTranslations[] = [
            "id" => $id,
            "original" => $translation["original"],
            "translation" =>
                $stagedChanges[$id]["updated"] ?? $translation["translation"],
            "context" => $translation["context"],
        ];
    }

    $translationIndex = [];
    foreach ($clientTranslations as $item) {
        $translationIndex[$item["id"]] = true;
    }

    $activeId = $edittraduction->getActiveEntry();
    if ($activeId !== null && !isset($translationIndex[$activeId])) {
        $activeId = null;
    }
    if ($activeId === null && !empty($stagedChanges)) {
        $activeId = array_key_first($stagedChanges);
    }
    if ($activeId === null && !empty($clientTranslations)) {
        $activeId = $clientTranslations[0]["id"];
    }
    if ($activeId !== null) {
        $edittraduction->setActiveEntry($activeId);
    }

    $clientPayload = [
        "translations" => $clientTranslations,
        "staged" => array_keys($stagedChanges),
        "activeId" => $activeId,
        "labels" => [
            "pending" => __("Pending change", "edittraduction"),
            "noResults" => __(
                "No translations match your search",
                "edittraduction",
            ),
            "context" => __("Context", "edittraduction"),
        ],
    ];

    $clientPayloadJson = json_encode($clientPayload, JSON_UNESCAPED_UNICODE);
    if ($clientPayloadJson === false) {
        $clientPayloadJson = "{}";
    }

    if (!$isWritable) {
        echo "<div class='et-alert et-alert-warning'>";
        echo "<i class='fa fa-exclamation-triangle' aria-hidden='true'></i>";
        echo "<span>" .
            sprintf(
                __(
                    "Translation files are not writable. Please contact your administrator to update file permissions",
                    "edittraduction",
                ),
                $language,
            ) .
            "</span>";
        echo "</div>";
    }

    echo "<div class='et-layout'>";
    echo "<div class='et-main'>";

    echo "<section class='et-panel et-language-panel'>";
    echo "<h2>" . __("Select language", "edittraduction") . "</h2>";
    echo "<form method='post' class='et-language-form'>";
    echo "<input type='hidden' name='action' value='select-language'>";
    echo "<div class='et-field'>";
    echo "<label for='et-language-select'>" .
        __("Language", "edittraduction") .
        "</label>";
    echo "<select id='et-language-select' name='language' class='et-select'>";
    foreach ($languageChoices as $code => $label) {
        $selected = $code === $language ? " selected" : "";
        echo "<option value='" .
            Html::cleanInputText($code) .
            "'" .
            $selected .
            ">" .
            Html::entities_deep($label) .
            "</option>";
    }
    echo "</select>";
    echo "</div>";
    echo "<div class='et-actions'>";
    echo "<button type='submit' class='btn btn-secondary'>" .
        __("Switch", "edittraduction") .
        "</button>";
    echo "</div>";
    Html::closeForm();
    echo "</section>";

    echo "<section class='et-panel et-editor-panel'>";
    echo "<div class='et-editor-header'>";
    echo "<div class='et-search'>";
    echo "<label for='et-search-input'>" .
        __("Search translations", "edittraduction") .
        "</label>";
    echo "<div class='et-search-control'>";
    echo "<input type='search' id='et-search-input' class='et-input' role='combobox' aria-autocomplete='list' aria-expanded='false' aria-controls='et-search-popover' placeholder='" .
        __("Search…", "edittraduction") .
        "'>";
    echo "<div class='et-search-popover' id='et-search-popover' role='listbox' hidden></div>";
    echo "</div>";
    echo "</div>";
    echo "<div class='et-view-toggle'>";
    echo "<button type='button' class='et-toggle-btn et-toggle-btn--active' data-et-view='form'>" .
        __("Editor", "edittraduction") .
        "</button>";
    echo "<button type='button' class='et-toggle-btn' data-et-view='list'>" .
        __("List view", "edittraduction") .
        "</button>";
    echo "</div>";
    echo "</div>";

    echo "<div class='et-editor-views'>";
    echo "<div class='et-view et-view--form et-view-active' data-et-view='form'>";
    echo "<form method='post' class='et-edit-form'>";
    echo "<input type='hidden' name='action' value='stage-change'>";
    echo "<input type='hidden' name='entry_id' id='et-entry-id'>";
    echo "<div class='et-field'>";
    echo "<label for='et-translation-field'>" .
        __("Translation", "edittraduction") .
        "</label>";
    echo "<textarea id='et-translation-field' class='et-textarea' name='translated_value'></textarea>";
    echo "</div>";
    echo "<div class='et-field'>";
    echo "<label for='et-original-field'>" .
        __("Original text", "edittraduction") .
        "</label>";
    echo "<textarea id='et-original-field' class='et-textarea' readonly></textarea>";
    echo "</div>";
    echo "<div class='et-actions'>";
    echo "<button type='submit' class='btn btn-primary'>" .
        __("Stage change", "edittraduction") .
        "</button>";
    echo "</div>";
    Html::closeForm();
    echo "</div>";

    echo "<div class='et-view et-view--list' data-et-view='list'>";
    echo "<div class='et-list' id='et-translations-list' role='list'></div>";
    echo "</div>";
    echo "</div>";

    echo "</section>";

    echo "</div>";

    echo "<section class='et-panel et-staged-panel'>";
    echo "<div class='et-staged-header'>";
    echo "<h2>" . __("Pending changes", "edittraduction") . "</h2>";
    echo "</div>";
    if (!empty($stagedChanges)) {
        echo "<div class='et-staged-list'>";
        foreach ($stagedChanges as $id => $change) {
            $original = Html::entities_deep($change["original"]);
            $previous = Html::entities_deep($change["previous"] ?? "");
            $updated = Html::entities_deep($change["updated"]);
            $context = Html::entities_deep($change["context"] ?? "");

            echo "<article class='et-change' data-entry='" .
                Html::cleanInputText($id) .
                "'>";
            echo "<div class='et-change-main'>";
            echo "<div class='et-change-texts'>";
            echo "<span class='et-change-original-label'>" .
                $original .
                "</span>";
            if ($context !== "") {
                echo "<span class='et-change-context'>" .
                    __("Context", "edittraduction") .
                    ": " .
                    $context .
                    "</span>";
            }
            echo "<div class='et-change-values'>";
            echo "<span class='et-change-value et-change-value--previous'>" .
                $previous .
                "</span>";
            echo "<span class='et-change-separator' aria-hidden='true'>→</span>";
            echo "<span class='et-change-value et-change-value--updated'>" .
                $updated .
                "</span>";
            echo "</div>";
            echo "</div>";
            echo "<form method='post' class='et-inline-form' aria-label='" .
                __("Remove pending change", "edittraduction") .
                "'>";
            echo "<input type='hidden' name='action' value='discard-change'>";
            echo "<input type='hidden' name='entry_id' value='" .
                Html::cleanInputText($id) .
                "'>";
            echo "<button type='submit' class='et-icon-button' title='" .
                __("Remove", "edittraduction") .
                "' aria-label='" .
                __("Remove", "edittraduction") .
                "'>";
            echo "<i class='fa fa-times' aria-hidden='true'></i>";
            echo "</button>";
            Html::closeForm();
            echo "</div>";
            echo "</article>";
        }
        echo "</div>";
        echo "<div class='et-staged-actions'>";
        echo "<form method='post'>";
        echo "<input type='hidden' name='action' value='commit-changes'>";
        echo "<button type='submit' class='btn btn-success'>" .
            __("Apply changes", "edittraduction") .
            "</button>";
        Html::closeForm();
        echo "<form method='post'>";
        echo "<input type='hidden' name='action' value='discard-all'>";
        echo "<button type='submit' class='btn btn-outline-secondary'>" .
            __("Discard all", "edittraduction") .
            "</button>";
        Html::closeForm();
        echo "</div>";
    } else {
        echo "<p class='et-empty'>" .
            __("No pending changes", "edittraduction") .
            "</p>";
    }
    echo "</section>";

    echo "</div>";

    echo Html::scriptBlock(
        "window.ET_TRANSLATION_PAYLOAD = $clientPayloadJson;",
    );
} else {
    global $CFG_GLPI;
    echo '<div class=\'center\'><br><br><img src=\'' .
        $CFG_GLPI["root_doc"] .
        '/pics/warning.png\' alt=\'warning\'><br><br>';
    echo "<b>" .
        __("The plugin is not activated", "edittraduction") .
        "</b></div>";
}

Html::footer();
