-- Creazione database
CREATE DATABASE IF NOT EXISTS casaautomobilistica;
USE casaautomobilistica;

-- =============================================
-- TABELLE PRINCIPALI (dal modello ER)
-- =============================================

-- Officine
CREATE TABLE officina (
    codice VARCHAR(10) PRIMARY KEY,
    denominazione VARCHAR(100) NOT NULL,
    indirizzo VARCHAR(200) NOT NULL,
    telefono VARCHAR(20),
    email VARCHAR(100)
);

-- Dipendenti autorizzati (per la modifica dati)
CREATE TABLE dipendente (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL, -- in produzione usare hash
    nome VARCHAR(50),
    cognome VARCHAR(50),
    ruolo ENUM('admin', 'tecnico', 'magazziniere') DEFAULT 'tecnico',
    officina_codice VARCHAR(10),
    FOREIGN KEY (officina_codice) REFERENCES officina(codice) ON DELETE SET NULL
);

-- Servizi offerti
CREATE TABLE servizio (
    codice VARCHAR(10) PRIMARY KEY,
    descrizione TEXT NOT NULL,
    costo_orario DECIMAL(10,2) NOT NULL
);

-- Pezzi di ricambio
CREATE TABLE pezzo_ricambio (
    codice VARCHAR(10) PRIMARY KEY,
    descrizione TEXT NOT NULL,
    costo_unitario DECIMAL(10,2) NOT NULL
);

-- Accessori
CREATE TABLE accessorio (
    codice VARCHAR(10) PRIMARY KEY,
    descrizione TEXT NOT NULL,
    costo_unitario DECIMAL(10,2) NOT NULL
);

-- Clienti
CREATE TABLE cliente (
    codice VARCHAR(10) PRIMARY KEY,
    cognome VARCHAR(50) NOT NULL,
    nome VARCHAR(50) NOT NULL,
    telefono VARCHAR(20),
    email VARCHAR(100),
    password VARCHAR(255),
    codiceOTP VARCHAR(255),
    scadenzaOTP DATETIME,
    isActive BOOLEAN
);

-- Autoveicoli
CREATE TABLE autoveicolo (
    targa VARCHAR(15) PRIMARY KEY,
    numero_telaio VARCHAR(50),
    anno_costruzione INT,
    modello VARCHAR(50),
    cliente_codice VARCHAR(10),
    FOREIGN KEY (cliente_codice) REFERENCES cliente(codice) ON DELETE SET NULL
);

-- =============================================
-- TABELLE DI RELAZIONE (associazioni)
-- =============================================

-- OFFRE (associazione tra officina e servizio)
CREATE TABLE offre (
    officina_codice VARCHAR(10),
    servizio_codice VARCHAR(10),
    PRIMARY KEY (officina_codice, servizio_codice),
    FOREIGN KEY (officina_codice) REFERENCES officina(codice) ON DELETE CASCADE,
    FOREIGN KEY (servizio_codice) REFERENCES servizio(codice) ON DELETE CASCADE
);

-- PRESENZA PEZZI (associazione officina - pezzo_ricambio)
CREATE TABLE presenza_pezzo (
    officina_codice VARCHAR(10),
    pezzo_codice VARCHAR(10),
    quantita INT DEFAULT 0,
    PRIMARY KEY (officina_codice, pezzo_codice),
    FOREIGN KEY (officina_codice) REFERENCES officina(codice) ON DELETE CASCADE,
    FOREIGN KEY (pezzo_codice) REFERENCES pezzo_ricambio(codice) ON DELETE CASCADE
);

-- PRESENZA ACCESSORI (associazione officina - accessorio)
CREATE TABLE presenza_accessorio (
    officina_codice VARCHAR(10),
    accessorio_codice VARCHAR(10),
    quantita INT DEFAULT 0,
    PRIMARY KEY (officina_codice, accessorio_codice),
    FOREIGN KEY (officina_codice) REFERENCES officina(codice) ON DELETE CASCADE,
    FOREIGN KEY (accessorio_codice) REFERENCES accessorio(codice) ON DELETE CASCADE
);

-- INTERVENTI
CREATE TABLE intervento (
    codice VARCHAR(10) PRIMARY KEY,
    data DATE NOT NULL,
    descrizione TEXT,
    officina_codice VARCHAR(10),
    autoveicolo_targa VARCHAR(15),
    cliente_codice VARCHAR(10),
    FOREIGN KEY (officina_codice) REFERENCES officina(codice) ON DELETE SET NULL,
    FOREIGN KEY (autoveicolo_targa) REFERENCES autoveicolo(targa) ON DELETE SET NULL,
    FOREIGN KEY (cliente_codice) REFERENCES cliente(codice) ON DELETE SET NULL
);

-- COMPRENDE SERVIZI (intervento - servizio)
CREATE TABLE comprende_servizio (
    intervento_codice VARCHAR(10),
    servizio_codice VARCHAR(10),
    ore_lavorate DECIMAL(5,2),
    PRIMARY KEY (intervento_codice, servizio_codice),
    FOREIGN KEY (intervento_codice) REFERENCES intervento(codice) ON DELETE CASCADE,
    FOREIGN KEY (servizio_codice) REFERENCES servizio(codice) ON DELETE CASCADE
);

-- UTILIZZA PEZZI (intervento - pezzo_ricambio)
CREATE TABLE utilizza_pezzo (
    intervento_codice VARCHAR(10),
    pezzo_codice VARCHAR(10),
    quantita INT DEFAULT 1,
    PRIMARY KEY (intervento_codice, pezzo_codice),
    FOREIGN KEY (intervento_codice) REFERENCES intervento(codice) ON DELETE CASCADE,
    FOREIGN KEY (pezzo_codice) REFERENCES pezzo_ricambio(codice) ON DELETE CASCADE
);

-- UTILIZZA ACCESSORI (intervento - accessorio)
CREATE TABLE utilizza_accessorio (
    intervento_codice VARCHAR(10),
    accessorio_codice VARCHAR(10),
    quantita INT DEFAULT 1,
    PRIMARY KEY (intervento_codice, accessorio_codice),
    FOREIGN KEY (intervento_codice) REFERENCES intervento(codice) ON DELETE CASCADE,
    FOREIGN KEY (accessorio_codice) REFERENCES accessorio(codice) ON DELETE CASCADE
);

-- =============================================
-- DATI DI ESEMPIO
-- =============================================

INSERT INTO officina VALUES 
('MI001', 'AutoFix Milano', 'Via Roma 1, Milano', '02-1234567', 'milano@autofix.it'),
('RM002', 'CarService Roma', 'Via Napoli 10, Roma', '06-7654321', 'roma@carservice.it'),
('NA003', 'Officina Sud', 'Corso Umberto 50, Napoli', '081-9876543', 'napoli@officinasud.it');

INSERT INTO servizio VALUES 
('S001', 'Tagliando completo', 50.00),
('S002', 'Sostituzione freni', 45.00),
('S003', 'Cambio gomme', 35.00),
('S004', 'Diagnosi elettronica', 60.00);

INSERT INTO pezzo_ricambio VALUES 
('P001', 'Pastiglie freno anteriori', 45.00),
('P002', 'Filtro olio', 12.00),
('P003', 'Olio motore 5W30 (1L)', 8.50),
('P004', 'Candele (4 pz)', 25.00);

INSERT INTO accessorio VALUES 
('A001', 'Cerchi in lega 17"', 350.00),
('A002', 'Autoradio Bluetooth', 180.00),
('A003', 'Tappetini in gomma', 35.00);

-- Associazioni officine-servizi
INSERT INTO offre VALUES 
('MI001', 'S001'), ('MI001', 'S002'), ('MI001', 'S003'),
('RM002', 'S001'), ('RM002', 'S004'),
('NA003', 'S001'), ('NA003', 'S002'), ('NA003', 'S003'), ('NA003', 'S004');

-- Presenze pezzi
INSERT INTO presenza_pezzo VALUES 
('MI001', 'P001', 20), ('MI001', 'P002', 50), ('MI001', 'P003', 100),
('RM002', 'P001', 15), ('RM002', 'P004', 30),
('NA003', 'P001', 25), ('NA003', 'P002', 40), ('NA003', 'P003', 80), ('NA003', 'P004', 20);

-- Dipendente admin (password: admin123)
INSERT INTO dipendente (id, username, password, nome, cognome, ruolo, officina_codice) VALUES (1, 'admin', '$2y$12$/5Ap0BXO3PVHDXUw7zAxHuaov2N7jYOghnoL5sWcpbV51vQQs7lCa', 'Amministratore', 'Sistema', 'admin', NULL);
