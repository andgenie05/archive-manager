-- Archive Manager Database Schema
-- MySQL Database Setup

CREATE DATABASE IF NOT EXISTS archive_manager;
USE archive_manager;

-- Users table
CREATE TABLE users (
  id INT PRIMARY KEY AUTO_INCREMENT,
  username VARCHAR(50) UNIQUE NOT NULL,
  email VARCHAR(100) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  full_name VARCHAR(100),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Directories table
CREATE TABLE directories (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  parent_id INT,
  name VARCHAR(255) NOT NULL,
  description TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (parent_id) REFERENCES directories(id) ON DELETE CASCADE,
  UNIQUE KEY unique_dir_per_parent (user_id, parent_id, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Documents table
CREATE TABLE documents (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  directory_id INT,
  name VARCHAR(255) NOT NULL,
  description TEXT,
  file_type VARCHAR(50),
  file_size INT,
  file_path VARCHAR(500),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (directory_id) REFERENCES directories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Indexes for better performance
CREATE INDEX idx_dirs_user_id ON directories(user_id);
CREATE INDEX idx_dirs_parent_id ON directories(parent_id);
CREATE INDEX idx_docs_user_id ON documents(user_id);
CREATE INDEX idx_docs_directory_id ON documents(directory_id);

-- Demo User
INSERT INTO users (username, email, password_hash, full_name) VALUES 
('demo', 'demo@archive.local', '$2y$10$YourHashedPasswordHere', 'Utilisateur Démo');

-- Demo Directories
INSERT INTO directories (user_id, parent_id, name, description) VALUES 
(1, NULL, '📁 Ressources Humaines', 'Gestion du personnel et documents RH'),
(1, NULL, '📁 Financier', 'Documents comptables et factures'),
(1, NULL, '📁 Projets', 'Archives de projets complétés'),
(1, NULL, '📁 Contrats', 'Contrats et accords'),
(1, 1, 'Employés Actifs', 'Liste des employés actuels'),
(1, 1, 'Contrats de travail', 'Contrats signés'),
(1, 2, 'Factures 2024', 'Factures de l\'année 2024'),
(1, 2, 'Rapports Financiers', 'Rapports annuels'),
(1, 3, 'Projet Alpha', 'Documentation du projet Alpha'),
(1, 3, 'Projet Beta', 'Documentation du projet Beta');

-- Demo Documents
INSERT INTO documents (user_id, directory_id, name, description, file_type, file_size) VALUES 
(1, 5, 'Liste_Employes_2024.pdf', 'Listing des effectifs', 'pdf', 245670),
(1, 5, 'Organigramme.xlsx', 'Structure organisationnelle', 'xlsx', 52300),
(1, 6, 'Contrat_Jean_Dupont.pdf', 'Contrat CDI', 'pdf', 185400),
(1, 6, 'Contrat_Marie_Martin.pdf', 'Contrat CDI', 'pdf', 187200),
(1, 7, 'Facture_2024_001.pdf', 'Facture client janvier', 'pdf', 95400),
(1, 7, 'Facture_2024_002.pdf', 'Facture client février', 'pdf', 97800),
(1, 8, 'Bilan_Financier_2023.xlsx', 'Résultats annuels', 'xlsx', 125600),
(1, 8, 'Previsions_2024.xlsx', 'Budget prévisionnel', 'xlsx', 98700),
(1, 9, 'Cahier_Charges_Alpha.pdf', 'Spécifications techniques', 'pdf', 325400),
(1, 9, 'Rapport_Final_Alpha.docx', 'Rapport de clôture', 'docx', 245600),
(1, 10, 'Cahier_Charges_Beta.pdf', 'Spécifications techniques', 'pdf', 298700),
(1, 10, 'Rapport_Final_Beta.docx', 'Rapport de clôture', 'docx', 267300);
