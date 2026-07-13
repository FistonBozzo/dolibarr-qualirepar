CREATE TABLE llx_qualirepar_bareme
(
    rowid INTEGER AUTO_INCREMENT PRIMARY KEY,

    entity INTEGER NOT NULL DEFAULT 1,

    libelle VARCHAR(255) NOT NULL,

    montant_bonus DECIMAL(24,8) NOT NULL DEFAULT 0,

    fk_ecoorganisme INTEGER DEFAULT NULL,

    ordre INTEGER NOT NULL DEFAULT 100,

    actif TINYINT NOT NULL DEFAULT 1,

    tms TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_bareme_entity (entity),
    INDEX idx_bareme_actif (actif),
    INDEX idx_bareme_eco (fk_ecoorganisme)
);
