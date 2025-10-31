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

include_once __DIR__ . "/vendor/autoload.php";

/**
 * Init the hooks of the plugin
 *
 * @return void
 */
function plugin_init_edittraduction()
{
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS["csrf_compliant"]["edittraduction"] = true;
    $PLUGIN_HOOKS["change_profile"]["edittraduction"] = [
        "PluginEdittraductionProfile",
        "changeProfile",
    ];
    $PLUGIN_HOOKS["add_css"]["edittraduction"] = ["css/edittraduction.css"];
    $PLUGIN_HOOKS["add_javascript"]["edittraduction"] = [
        "js/edittraduction.js",
    ];

    Plugin::registerClass("PluginEdittraductionProfile", [
        "addtabon" => ["Profile"],
    ]);
    Plugin::registerClass(PluginEdittraductionEdittraduction::class);

    if (Session::haveRight("plugin_edittraduction_edittraduction", UPDATE)) {
        $PLUGIN_HOOKS["menu_toadd"]["edittraduction"] = [
            "tools" => [PluginEdittraductionConfig::class],
        ];
    }
}

/**
 * Get the name and the version of the plugin
 */
function plugin_version_edittraduction()
{
    return [
        "name" => __("Translation editor", "edittraduction"),
        "version" => "2.0.0",
        "author" => "ITSM Dev Team, Djily SARR, Rudy LAURENT",
        "license" => "GPLv2+",
        "homepage" => "https://github.com/itsmng/edittraduction",
        "minGlpiVersion" => "9.5.7",
    ];
}

/**
 * Check if the prerequisites of the plugin are satisfied
 */
function plugin_edittraduction_check_prerequisites()
{
    if (version_compare(ITSM_VERSION, "1.0", "lt")) {
        echo "This plugin requires ITSM >= 1.0";
        return false;
    }

    return true;
}

/**
 *  Check if the config is ok
 */
function plugin_edittraduction_check_config()
{
    return true;
}
