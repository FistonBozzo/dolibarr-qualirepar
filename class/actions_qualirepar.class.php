<?php

class ActionsQualiRepar
{
    public $results = array();
    public $resprints = '';


    /**
     * Ajout d'informations sur la facture
     */
    public function printInvoiceFooter($parameters, &$object, &$action, $hookmanager)
    {
        global $langs;

        if ($object->element != 'facture') {
            return 0;
        }


        if (
            !empty($object->array_options['options_afficher_bonus'])
            &&
            !empty($object->array_options['options_bonus_reparation'])
        ) {

            $montant = price($object->array_options['options_bonus_reparation']);


            $this->resprints .= '
            <table width="100%">
                <tr>
                    <td align="right">
                        '.$langs->trans("BonusReparation").'
                    </td>
                    <td align="right">
                        -'.$montant.' €
                    </td>
                </tr>
            </table>';
        }


        return 0;
    }
}
