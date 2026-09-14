-- Opprett databasen
CREATE DATABASE WorkSync;
USE WorkSync;

-- 1. Opprett Administrator-tabellen først (siden andre tabeller peker på den)
CREATE TABLE ADMINISTRATOR
(
    AdminId INT AUTO_INCREMENT PRIMARY KEY,
    Epost VARCHAR(255) NOT NULL,
    Passord VARCHAR(255) NOT NULL,
    AdminTelefonnr VARCHAR(20)
);

-- 2. Opprett Bedriftsleder-tabellen
CREATE TABLE COMPANYMANAGER (
    BedriftsId INT AUTO_INCREMENT PRIMARY KEY,
    Navn VARCHAR(100) NOT NULL,
    Epost VARCHAR(255),
    Passord VARCHAR(255),
    BedriftsEpost VARCHAR(255),
    Telefonnr VARCHAR(20),
    Bedriftsnavn VARCHAR(255),
    AdminId INT,
    FOREIGN KEY (AdminId) REFERENCES ADMINISTRATOR(AdminId)
);
-- 3. Opprett Kontor-tabellen
CREATE TABLE OFFICE (
    KontorId INT AUTO_INCREMENT PRIMARY KEY,
    AnsattId INT
);

-- 4. Opprett Ansatt-tabellen
CREATE TABLE EMPLOYEE (
    AnsattId INT AUTO_INCREMENT PRIMARY KEY,
    AnsattEpost VARCHAR(255) NOT NULL,
    AnsattPassord VARCHAR(255) NOT NULL,
    AnsattTelefonnr VARCHAR(20),
    Tilgjengelighet TEXT,
    AdminId INT,
    KontorId INT,
    FOREIGN KEY (AdminId) REFERENCES COMPANYMANAGER(AdminId),
    FOREIGN KEY (KontorId) REFERENCES OFFICE(KontorId)
);
-- Legg til manglende sirkulær referanse på Kontor-tabellen (Kontor har AnsattId som peker på Ansatt)
ALTER TABLE OFFICE
ADD CONSTRAINT fk_kontor_ansatt 
FOREIGN KEY (AnsattId) REFERENCES EMPLOYEE(AnsattId);

-- 5. Opprett Møterom-tabellen
CREATE TABLE MEETINGROOM (
    MoteromId INT AUTO_INCREMENT PRIMARY KEY,
    Tilgjengelighet VARCHAR(255)
);

-- 6. Opprett Oppgaver-tabellen
CREATE TABLE TASKS (
    OppgaveId INT AUTO_INCREMENT PRIMARY KEY,
    Beskrivelse VARCHAR(255),
    Frist DATE,
    BedriftsId INT,
    AnsattId INT,
    FOREIGN KEY (BedriftsId) REFERENCES COMPANYMANAGER(BedriftsId),
    FOREIGN KEY (AnsattId) REFERENCES EMPLOYEE(AnsattId)
);

-- 7. Opprett Avvik-tabellen
CREATE TABLE DEVIATION (
    OppgaveId INT AUTO_INCREMENT PRIMARY KEY,
    Beskrivelse VARCHAR(255),
    Frist DATE,
    BedriftsId INT,
    AnsattId INT,
    FOREIGN KEY (BedriftsId) REFERENCES COMPANYMANAGER(BedriftsId),
    FOREIGN KEY (AnsattId) REFERENCES EMPLOYEE(AnsattId)
);

-- Oppdater Administrator med fremmednøkler til Ansatt og Bedriftsleder (hvis du trenger det i etterkant)
ALTER TABLE ADMINISTRATOR
ADD COLUMN AnsattId INT,
ADD COLUMN BedriftsId INT,
ADD CONSTRAINT fk_admin_ansatt FOREIGN KEY (AnsattId) REFERENCES EMPLOYEE(AnsattId),
ADD CONSTRAINT fk_admin_bedrift FOREIGN KEY (BedriftsId) REFERENCES COMPANYMANAGER(BedriftsId);

CREATE TABLE PasswordReset (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    Epost VARCHAR(255) NOT NULL,
    Token VARCHAR(64) NOT NULL,
    ExpiresAt DATETIME NOT NULL
);