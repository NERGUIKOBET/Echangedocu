# Application de Gestion de Documents

Application web permettant la gestion et le partage de documents entre entreprises.

## Fonctionnalités

- Upload et partage de documents
- Gestion des statuts (en attente, approuvé, refusé)
- Historique des échanges
- Prévisualisation des documents
- Modification des documents
- Export des historiques en PDF et CSV

## Configuration requise

- PHP 7.4+
- MySQL/MariaDB
- Serveur Web (Apache recommandé)
- Extensions PHP requises :
  - PDO
  - PDO_MySQL
  - GD (pour les prévisualisations)
  - FileInfo

## Installation

1. Cloner le dépôt
```bash
git clone [URL_DU_REPO]
cd ap.enerca
```

2. Créer la base de données
```sql
SOURCE database.sql
```

3. Configurer la connexion à la base de données
- Copier `db_connect.example.php` vers `db_connect.php`
- Modifier les paramètres de connexion dans `db_connect.php`

4. Créer le dossier pour les documents
```bash
mkdir documents_storage
chmod 755 documents_storage
```

5. Configurer les permissions
```bash
chmod 755 .
chmod 644 *.php
chmod 755 documents_storage
```

## Structure du projet

- `admin.php` - Interface d'administration
- `dashboard.php` - Interface principale
- `js/` - Scripts JavaScript
- `documents_storage/` - Stockage des fichiers uploadés
- `*.php` - Scripts de traitement

## Sécurité

- Les mots de passe sont hachés
- Validation des fichiers uploadés
- Protection contre les injections SQL
- Contrôle des accès par rôle

## Licence

Ce projet est sous licence privée. Tous droits réservés.