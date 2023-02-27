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

/**
 * Install hook
 * 
 * @return boolean
 */
function plugin_edittraduction_install() {
    global $DB;

    $migration = new Migration(100);

    if (!$DB->tableExists("glpi_plugin_edittraduction_profiles")) {  
        $query2 = "CREATE TABLE `glpi_plugin_edittraduction_profiles` (
			`id` int(11) NOT NULL default '0',
			`right` char(1) collate utf8_unicode_ci default NULL,
			PRIMARY KEY  (`id`)
        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

        $DB->queryOrDie($query2, $DB->error());

        include_once(GLPI_ROOT."/plugins/edittraduction/inc/profile.class.php");
        PluginEdittraductionProfile::createAdminAccess($_SESSION['glpiactiveprofile']['id']);
        
        foreach (PluginEdittraductionProfile::getRightsGeneral() as $right) {
            PluginEdittraductionProfile::addDefaultProfileInfos($_SESSION['glpiactiveprofile']['id'], [$right['field'] => $right['default']]);
        }

    } else $DB->queryOrDie("ALTER TABLE `glpi_plugin_edittraduction_profiles` ENGINE = InnoDB", $DB->error());

    $migration->executeMigration();
    return true;
}

/**
 * Uninstall hook
 * 
 * @return boolean
 */
function plugin_edittraduction_uninstall() {
    global $DB;

	$tablename = 'glpi_plugin_edittraduction_profiles';
	
	if($DB->tableExists($tablename)) $DB->queryOrDie("DROP TABLE `$tablename`", $DB->error());
    
    foreach (PluginEdittraductionProfile::getRightsGeneral() as $right) {
		$query = "DELETE FROM `glpi_profilerights` WHERE `name` = '".$right['field']."'";
		$DB->query($query);

		if (isset($_SESSION['glpiactiveprofile'][$right['field']])) unset($_SESSION['glpiactiveprofile'][$right['field']]);
    }

    return true;
}
