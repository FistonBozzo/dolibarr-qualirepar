<?php
/* Copyright (C) 2026 - QualiRépar Module
 *
 * Gestion simple du Bonus Réparation
 */

include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

class modQualiRepar extends DolibarrModules
{
    public function __construct($db)
    {
        global $langs;

        $this->db = $db;

        $this->numero = 500000;
        $this->rights_class = 'qualirepar';

        $this->family = "other";
        $this->module_position = '90';

        $this->name = 'QualiRepar';
        $this->description = 'Ajout du Bonus Réparation sur les factures';
        $this->version = '0.1.0';

        $this->const_name = 'MAIN_MODULE_QUALIREPAR';

        $this->picto = 'bill';

        $this->langfiles = array(
            'qualirepar@qualirepar'
        );


        /*
         * Hooks utilisés
         */
        $this->module_parts = array(
            'hooks' => array(
                'pdfgeneration',
                'invoicecard'
            )
        );


        /*
         * Création des champs supplémentaires
         */
        $this->extrafields = array(
            'facture' => array(
                array(
                    'name' => 'bonus_reparation',
                    'label' => 'Bonus réparation',
                    'type' => 'price'
                ),
                array(
                    'name' => 'afficher_bonus',
                    'label' => 'Afficher Bonus Réparation',
                    'type' => 'boolean'
                )
            )
        );
    }


    /**
     * Activation du module
     */
    public function init($options = '')
    {
        $sql = array();

        return $this->_init($sql, $options);
    }


    /**
     * Désactivation du module
     */
    public function remove($options = '')
    {
        $sql = array();

        return $this->_remove($sql, $options);
    }
}
