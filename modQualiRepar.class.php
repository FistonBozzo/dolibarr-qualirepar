<?php
/* Copyright (C) 2026 - QualiRépar Module
 *
 * This program is free software.
 */

include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';

class modQualiRepar extends DolibarrModules
{
    public function __construct($db)
    {
        global $langs;

        $this->db = $db;

        // Identifiant unique du module
        $this->numero = 500000;

        // Nom du module
        $this->rights_class = 'qualirepar';
        $this->family = "other";
        $this->module_position = 500;

        $this->name = 'QualiRepar';
        $this->description = 'Gestion du Bonus Réparation QualiRépar';
        $this->version = '0.1.0';

        $this->const_name = 'MAIN_MODULE_QUALIREPAR';

        $this->picto = 'bill';

        // Module activable
        $this->editor_name = 'Julien GELAY';
        $this->editor_url = '';

        // Langues
        $this->langfiles = array(
            "qualirepar@qualirepar"
        );

        // Dépendances
        $this->depends = array();
        $this->requiredby = array();
        $this->conflictwith = array();

        // Aucun répertoire particulier
        $this->dirs = array();

        // Hooks utilisés
        $this->module_parts = array(
            'hooks' => array(
                'invoicecard',
                'pdf_generation'
            )
        );

        // Menus
        $this->menus = array();

        // Permissions
        $this->rights = array();
        $r = 0;

        $this->rights[$r][0] = 500001;
        $this->rights[$r][1] = 'Administrer QualiRépar';
        $this->rights[$r][2] = 'r';
        $this->rights[$r][3] = 1;
        $this->rights[$r][4] = 'admin';
        $this->rights[$r][5] = '';
    }

    /**
     * Activation du module
     */
    public function init($options = '')
    {
        $sql = array();

        $result = $this->_init($sql, $options);

        if ($result <= 0) {
            return $result;
        }

        $extrafields = new ExtraFields($this->db);

        /*
         * Champ : Montant du bonus réparation
         */
        if (!$extrafields->fetch_name_optionals_label('facture', 'bonus_reparation')) {

            $extrafields->addExtraField(
                'bonus_reparation',
                'Bonus réparation',
                'price',
                100,
                '',
                'facture',
                0,
                0,
                '',
                '',
                1,
                '',
                '',
                '',
                '',
                '',
                ''
            );
        }

        /*
         * Champ : Afficher le bonus sur le PDF
         */
        if (!$extrafields->fetch_name_optionals_label('facture', 'afficher_bonus')) {

            $extrafields->addExtraField(
                'afficher_bonus',
                'Afficher le Bonus Réparation',
                'boolean',
                101,
                '',
                'facture',
                0,
                0,
                '',
                '',
                1,
                '',
                '',
                '',
                '',
                '',
                ''
            );
        }

        return $result;
    }

    /**
     * Désinstallation
     */
    public function remove($options = '')
    {
        $sql = array();

        return $this->_remove($sql, $options);
    }
}
