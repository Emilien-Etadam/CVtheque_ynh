# Guide d'administration pour CV Manager

## Configuration

CV Manager est conçu pour fonctionner sans configuration supplémentaire après l'installation. La base de données SQLite est créée automatiquement lors de la première utilisation.

### Prérequis

- PHP 8.0 ou supérieur
- Extension SQLite pour PHP
- Extensions PHP: gd, xml, zip

### Structure des répertoires

```
/var/www/cv-manager/
├── app/                  # Code source de l'application
├── conf/                 # Fichiers de configuration
├── uploads/              # Répertoire de stockage des CV
└── temp/                 # Répertoire temporaire pour les uploads
```

## Personnalisation

### Statuts des candidats

Vous pouvez modifier la liste des statuts disponibles en éditant la variable `$statuses` dans le fichier `db.php` :

```php
$statuses = ['Nouveau', 'En cours', 'Entretien planifié', 'Accepté', 'Refusé'];
```

### Apparence

Le style de l'application peut être personnalisé en modifiant les fichiers CSS :
- `darkmode.css` pour les styles du mode sombre/clair

## Sécurité

- Les noms de fichiers sont générés de manière unique pour éviter les collisions
- Les entrées utilisateur sont validées et échappées pour prévenir les injections SQL et XSS
- Les chemins de fichiers sont vérifiés pour éviter les attaques de type directory traversal

## Sauvegarde et restauration

### Sauvegarde

La sauvegarde de l'application inclut :
- Le code source de l'application
- La base de données SQLite (`cv.db`)
- Les CV uploadés (répertoire `uploads/`)

### Restauration

La restauration de l'application consiste à :
- Restaurer le code source
- Restaurer la base de données SQLite
- Restaurer les CV uploadés

## Dépannage

### Problèmes courants

1. **Erreur d'upload de fichier** : Vérifiez les permissions des répertoires `uploads/` et `temp/`. Ils doivent être accessibles en écriture par l'utilisateur www-data.

2. **Extraction d'informations depuis les PDF ne fonctionne pas** : Vérifiez que les extensions PHP requises sont installées et activées.

3. **Page blanche** : Vérifiez les logs PHP pour identifier l'erreur.

### Logs

Les logs de l'application peuvent être consultés dans les logs PHP standard :

```bash
sudo tail -f /var/log/nginx/error.log
sudo tail -f /var/log/php8.0-fpm.log
```

## Mise à jour

La mise à jour de l'application se fait via YunoHost :

```bash
sudo yunohost app upgrade cv-manager
```

## Support

Si vous rencontrez des problèmes, vous pouvez :
- Consulter la [documentation officielle](https://github.com/YunoHost-Apps/cv-manager_ynh)
- Signaler un bug sur [GitHub](https://github.com/YunoHost-Apps/cv-manager_ynh/issues)
