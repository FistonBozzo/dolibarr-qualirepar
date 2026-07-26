<?php

class ActionsQualiRepar
{
    public $results = array();
    public $resprints = '';

    /**
     * Ajoute les informations Bonus Réparation sur le PDF
     */
    public function beforePDFCreation($parameters, &$object, &$action, $hookmanager)
    {
        return 0;
    }
}
