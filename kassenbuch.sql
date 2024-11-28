CREATE TABLE benutzer (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
);

CREATE TABLE kassenbuch_eintraege (
    id INT AUTO_INCREMENT PRIMARY KEY,
    datum DATE NOT NULL,
    beleg VARCHAR(50),
    bemerkung TEXT,
    einnahme DECIMAL(10, 2),
    ausgabe DECIMAL(10, 2),
    saldo DECIMAL(10, 2) NOT NULL,
    user_id INT,
    FOREIGN KEY (user_id) REFERENCES benutzer(id)
);
