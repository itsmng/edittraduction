<?php

include('../../../inc/includes.php');

Session::checkRight('plugin_edittraduction_edittraduction', UPDATE);

$plugin  = new Plugin();
$edittraduction = new PluginEdittraductionEdittraduction();

if ($plugin->isActivated('edittraduction')) {
    
    Html::header(__("Edit traduction", "edittraduction"), $_SERVER['PHP_SELF'], 'tools', 'PluginEdittraductionConfig', 'editraduction');

    echo "<table class='tab_cadre' cellpadding='5'><tr class='tab_bg_1'>";
    echo "<tr><th>". __("Edit traduction", "edittraduction") ."</th></tr>\n";
    echo "<tr class='tab_bg_1'><td class='center'>";
    
    //var_dump($_SESSION);
    if (isset($_POST["update_choix_lang"])) {
        $_SESSION['edittraduction']['language'] = $_POST["language"]; 
        $edittraduction->ShowFormLanguage();
        //$edittraduction->ShowFormSearch();

    }else{
        $edittraduction->ShowFormLanguage();
        
    }

    if (isset($_POST["submitsave"])) {
        //var_dump($_SESSION['edittraduction']['language']);
        $lang = $_SESSION['edittraduction']['language'];
        $text = stripcslashes($_POST["textdata"]);
        $directory = $edittraduction->getFile($lang);
        if (is_writable($directory)){
            file_put_contents($directory, html_entity_decode($text));
            $edittraduction->upadteMoFile($directory);
            $message = sprintf(__('the treduction %1$s has been modified'), $_SESSION['edittraduction']['language']);
            Session::addMessageAfterRedirect(
                $message,
                true,
                INFO
            );
            Html::back();
            
        }else{
            $message = sprintf(__('You don\'t have access denied to modify %1$s' , "edittraduction"), $_SESSION['edittraduction']['language']);
            Session::addMessageAfterRedirect(
                $message,
                true,
                INFO
            );
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
   echo '<b>' . __("Enable your plugin", "edittraduction") . '</b></div>';
}

Html::footer();