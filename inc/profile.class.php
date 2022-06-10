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
            return __('Edit translation', 'edittraduction');
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
                'label'     => __('Edit translation', 'edittraduction'),
                'field'     => 'plugin_edittraduction_edittraduction',
                'rights'    =>  [UPDATE    => __('Allow editing', 'edittraduction')],
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
      $rights = $this->getRightsGeneral();
      $profile->displayRightsChoiceMatrix($rights, ['default_class' => 'tab_bg_2',
                                                         'title'         => __('General')]);

      if ($canedit && $closeform) {
         echo "<div class='center'>";
         echo Html::hidden('id', ['value' => $profiles_id]);
         echo Html::submit(_sx('button', 'Save'), ['name' => 'update']);
         echo "</div>\n";
         Html::closeForm();
      }
      echo "</div>";
   }
}