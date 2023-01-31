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

class PluginEdittraductionConfig extends CommonDBTM {

    static $rightname = 'config';
    
    /**
     * getTypeName
     *
     * @param  int $nb
     * @return string
     */
    static function getTypeName($nb = 0) {
        return __("Translation editor", 'edittraduction');
    }
    
    /**
     * getMenuContent
     *
     * @return array
     */
    static function getMenuContent() {
        $menu = array();

        $menu['title'] = self::getTypeName(2);
        $menu['page'] = "/plugins/edittraduction/front/edittraduction.form.php";
        $menu['icon']  = "fas fa-cog";

        return $menu;
    }
}