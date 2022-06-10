<?php

include('../../../inc/includes.php');

Session::checkRight('plugin_edittraduction_edittraduction', UPDATE);

$plugin  = new Plugin();
$edittraduction = new PluginEdittraductionEdittraduction();

if ($plugin->isActivated('edittraduction')) {
    
    Html::header(__("Edit translation", "edittraduction"), $_SERVER['PHP_SELF'], 'tools', 'PluginEdittraductionConfig', 'editraduction');

    if (!isset($_SESSION['edittraduction']['language'])) {
        $_SESSION['edittraduction']['language'] = "en_GB";
    } elseif (isset($_POST["update_choix_lang"])) {
        $_SESSION['edittraduction']['language'] = $_POST["language"];
    }

    $lang = $_SESSION['edittraduction']['language'];
    $directory = $edittraduction->getFile($lang);
    $message = sprintf(__('You are not allowed to edit %1$s' , "edittraduction"), $_SESSION['edittraduction']['language']);
    if (!is_writable($directory)){
        echo "<div class='center notifs_setup warning' style='width:40%'>";
        echo "<i class='fa fa-exclamation-triangle fa-5x'></i>";
        echo "<p>$message</p>";
        echo "<p>". __('Please contact your administrator to update file permissions' , "edittraduction") ."</p>";
        echo "</div>";
        echo "<br>";
    }
    
    echo "<table style='width:30%' class='tab_cadre' cellpadding='5'><tr class='tab_bg_1'>";
    echo "<tr><th class='center'>". __("Edit translation", "edittraduction") ."</th></tr>\n";
    echo "<tr class='tab_bg_1'><td class='center'>";
    
    if (isset($_POST["update_choix_lang"])) {
        $_SESSION['edittraduction']['language'] = $_POST["language"]; 
        $edittraduction->ShowFormLanguage();

    }else{
        $edittraduction->ShowFormLanguage();
        
    }

    
    if (isset($_POST["submitsave"])) {
        $text = stripcslashes($_POST["textdata"]);
        $lang = $_SESSION['edittraduction']['language'];
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