<?php

/**
 * Install hook
 * 
 * @return boolean
 */
function plugin_edittraduction_install() {
    //Do stuff like instanciating database, default values, ...
    global $DB;

    //Instanciate migration with version
    $migration = new Migration(100);

    // Création de la table uniquement lors de la première installation
    if (!$DB->tableExists("glpi_plugin_edittraduction_profiles")) {

        // requete de création de la table    
        $query2 = "CREATE TABLE `glpi_plugin_edittraduction_profiles` (
                    `id` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_profiles (id)',
                    `right` char(1) collate utf8_unicode_ci default NULL,
                    PRIMARY KEY  (`id`)
                    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $DB->queryOrDie($query2, $DB->error());


        //creation du premier accès nécessaire lors de l'installation du plugin
        include_once(GLPI_ROOT."/plugins/edittraduction/inc/profile.class.php");
        PluginEdittraductionProfile::createAdminAccess($_SESSION['glpiactiveprofile']['id']);
        
        foreach (PluginEdittraductionProfile::getRightsGeneral() as $right) {
            PluginEdittraductionProfile::addDefaultProfileInfos($_SESSION['glpiactiveprofile']['id'],
                                        [$right['field'] => $right['default']]);
        }

    } else $DB->queryOrDie("ALTER TABLE `glpi_plugin_edittraduction_profiles` ENGINE = InnoDB", $DB->error());

    //Execute the whole migration
    $migration->executeMigration();
    return true;
}



/**
 * Uninstall hook
 * 
 * @return boolean
 */
function plugin_edittraduction_uninstall() {
    //Do stuff like removing tables, generated files, ... 
    global $DB;

        $tablename = 'glpi_plugin_edittraduction_profiles';
        //Create table only if it doesn't exist yet
        if($DB->tableExists($tablename)) {
            $DB->queryOrDie(
                "DROP TABLE `$tablename`",
                $DB->error()
            );
        }
    

    foreach (PluginEdittraductionProfile::getRightsGeneral() as $right) {
         $query = "DELETE FROM `glpi_profilerights`
                   WHERE `name` = '".$right['field']."'";
         $DB->query($query);

         if (isset($_SESSION['glpiactiveprofile'][$right['field']])) {
            unset($_SESSION['glpiactiveprofile'][$right['field']]);
         }
    }

    return true;
}
