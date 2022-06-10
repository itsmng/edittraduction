<?php

class PluginEdittraductionConfig extends CommonDBTM {

    static $rightname = 'config';

    static function getTypeName($nb = 0) {
        return __("Edit translation", 'edittraduction');
    }

    static function getMenuContent() {
        $menu = array();
        //Menu entry in config
        $menu['title'] = self::getTypeName(2);
        $menu['page'] = "/plugins/edittraduction/front/edittraduction.form.php";
        $menu['icon']  = "fas fa-cog";

        return $menu;
    }

}