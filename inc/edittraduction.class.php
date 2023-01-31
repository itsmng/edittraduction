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
        
        if(isset($_POST['update_choix_lang'])) {
            $lang = $_POST["language"];
            $path = $this->getFile($lang);
            
            if(is_readable($path) && ($ressource = fopen($path, 'r+b'))) {
                echo "<form action='".$this->getFormURL()."' method='post'>";
                echo "<textarea rows = '40' cols = '160' name='textdata' id='text_area'>";
                        
                while(!feof($ressource)) {
                    $ligne = fgets($ressource);
                    echo $ligne;
                }           

                fclose($ressource);
                echo"</textarea>";
        
                if ($canedit) {
                    echo"<br><br>";      
                    echo"<input type='submit' name='submitsave' class='submit' value='".__("Update")."'>";
                    Html::closeForm();  
                } 
            } else {
                $message = sprintf(__('The %1$s translation file is not writable. Please contact your administrator to update file permissions' , "edittraduction"), $_SESSION['edittraduction']['language']);
                Session::addMessageAfterRedirect($message, true, ERROR);
                Html::back();
            } 
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