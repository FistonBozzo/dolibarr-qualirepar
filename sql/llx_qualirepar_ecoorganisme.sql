CREATE TABLE llx_qualirepar_ecoorganisme
(
    rowid INTEGER AUTO_INCREMENT PRIMARY KEY,

    entity INTEGER NOT NULL DEFAULT 1,

    nom VARCHAR(255) NOT NULL,

    url_portail VARCHAR(255),

    actif TINYINT NOT NULL DEFAULT 1,

    tms TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_eco_entity (entity),
    INDEX idx_eco_actif (actif)
);
