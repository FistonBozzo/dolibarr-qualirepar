<?php
/* Copyright (C) 2026 - QualiRépar Module for Dolibarr
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

include_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';

class modQualiRepar extends DolibarrModules
{
    public function __construct($db)
    {
        $this->db = $db;

        // Identifiants du module
        $this->numero = 500000;  // Numéro unique (à adapter si conflit)
        $this->rights_class = 'qualirepar';
        $this->family = "other";
        $this->module_position = '90';

        // Informations du module
        $this->name = 'QualiRepar';
        $this->description = 'Gestion du bonus réparation QualiRépar';
        $this->version = '0.1.0';

        // Constantes
        $this->const_name = 'MAIN_MODULE_QUALIREPAR';
        $this->picto = 'bill';  // Icône (ex: 'bill', 'product', 'user')

        // Fichiers de langue
        $this->langfiles = array("qualirepar@qualirepar");

        // Dossiers
        $this->dirs = array();

        // Droits
        $this->rights = array(
            'qualirepar' => array(
                'admin' => array(
                    'lib' => 'Administrer QualiRépar',
                    'perms' => array('admin'),
                    'type' => 'admin'
                )
            )
        );

        // Parties du module
        $this->module_parts = array(
            'hooks' => array(
                'formBuildObject',  // Ajoute des champs dans les formulaires
                'doActions',        // Actions (validation, etc.)
                'printObjectLine',  // Affiche des lignes dans les objets (factures)
                'pdf_generation',   // Génération de PDF
                'invoicecard'       // Affichage de la carte facture
            ),
            'models' => 1
        );

        // Menu
        $this->menus = array(
            array(
                'fk_menu' => 'top',
                'type' => 'top',
                'titre' => 'QualiRépar',
                'mainmenu' => 'qualirepar',
                'leftmenu' => 'qualirepar',
                'url' => '/qualirepar/admin/qualirepar_ecoorganisme.php',
                'langs' => 'qualirepar@qualirepar',
                'position' => 100,
                'enabled' => '$conf->qualirepar->enabled',
                'perms' => '$user->rights->qualirepar->admin',
                'target' => '',
                'user' => 0
            )
        );
    }

    // Fonction d'initialisation (optionnelle)
    public function init($options = '')
    {
        global $conf, $langs;
        $sql = array();
        return $this->_init($sql, $options);
    }
}
