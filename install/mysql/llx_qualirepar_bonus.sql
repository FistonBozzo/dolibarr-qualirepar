CREATE TABLE llx_qualirepar_bonus
(
    rowid INTEGER AUTO_INCREMENT PRIMARY KEY,

    entity INTEGER NOT NULL DEFAULT 1,

    fk_facture INTEGER NOT NULL,

    fk_bareme INTEGER NOT NULL,

    montant_bonus DECIMAL(24,8) NOT NULL,

    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,

    fk_user_creat INTEGER,

    tms TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_bonus_facture (fk_facture),
    INDEX idx_bonus_bareme (fk_bareme),
    INDEX idx_bonus_entity (entity)
);
