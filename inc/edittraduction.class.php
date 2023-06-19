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

include_once (GLPI_ROOT . "/inc/based_config.php");
include_once (GLPI_CONFIG_DIR . "/config_db.php");

class PluginEdittraductionEdittraduction extends CommonDBTM {

    static $rightname = 'plugin_edittraduction_edittraduction';
        
    /**
     * canCreate
     *
     * @return boolean
     */
    static function canCreate() {
        return Session::haveRight('plugin_edittraduction_edittraduction', CREATE);
    }
    
    /**
     * canView
     *
     * @return boolean
     */
    static function canView() {
        return Session::haveRight('plugin_edittraduction_edittraduction', READ);
    }
    
    /**
     * ShowFormLanguage
     *
     * @return void
     */
    function ShowFormLanguage(){
       
        if (!Session::haveRight("plugin_edittraduction_edittraduction",UPDATE)) return false;

        $canedit = Session::haveRight("plugin_edittraduction_edittraduction",UPDATE);

        if (isset($_SESSION['edittraduction']['language'])) {
            $langValue = $_SESSION['edittraduction']['language'];
        } else {
            $langValue = "en_GB";
        }
        
        if ($canedit) {
            echo "<form action=".$this->getFormURL()." method='post' name='choixlang'>";
            echo "<p class='center'>";

            Dropdown::showLanguages("language", array('value' => $langValue));
            
            echo "<br>";echo "<br>";
            echo "<input type='submit' name='update_choix_lang' class='submit' value='".__("Edit")."' id='lang'>";
            echo "<br/><br/>";
            echo "<input type='submit' name='PreUpload' class='submit' value='".__("Update")."' id='lang'>";
            echo "</p>";

            Html::closeForm();
        }
    }
        
    /**
     * showFile
     *
     * @return void
     */
    function showFile() {

        if (!Session::haveRight("plugin_edittraduction_edittraduction",UPDATE)) return false;

        $canedit = Session::haveRight("plugin_edittraduction_edittraduction",UPDATE);
        
        if(isset($_POST['update_choix_lang']) || isset($_POST['submitsave'])) {
            if (isset($_POST["language"])) {
                $lang = $_POST["language"];
            }else {
                $lang = $_SESSION['edittraduction']["language"];
            }
            $path = $this->getFile($lang);
            $init = [];
            
            if(is_readable($path) && ($ressource = fopen($path, 'r+b'))) {
                echo "<form action='".$this->getFormURL()."' method='post'>";
                $i = 1;
                        
                while(!feof($ressource)) {
                    $ligne = fgets($ressource);
                    if (strncmp($ligne, "msgstr ", 7) === 0) {
                        if (preg_match("/\"(.*?)\"/", $ligne, $matches)) {
                            if ($matches[1] != "") {
                                $init[$i] = $matches[1];
                            }
                        }
                    }
                    $i++;
                }

                $_SESSION['edittraduction']['init'] = $init;

                fclose($ressource);
        
                if ($canedit) {
                    Dropdown::showFromArray('msg', $init, []);
                    echo "<input type='text' name='new' class='input'>";
                    echo"<br><br>";      
                    echo"<input type='submit' name='submitsave' class='submit' value='".__("Update")."'>";
                    echo "<br/><br/>";
                    echo"<input type='submit' name='backsave' class='submit' value='".__("Back")."'>";
                    Html::closeForm();  
                } 
            } else {
                $message = sprintf(__('The %1$s translation file is not writable. Please contact your administrator to update file permissions' , "edittraduction"), $_SESSION['edittraduction']['language']);
                Session::addMessageAfterRedirect($message, true, ERROR);
                Html::back();
            } 
        }
    }

    public function showBeforeUpdate(){
        if (is_numeric($_POST['PreUpload'])) {
            unset($_SESSION['edittraduction']['changes'][$_POST['PreUpload']]);
        }
        echo "<form action='".$this->getFormURL()."' method='post'>";
        if (isset($_SESSION['edittraduction']['changes']) && !empty($_SESSION['edittraduction']['changes'])) {

            echo "<table class='tab_cadre'>";
            echo "<tr>";
            echo "<th>" . __("Origin", 'edittraduction') . "</th>";
            echo "<th>" . __("New value", 'edittraduction') . "</th>";
            echo "<th>" . __("Action", 'edittraduction') . "</th>";
            echo "</tr>";

            foreach ($_SESSION['edittraduction']['changes'] as $key => $value) {
                echo "<tr>";
                echo "<td>" . $_SESSION['edittraduction']['init'][$key] . "</td>";
                echo "<td>" . $value . "</td>";
                echo "<td>" . "<button type='submit' name='PreUpload' class='vsubmit' value='".$key."'>"."<i class='fa fa-times'"."</button>" . "</td>";
                echo "</tr>";
            }

            echo "</table>";
            echo "<br/><br/>";
            echo"<input type='submit' name='uploadSave' class='submit' value='".__("Update")."'>";
            echo "<br/><br/>";
            echo"<input type='submit' name='backsave' class='submit' value='".__("Back")."'>";
            Html::closeForm();  
        } else {
            echo __('No data to update', 'edittraduction');
            echo "<br/><br/>";
            echo"<input type='submit' name='backsave' class='submit' value='".__("Back")."'>";
            Html::closeForm();  

        }
    }
    
    /**
     * getFile
     *
     * @param  string $lang
     * @return string
     */
    public function getFile($lang) {
        $locale_path = GLPI_ROOT . "/locales/$lang.po";
        return $locale_path;
    }
        
    /**
     * upadteMoFile
     *
     * @param  string $path
     * @return string|boolean
     */
    public function upadteMoFile($path) {
        $moFile = substr($path, 0, -3) . ".mo";
        $commande = "msgfmt -o " . $moFile . " -v " . $path;
        $result = exec($commande);

        $translation_cache = Config::getCache('cache_trans');
        $translation_cache->clear();
                
        return $result;
    }
}