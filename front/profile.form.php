<?php

include ('../../../inc/includes.php');

// Gestion des données du formulaire

Session::haveRight('plugin_edittraduction_edittraduction', UPDATE);

$prof = new PluginEdittraductionProfile();

if (isset($_POST['update'])) {
   $prof->update($_POST);
   Html::back();
}

