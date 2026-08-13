<?php
/**
 * Gestion de la fiche tarifaire QualiRépar
 */

class QualiReparTarifs
{
    /**
     * @var DoliDB
     */
    private $db;

    /**
     * Constructeur
     *
     * @param DoliDB $db
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Récupère les produits à afficher dans la fiche tarifaire.
     *
     * @return array
     */
    public function getTarifs()
    {
        $tarifs = array();

        $sql = "SELECT";
        $sql .= " p.rowid,";
        $sql .= " p.ref,";
        $sql .= " p.label,";
        $sql .= " p.description,";
        $sql .= " p.price,";
        $sql .= " p.price_ttc,";
        $sql .= " p.tva_tx,";
        $sql .= " p.tms,";
        $sql .= " e.afficher_site_tarif,";
        $sql .= " e.ordre_site_tarif";
        $sql .= " FROM ".MAIN_DB_PREFIX."product AS p";
        $sql .= " INNER JOIN ".MAIN_DB_PREFIX."product_extrafields AS e";
        $sql .= " ON e.fk_object = p.rowid";
        $sql .= " WHERE p.tosell = 1";
        $sql .= " AND e.afficher_site_tarif = 1";
        $sql .= " ORDER BY";
        $sql .= " e.ordre_site_tarif ASC,";
        $sql .= " p.label ASC";

        $resql = $this->db->query($sql);

        if (!$resql) {
            return $tarifs;
        }

        while ($obj = $this->db->fetch_object($resql)) {

            $tarifs[] = array(
                'id' => (int) $obj->rowid,
                'ref' => $obj->ref,
                'label' => $obj->label,
                'description' => $obj->description,
                'price' => (float) $obj->price,
                'price_ttc' => (float) $obj->price_ttc,
                'tva_tx' => (float) $obj->tva_tx,
                'tms' => $obj->tms,
                'ordre' => (int) $obj->ordre_site_tarif
            );
        }

        return $tarifs;
    }

    /**
     * Retourne la date de dernière modification
     * des produits présents dans la fiche tarifaire.
     *
     * @return string|null
     */
    public function getDateMiseAJour()
    {
        $sql = "SELECT MAX(p.tms) as derniere_modification";
        $sql .= " FROM ".MAIN_DB_PREFIX."product AS p";
        $sql .= " INNER JOIN ".MAIN_DB_PREFIX."product_extrafields AS e";
        $sql .= " ON e.fk_object = p.rowid";
        $sql .= " WHERE p.tosell = 1";
        $sql .= " AND e.afficher_site_tarif = 1";

        $resql = $this->db->query($sql);

        if (!$resql) {
            return null;
        }

        $obj = $this->db->fetch_object($resql);

        if (empty($obj->derniere_modification)) {
            return null;
        }

        return $obj->derniere_modification;
    }
}
