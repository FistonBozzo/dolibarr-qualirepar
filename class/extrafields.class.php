public function init($options = '')
{
    global $conf;

    $sql = array();

    $extrafields = new ExtraFields($this->db);

    // Champ montant bonus réparation
    $result = $extrafields->addExtraField(
        'bonus_reparation',
        'Bonus réparation QualiRépar',
        'price',
        100,
        '',
        'facture',
        0,
        0,
        '',
        '',
        0,
        '',
        '',
        '',
        ''
    );

    // Case affichage bonus réparation
    $result = $extrafields->addExtraField(
        'afficher_bonus_reparation',
        'Afficher bonus réparation',
        'boolean',
        101,
        '',
        'facture',
        0,
        0,
        '',
        '',
        0,
        '',
        '',
        '',
        ''
    );

    return $this->_init($sql, $options);
}
