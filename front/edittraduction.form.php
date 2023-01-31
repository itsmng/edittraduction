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

include('../../../inc/includes.php');

Session::checkRight('plugin_edittraduction_edittraduction', UPDATE);

$plugin  = new Plugin();
$edittraduction = new PluginEdittraductionEdittraduction();

if ($plugin->isActivated('edittraduction')) {
    
    Html::header(__("Translation editor", "edittraduction"), $_SERVER['PHP_SELF'], 'tools', 'PluginEdittraductionConfig', 'editraduction');

    if (!isset($_SESSION['edittraduction']['language'])) {
        $_SESSION['edittraduction']['language'] = "en_GB";
    } elseif (isset($_POST["update_choix_lang"])) {
        $_SESSION['edittraduction']['language'] = $_POST["language"];
    }

    $output = "";

    foreach (Dropdown::getLanguages() as $key => $value) {
        $result = $edittraduction->getFile($key);
        if (!is_writable($result)) $output = $output . "$key.po, ";
    }

    $output = substr($output, 0, -2);
    $output = $output . ".";

    $lang = $_SESSION['edittraduction']['language'];
    $directory = $edittraduction->getFile($lang);

    if (!is_writable($directory)) {
        echo "<div class='center notifs_setup warning' style='width:40%'>";
        echo "<i class='fa fa-exclamation-triangle fa-5x'></i>";
        echo "<p>". sprintf(__('Translation files are not writable. Please contact your administrator to update file permissions', "edittraduction"), $_SESSION['edittraduction']['language']) ."</p>";
        echo "</div>";
        echo "<br>";
    }
    
    echo "<table style='width:30%' class='tab_cadre' cellpadding='5'><tr class='tab_bg_1'>";
    echo "<tr><th class='center'>". __("Translation editor", "edittraduction") ."</th></tr>\n";
    echo "<tr class='tab_bg_1'><td class='center'>";
    
    if (isset($_POST["update_choix_lang"])) {
        $_SESSION['edittraduction']['language'] = $_POST["language"]; 
        $edittraduction->ShowFormLanguage();
    } else {
        $edittraduction->ShowFormLanguage();  
    }

    if (isset($_POST["submitsave"])) {
        $text = stripcslashes($_POST["textdata"]);
        $lang = $_SESSION['edittraduction']['language'];
        $directory = $edittraduction->getFile($lang);

        if (is_writable($directory)) {
            file_put_contents($directory, html_entity_decode($text));
            $result = $edittraduction->upadteMoFile($directory);

			if($result != "") {
				$message = sprintf(__('The %1$s translation has been modified with success', 'edittraduction'), $_SESSION['edittraduction']['language']);
				Session::addMessageAfterRedirect($message, true, INFO);
			} else {
				$message = sprintf(__('An error occurred while editing the %1$s translation, please contact your administrator', 'edittraduction'), $_SESSION['edittraduction']['language']);
            	Session::addMessageAfterRedirect($message, true, ERROR);
			}
            
            Html::back();
        } else {
			$message = sprintf(__('The %1$s translation file is not writable', 'edittraduction'), $_SESSION['edittraduction']['language']);
            Session::addMessageAfterRedirect($message, true, ERROR);
            Html::back();  
        }
        echo "<br><br>";
    }

    $edittraduction->showFile();

    echo "</td/></tr>";
    echo "</table>";
} else {
   global $CFG_GLPI;
   echo '<div class=\'center\'><br><br><img src=\'' . $CFG_GLPI['root_doc'] . '/pics/warning.png\' alt=\'warning\'><br><br>';
   echo '<b>' . __("The plugin is not activated", "edittraduction") . '</b></div>';
}

Html::footer();