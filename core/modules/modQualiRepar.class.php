<?php
/* Copyright (C) 2026
 * Module QualiRépar
 */

include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

class modQualiRepar extends DolibarrModules
{
    public function __construct($db)
    {
        global $conf;

        $this->db = $db;

        // Identifiant du module
        $this->numero = 500000;

        // Classe des droits
        $this->rights_class = 'qualirepar';

        // Famille du module
        $this->family = "other";

        $this->module_position = '90';

        // Informations du module
        $this->name = 'QualiRepar';
        $this->description = 'Gestion du bonus réparation QualiRépar';

        $this->version = '0.2.0';

        // Constante d'activation
        $this->const_name = 'MAIN_MODULE_QUALIREPAR';

        // Icône
        $this->picto = 'bill';

        // Fichiers de langue
        $this->langfiles = array("qualirepar@qualirepar");


        /*
         * Répertoire d'installation SQL
         */
        $this->module_parts = array(
            'sql' => '/install/mysql/'
        );

        /*
         * Répertoires créés à l'installation
         */
        $this->dirs = array();


        /*
         * Droits (à compléter plus tard)
         */
        $this->rights = array();
    }


    /**
     * Installation du module
     */
    public function init($options = '')
    {
        $result = $this->_load_tables('/install/mysql/');

        if ($result < 0) {
            return -1;
        }

        return $this->_init($options);
    }
}
