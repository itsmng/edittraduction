<?php


class PluginEdittraductionProfile extends CommonDBTM {
      
   static function canCreate() {

      if (isset($_SESSION["profile"])) {
        return ($_SESSION["profile"]['edittraduction'] == 'w');
      }
      return false;
   }

   static function canView() {

      if (isset($_SESSION["profile"])) {
        return ($_SESSION["profile"]['edittraduction'] == 'w'
                || $_SESSION["profile"]['edittraduction'] == 'r');
      }
      return false;
   }

   static function createAdminAccess($ID) {

      $myProf = new self();
    // si le profile n'existe pas déjà dans la table profile de mon plugin
      if (!$myProf->getFromDB($ID)) {
    // ajouter un champ dans la table comprenant l'ID du profil d la personne connecté et le droit d'écriture
         $myProf->add(array('id' => $ID,
                            'right'       => 'w'));
      }
   }


   /**
    * addDefaultProfileInfos
    * @param $profiles_id
    * @param $rights
   **/
   static function addDefaultProfileInfos($profiles_id, $rights) {
      $profileRight = new ProfileRight();
      foreach ($rights as $right => $value) {
         if (!countElementsInTable('glpi_profilerights',
                                   ['profiles_id' => $profiles_id, 'name' => $right])) {
            $myright['profiles_id'] = $profiles_id;
            $myright['name']        = $right;
            $myright['rights']      = $value;
            $profileRight->add($myright);
            //Add right to the current session
            $_SESSION['glpiactiveprofile'][$right] = $value;
         }
      }
   }

   static function changeProfile() {

      $prof = new self();
      if ($prof->getFromDB($_SESSION['glpiactiveprofile']['id'])) {
         $_SESSION["glpi_plugin_edittraduction_profile"] = $prof->fields;
      } else {
         unset($_SESSION["glpi_plugin_edittraduction_profile"]);
      }
   }


   // Définition du nom de l'objet dans Profile du coeur
   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      
      if (Session::haveRight("profile", UPDATE)) 
      {
         if ($item->getType() == 'Profile') {
            return __('Edit traduction', 'edittraduction');
         }
      }
      return '';
   }

   // Définition du contenu de l'onglet 
   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
        global $CFG_GLPI;

        if ($item->getType() == 'Profile') {
            
            $ID = $item->getID();
            $prof = new self();
            
            //In case there's no right for this profile, create it
            foreach (self::getRightsGeneral() as $right) {
               self::addDefaultProfileInfos($ID, [$right['field'] => 0]);
            }

            // j'affiche le formulaire
            $prof->showForm($ID);
        }
        return true;
   }

   static function getRightsGeneral()
   {
      $rights = [
          ['itemtype'  => 'PluginEdittraductionProfile',
                'label'     => 'Edittraduction_label',
                'field'     => 'plugin_edittraduction_edittraduction',
                'rights'    =>  [CREATE  => __('Create'),
                                      READ    => __('Read'),
                                      UPDATE    => __('Update'),
                                      PURGE   => ['short' => __('Purge'),
                                      'long' => _x('button', 'Delete permanently')]],
                'default'   => 23]];
      return $rights;
   }


      /**
   * Show profile form
   *
   * @param $items_id integer id of the profile
   * @param $target value url of target
   *
   * @return nothing
   **/
   function showForm($profiles_id = 0, $openform = true, $closeform = true) {
      global $DB, $CFG_GLPI;
      

      if (!Session::haveRight("profile",READ)) {
         return false;
      }
      
      echo "<div class='firstbloc'>";
      if (($canedit = Session::haveRight('profile', UPDATE))
          && $openform) {
         $profile = new Profile();
        
         echo "<form method='post' action='".$profile->getFormURL()."'>";
      }
    
      $profile = new Profile();
      $profile->getFromDB($profiles_id);
      if ($profile->getField('interface') == 'central') {
         $rights = $this->getRightsGeneral();
         $profile->displayRightsChoiceMatrix($rights, ['default_class' => 'tab_bg_2',
                                                         'title'         => __('General')]);
      }

      if ($canedit && $closeform) {
         echo "<div class='center'>";
         echo Html::hidden('id', ['value' => $profiles_id]);
         echo Html::submit(_sx('button', 'Save'), ['name' => 'update']);
         echo "</div>\n";
         Html::closeForm();
      }
      echo "</div>";
   }










   //Refaite avec la façon de dumpentity

/*


   static $rightname = "profile";
   /**
    * @see inc/CommonGLPI::getTabNameForItem()
    *
    * @param CommonGLPI $item
    * @param int        $withtemplate
    *
    * @return string|translated
    */
/*    function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

        if ($item->getType() == 'Profile' && $item->getField('interface') != 'helpdesk') {
            return __('Editer trad', 'edittraduction');
        }
        return '';
   }



   /**
    * @see inc/CommonGLPI::displayTabContentForItem()
    *
    * @param CommonGLPI $item
    * @param int        $tabnum
    * @param int        $withtemplate
    *
    * @return bool|true
    */
/*    static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

        if ($item->getType() == 'Profile') {
            $ID   = $item->getID();
            $prof = new self();

            self::addDefaultProfileInfos($ID, [
                    'plugin_edittraduction_edittraduction'   => 0
                ]
            );
            $prof->showForm($ID);
        }
        return true;
    }

    /**
    * @param $ID
    */
/*    static function createFirstAccess($ID) {
        //85
        self::addDefaultProfileInfos($ID, [
                'plugin_edittraduction_edittraduction'   => READ + CREATE + UPDATE + PURGE,
                true
            ]
        );
    }


     /**
    * @param      $profiles_id
    * @param      $rights
    * @param bool $drop_existing
    *
    * @internal param $profile
    */
 /*   static function addDefaultProfileInfos($profiles_id, $rights, $drop_existing = false) {

        $profileRight = new ProfileRight();
        $dbu = new DbUtils();
        foreach ($rights as $right => $value) {
            if ($dbu->countElementsInTable('glpi_profilerights',["profiles_id" => $profiles_id, "name" => $right]) && $drop_existing) {
                $profileRight->deleteByCriteria(['profiles_id' => $profiles_id, 'name' => $right]);
            }
            if (!$dbu->countElementsInTable('glpi_profilerights', ["profiles_id" => $profiles_id, "name" => $right])) {
                $myright['profiles_id'] = $profiles_id;
                $myright['name']        = $right;
                $myright['rights']      = $value;
                $profileRight->add($myright);

                //Add right to the current session
                $_SESSION['glpiactiveprofile'][$right] = $value;
            }
        }
    }


    /**
    * @return array
    */
/*    static function getAllRights() {
        $rights = [[
            
                'itemtype' => 'PluginEdittraductionProfile',
                'label'    => _n('Model', 'Models', 2),
                'field'    => 'plugin_edittraduction_edittraduction'
            
        ]];
        return $rights;
    }

    function showForm($profiles_id = 0, $openform = true, $closeform = true) {
        global $DB, $CFG_GLPI;

        $profile = new Profile();
        $profile->getFromDB($profiles_id);
  
        if (!Session::haveRight("profile",READ)) {
            return false;
        }

        $canedit = Session::haveRight("profile", UPDATE);
  
        $this->getFromDBForProfile($profiles_id);
      

      if (!Session::haveRight("profile",READ)) {
         return false;
      }
      
      
      if (($canedit = Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, PURGE])) && $openform)
      {
         echo "<form method='post' action='".$profile->getFormURL()."'>";
      }
    
      
      
      $rights = $this->getAllRights();
      $profile->displayRightsChoiceMatrix($rights, [
                'canedit'       => $canedit,
                'default_class' => 'tab_bg_2',
                'title'         => __('General')
            ]
        );
      

      if ($canedit && $closeform) {
         echo "<div class='center'>";
         echo Html::hidden('id', ['value' => $profiles_id]);
         echo Html::submit(_sx('button', 'Save'), ['name' => 'update']);
         echo "</div>\n";
         Html::closeForm();
      }
      echo "</div>";
   }
    

    function getFromDBForProfile($ID){
        global $DB;

        $ID_profile = 0;
        // Get user profile
        $query = "SELECT `id`
                    FROM `glpi_plugin_edittraduction_profiles`
                    WHERE `id` = '$ID'";

        if ($result = $DB->query($query)) {
            if ($DB->numrows($result)) {
                $ID_profile = $DB->result($result,0,0);
            }
        }

        if ($ID_profile) {
            return $this->getFromDB($ID_profile);
        }
        return false;
    }


    /**
    * Initialize profiles, and migrate it necessary
    */
/*    static function initProfile() {
        global $DB;
        $profile = new self();
        $dbu = new DbUtils();
        //Add new rights in glpi_profilerights table
        foreach ($profile->getAllRights() as $data) {
            if ($dbu->countElementsInTable("glpi_profilerights", ["name" => $data['field']]) == 0) {
                ProfileRight::addProfileRights([$data['field']]);
            }
        }

        foreach ($DB->request("SELECT * FROM `glpi_profilerights` WHERE `profiles_id`='".$_SESSION['glpiactiveprofile']['id']."' AND `name` LIKE '%plugin_edittraduction%'") as $prof) {
            $_SESSION['glpiactiveprofile'][$prof['name']] = $prof['rights'];
        }
    }


    static function removeRightsFromSession() {
        foreach (self::getAllRights() as $right) {
            if (isset($_SESSION['glpiactiveprofile'][$right['field']])) {
                unset($_SESSION['glpiactiveprofile'][$right['field']]);
            }
        }
    }
*/


}