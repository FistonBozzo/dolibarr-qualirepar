<?php

class ActionsQualiRepar
{
    public $results = array();
    public $resprints = '';


    /**
     * Ajout du bonus réparation dans la facture
     */
    public function printInvoiceFooter($parameters, &$object, &$action, $hookmanager)
    {
        global $langs;

        if ($object->element != 'facture') {
            return 0;
        }

        // Charge les champs extra
        $object->fetch_optionals();

        if (
            !empty($object->array_options['options_afficher_bonus_reparation'])
            &&
            !empty($object->array_options['options_bonus_reparation'])
        ) {

            $montant = price($object->array_options['options_bonus_reparation']);

            $this->resprints .= '
            <table width="100%">
                <tr>
                    <td align="right">
                        Bonus réparation QualiRépar
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
