<?php


include_once (GLPI_ROOT . "/inc/based_config.php");
include_once (GLPI_CONFIG_DIR . "/config_db.php");


class PluginEdittraductionEdittraduction extends CommonDBTM {

    static $rightname = 'plugin_edittraduction_edittraduction';
    
    static function canCreate() {
        return Session::haveRight('plugin_edittraduction_edittraduction', CREATE);
    }

    static function canView() {
        return Session::haveRight('plugin_edittraduction_edittraduction', READ);
    }

    function ShowFormLanguage(){
       
        if (!Session::haveRight("plugin_edittraduction_edittraduction",UPDATE)) {
            return false;
        }

        $canedit = Session::haveRight("plugin_edittraduction_edittraduction",UPDATE);

        if (isset($_SESSION['edittraduction']['language'])){
           
            $langValue = $_SESSION['edittraduction']['language'];
        }else{
            $langValue = "en_GB";
        }
        
        if ($canedit){
            echo "<form action=".$this->getFormURL()." method='post' name='choixlang'>";
            echo "<p class='center'>";

            Dropdown::showLanguages("language", array('value' => $langValue));
            
            echo "<br>";echo "<br>";
            echo "<input type='submit' name='update_choix_lang' class='submit' value='".__("Edit")."' id='lang'>";
            echo "</p>";

            Html::closeForm();
        }
    }
    
    function showFile() {
        global $CFG_GLPI;

        if (!Session::haveRight("plugin_edittraduction_edittraduction",UPDATE)) {
            return false;
        }

        $canedit = Session::haveRight("plugin_edittraduction_edittraduction",UPDATE);
        
        if(isset($_POST['update_choix_lang'])){
            $lang = $_POST["language"];
            $path = $this->getFile($lang);
            
            if(is_readable($path) && ($ressource = fopen($path, 'r+b'))){
                
                echo "<form action='".$this->getFormURL()."' method='post'>";

                echo "<textarea rows = '40' cols = '160' name='textdata' id='text_area'>";
                        
                while(!feof($ressource))
                {
                    $ligne = fgets($ressource);
                    echo $ligne;
                }           

                fclose($ressource);
                echo"</textarea>";
        
                if ($canedit){
                    echo"<br><br>";      
                    echo"<input type='submit' name='submitsave' class='submit' value='".__("Update")."'>";
                    Html::closeForm();
                    
                } 
            }else{
                $message = sprintf(__('You are not allowed to edit %1$s' , "edittraduction"), $_SESSION['edittraduction']['language']);
                Session::addMessageAfterRedirect(
                    $message,
                    true,
                    INFO
                );
                Html::back();
            } 
   
        }
  
    }

    public function getFile($lang){

        $locale_path = GLPI_ROOT . "/locales/$lang.po";
        return $locale_path;
    }
    
    public function upadteMoFile($path){

        $moFile = substr($path, 0, -3) . ".mo";
        $commande = "msgfmt -o " . $moFile . " -v " . $path;
        $result = exec($commande);

        $translation_cache = Config::getCache('cache_trans');
        $translation_cache->clear(); // Force cache cleaning to prevent usage of outdated cache data
                
        return $result;
    }
}