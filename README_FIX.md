# CV Manager Database Fix

Ce guide vous aidera à résoudre l'erreur SQLite que vous rencontrez avec votre installation de CV Manager.

## Problème

Vous avez rencontré l'erreur suivante :

```
Warning: SQLite3::prepare(): Unable to prepare statement: 1, no such table: candidates in /var/www/cv-manager/index.php on line 179

Fatal error: Uncaught Error: Call to a member function execute() on bool in /var/www/cv-manager/index.php:183 Stack trace: #0 {main} thrown in /var/www/cv-manager/index.php on line 183
```

Cette erreur se produit car la base de données SQLite n'a pas été correctement initialisée ou n'a pas les bonnes permissions.

## Solution

Nous avons créé deux fichiers pour résoudre ce problème :

1. `init_database.php` - Un script PHP qui initialise la base de données
2. `fix_database.sh` - Un script shell qui automatise le processus de correction

### Instructions pour Linux/YunoHost

1. Transférez les deux fichiers (`init_database.php` et `fix_database.sh`) sur votre serveur
2. Connectez-vous à votre serveur via SSH
3. Rendez le script shell exécutable :
   ```
   chmod +x fix_database.sh
   ```
4. Exécutez le script en tant que root ou avec sudo :
   ```
   sudo ./fix_database.sh
   ```
5. Suivez les instructions à l'écran

### Instructions manuelles (si le script ne fonctionne pas)

Si le script automatique ne fonctionne pas, vous pouvez suivre ces étapes manuelles :

1. Transférez le fichier `init_database.php` dans le répertoire d'installation de CV Manager (généralement `/var/www/cv-manager/`)
2. Connectez-vous à votre serveur via SSH
3. Accédez au répertoire d'installation :
   ```
   cd /var/www/cv-manager/
   ```
4. Exécutez le script PHP :
   ```
   php init_database.php
   ```
5. Assurez-vous que les permissions sont correctes :
   ```
   sudo chown www-data:www-data cv.db
   sudo chmod 664 cv.db
   sudo chown -R www-data:www-data uploads/
   sudo chmod -R 775 uploads/
   ```

## Que fait ce correctif ?

Le script effectue les actions suivantes :

1. Vérifie si le fichier de base de données existe
2. Crée la base de données si elle n'existe pas
3. Vérifie si la table `candidates` existe
4. Crée la table `candidates` avec la structure correcte si elle n'existe pas
5. Vérifie et crée le répertoire `uploads` si nécessaire
6. Définit les permissions correctes sur les fichiers et répertoires

## Besoin d'aide supplémentaire ?

Si vous rencontrez toujours des problèmes après avoir exécuté ce correctif, veuillez vérifier les points suivants :

1. Assurez-vous que PHP et l'extension SQLite3 sont installés sur votre serveur
2. Vérifiez les logs d'erreur de votre serveur web pour plus de détails
3. Assurez-vous que l'utilisateur du serveur web (généralement www-data) a les permissions d'écriture sur le répertoire d'installation
