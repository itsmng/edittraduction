<?php


/**
 * Init the hooks of the plugin - Needed
 * 
 * @return void
 */
function plugin_init_edittraduction() {
        // déclarer les variables globales dont on a besoin 
        global $PLUGIN_HOOKS;

        //required!
        //Un plugin CSRF_COMPLIANT est un plugin qui contient des formulaires fermés avec Html::closeForm() et qui ne passe aucune variable de type GET
        $PLUGIN_HOOKS['csrf_compliant']['edittraduction'] = true;
        // Fonction pour changer mon profil
        $PLUGIN_HOOKS['change_profile']['edittraduction'] = array('PluginEdittraductionProfile','changeProfile');
        
        //$PLUGIN_HOOKS['add_javascript']['edittraduction'] = 'edittraduction.js';
        
        // Gérer les droits dans les profils du coeur 
        Plugin::registerClass('PluginEdittraductionProfile', array('addtabon' => array('Profile')));
        Plugin::registerClass(PluginEdittraductionEdittraduction::class);
        
        if (Session::haveRight("profile", UPDATE)) {
            $PLUGIN_HOOKS['menu_toadd']['edittraduction'] = [
                'tools' => array(PluginEdittraductionConfig::class)
            ];
        }

}


/**
* Get the name and the version of the plugin - Needed
*/
function plugin_version_edittraduction() {
return array('name'           => __("Edit traduction", "edittraduction"),
                'version'        => '1.0.0',
                'author'         => 'ITSM Dev Team, Djily SARR',
                'license'        => 'GPLv2+',
                'homepage'       => '',
                'minGlpiVersion' => '9.5.7'
              );
}




/**
 * Check if the prerequisites of the plugin are satisfied - Needed
 */
function plugin_edittraduction_check_prerequisites() {
 
    // Check that the GLPI version is compatible
    if (version_compare(GLPI_VERSION, '9.5.7', 'lt')) {
        echo "This plugin Requires GLPI >= 9.5.7";
        return false;
    }
 
    return true;
}


/**
 *  Check if the config is ok - Needed
 */
function plugin_edittraduction_check_config() {
    return true;
}
 
