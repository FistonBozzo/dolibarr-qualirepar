<?php
/* Copyright (C) 2026
 * Module QualiRépar
 */

include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

class modQualiRepar extends DolibarrModules
{
    public function __construct($db)
    {
        $this->db = $db;

        $this->numero = 500000;
        $this->rights_class = 'qualirepar';

        $this->family = "other";
        $this->module_position = '90';

        $this->name = 'QualiRepar';
        $this->description = 'Gestion du bonus réparation QualiRépar';

        $this->version = '0.1.0';

        $this->const_name = 'MAIN_MODULE_QUALIREPAR';

        $this->picto = 'bill';

        $this->langfiles = array("qualirepar@qualirepar");

        $this->dirs = array();

        $this->rights = array();

        $this->module_parts = array(
            'models' => 1
        );
    }
}
