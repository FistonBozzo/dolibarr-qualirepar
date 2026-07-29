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
        $this->version = '0.2.0';

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
            ),
            'models' => array(
                'custom/qualirepar/'
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
                    'name' => 'afficher_bonus_reparation',
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
        global $conf;
    
        require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
    
        $extrafields = new ExtraFields($this->db);
    
        $extrafields->addExtraField(
            'bonus_reparation',
            'Bonus réparation QualiRépar',
            'price',
            100,
            '',
            'facture',
            1,
            1,
            '',
            '',
            0,
            '',
            '',
            '',
            ''
        );
    
        $extrafields->addExtraField(
            'afficher_bonus_reparation',
            'Afficher bonus',
            'boolean',
            101,
            '',
            'facture',
            1,
            1,
            '',
            '',
            0,
            '',
            '',
            '',
            ''
        );
    
    
        // Création du modèle PDF intervention QualiRépar
        $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."document_model";
        $sql .= " WHERE nom='soleil_qualirepar'";
        $sql .= " AND type='ficheinter'";
        $sql .= " AND entity=".(int) $conf->entity;
    
        $resql = $this->db->query($sql);
    
        if ($resql && $this->db->num_rows($resql) == 0) {
    
            $sql = "INSERT INTO ".MAIN_DB_PREFIX."document_model";
            $sql .= " (nom, entity, type, libelle)";
            $sql .= " VALUES ('soleil_qualirepar', "
                 .(int) $conf->entity
                 .", 'ficheinter', 'Soleil QualiRépar')";
    
            $this->db->query($sql);
        }
    
    
        return $this->_init(array(), $options);
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
