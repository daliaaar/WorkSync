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


-- Legger til etasje, bygning og avdeling --
ALTER TABLE OFFICE
  ADD COLUMN Avdeling VARCHAR(100) NULL,
  ADD COLUMN Etasje   VARCHAR(20)  NULL,
  ADD COLUMN Bygning  VARCHAR(100) NULL;

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

-- endrer lik at et kontor tilhører kun en ansatt --
ALTER TABLE EMPLOYEE ADD CONSTRAINT uq_employee_kontor UNIQUE (KontorId);

-- la til manglende stilling og avdeling slik nevnt i rapporten: 
ALTER TABLE EMPLOYEE
  ADD COLUMN Stilling VARCHAR(100) NULL AFTER AnsattTelefonnr,
  ADD COLUMN Avdeling VARCHAR(100) NULL AFTER Stilling;
  
-- Legg til manglende sirkulær referanse på Kontor-tabellen (Kontor har AnsattId som peker på Ansatt)
ALTER TABLE OFFICE
ADD CONSTRAINT fk_kontor_ansatt 
FOREIGN KEY (AnsattId) REFERENCES EMPLOYEE(AnsattId);

-- endring i møretom tabellen siden den skal regne utfra klokkeslett og vise tilgjengelighet
ALTER TABLE MEETINGROOM DROP COLUMN Tilgjengelighet;

-- legger heller til hvor mange personer som kan sitte i rommet og navn f.eks
ALTER TABLE MEETINGROOM
  ADD COLUMN Navn VARCHAR(100) NOT NULL DEFAULT '',
  ADD COLUMN Kapasitet INT NULL;
-- 5. Opprett Møterom-tabellen
CREATE TABLE MEETINGROOM (
    MoteromId INT AUTO_INCREMENT PRIMARY KEY,
    Tilgjengelighet VARCHAR(255)
);
-- La til avdeling, etasje og bygnig
ALTER TABLE MEETINGROOM
  ADD COLUMN Avdeling VARCHAR(100) NULL,
  ADD COLUMN Etasje   VARCHAR(20)  NULL,
  ADD COLUMN Bygning  VARCHAR(100) NULL;
-- Opprett Booking-tabellen
CREATE TABLE BOOKING (
    BookingId   INT AUTO_INCREMENT PRIMARY KEY,
    MoteromId   INT NOT NULL,
    AnsattEpost VARCHAR(255) NOT NULL,
    Dato        DATE NOT NULL,
    Starttid    TIME NOT NULL,
    Sluttid     TIME NOT NULL,
    FOREIGN KEY (MoteromId) REFERENCES MEETINGROOM(MoteromId)
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

CREATE TABLE EQUIPMENT (
    UtstyrId  INT AUTO_INCREMENT PRIMARY KEY,
    Navn      VARCHAR(100) NOT NULL,
    Type      VARCHAR(100),
    Status    VARCHAR(50) NOT NULL DEFAULT 'Available',
    Kjopsdato DATE,
    Garanti   DATE,
    AnsattId  INT NULL,
    FOREIGN KEY (AnsattId) REFERENCES EMPLOYEE(AnsattId) ON DELETE SET NULL
);