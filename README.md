# Archive Manager 📦

Une application web complète pour la gestion d'archives (répertoires, documents, etc.) avec PHP PDO, AJAX et MySQL.

## Fonctionnalités

### Authentification
- ✅ Inscription et connexion sécurisées
- ✅ Gestion de sessions
- ✅ Mot de passe hashé avec bcrypt
- ✅ Redirection automatique si non authentifié

### Gestion des Répertoires
- ✅ Créer, modifier, supprimer des répertoires
- ✅ Structure hiérarchique (dossiers imbriqués)
- ✅ Accès par double-clic
- ✅ Navigation par fil d'Ariane
- ✅ Suppression récursive avec ses documents

### Gestion des Documents
- ✅ Upload de fichiers avec drag-and-drop
- ✅ Modification des métadonnées
- ✅ Suppression des documents
- ✅ Accès par double-clic
- ✅ Affichage d'icônes selon le type de fichier

### Interface Utilisateur
- ✅ Design moderne et épuré avec gradient
- ✅ Navigation latérale intuitive
- ✅ États vides avec illustrations
- ✅ États de chargement avec spinner
- ✅ Responsive design (mobile-friendly)
- ✅ Mises à jour optimistes
- ✅ Alertes utilisateur (succès, erreur)

### Recherche
- ✅ Recherche en temps réel
- ✅ Résultats instantanés
- ✅ Recherche dans les noms et descriptions

### Données de Démonstration
- ✅ Utilisateur démo préprogrammé
- ✅ Répertoires de démonstration réalistes
- ✅ Documents de démonstration fonctionnels

## Installation

### 1. Prérequis
- PHP 7.4+
- MySQL 5.7+
- Apache ou serveur web compatible

### 2. Configuration de la Base de Données

```bash
# Créer la base de données
mysql -u root -p < database.sql
```

### 3. Configuration PHP

Modifier `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'archive_manager');
```

### 4. Permissions des Dossiers

```bash
mkdir -p uploads
chmod 755 uploads
chmod 755 api
chmod 755 config
```

### 5. Accès à l'Application

- **URL**: `http://localhost/archive-manager/`
- **Utilisateur démo**: `demo`
- **Mot de passe démo**: `demo`

## Structure du Projet

```
archive-manager/
├── config/
│   └── database.php          # Configuration DB et helpers
├── classes/
│   ├── Auth.php              # Classe d'authentification
│   ├── Directory.php         # Classe de gestion des répertoires
│   └── Document.php          # Classe de gestion des documents
├── api/
│   ├── directory.php         # Endpoint API répertoires
│   ├── document.php          # Endpoint API documents
│   └── search.php            # Endpoint API recherche
├── assets/
│   ├── css/
│   │   └── styles.css        # Styles principaux
│   └── js/
│       └── app.js            # JavaScript principal
├── uploads/                  # Stockage des fichiers
├── index.php                 # Page principale
├── login.php                 # Page de connexion/inscription
└── database.sql              # Schéma de base de données
```

## Fonctionnement

### Architecture
- **Backend**: PHP PDO avec MySQL
- **Frontend**: Vanilla JavaScript avec AJAX
- **Design**: CSS moderne avec Flexbox et Grid

### Flux d'Utilisation
1. Authentification via login.php
2. Redirection vers index.php
3. Chargement du répertoire racine
4. Navigation via le sidebar ou double-clic
5. CRUD sur répertoires et documents via AJAX

### AJAX et Mises à Jour
- Les opérations CRUD sont effectuées via AJAX
- Les modifications s'affichent instantanément
- Pas de rechargement de page

## Sécurité

- 🔒 Mot de passe hashé avec bcrypt
- 🔒 Validation des entrées utilisateur
- 🔒 Protection contre l'injection SQL (requêtes préparées)
- 🔒 Vérification des permissions utilisateur
- 🔒 Sessions sécurisées avec timeout

## Données de Démonstration

L'application est initialisée avec:
- 1 utilisateur démo
- 4 répertoires racine (RH, Financier, Projets, Contrats)
- 6 répertoires imbriqués
- 12 documents fictifs réalistes

## Améliorations Possibles

- [ ] Export PDF des dossiers
- [ ] Partage de documents
- [ ] Historique des modifications
- [ ] Versioning des documents
- [ ] Contrôle d'accès par rôle
- [ ] API RESTful complète
- [ ] Notifications en temps réel
- [ ] Gestion d'utilisateurs multiples
- [ ] Importation en masse
- [ ] Compression des archives

## Technologies Utilisées

- **PHP**: 7.4+
- **MySQL**: 5.7+
- **JavaScript**: ES6+
- **CSS**: 3 avec animations
- **AJAX**: Fetch API
- **Architecture**: MVC

## Auteur

Archive Manager - Application de gestion d'archives 2024

## Licence

GNU General Public License v3.0
