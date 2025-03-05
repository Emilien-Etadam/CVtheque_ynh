# CV Manager pour YunoHost

[![Niveau d'intégration](https://dash.yunohost.org/integration/cv-manager.svg)](https://dash.yunohost.org/appci/app/cv-manager) ![](https://ci-apps.yunohost.org/ci/badges/cv-manager.status.svg) ![](https://ci-apps.yunohost.org/ci/badges/cv-manager.maintain.svg)  
[![Installer CV Manager avec YunoHost](https://install-app.yunohost.org/install-with-yunohost.svg)](https://install-app.yunohost.org/?app=cv-manager)

*[Read this readme in english.](./README.md)*

> *Ce package vous permet d'installer CV Manager rapidement et simplement sur un serveur YunoHost.  
Si vous n'avez pas YunoHost, consultez [le guide](https://yunohost.org/#/install) pour apprendre comment l'installer.*

## Vue d'ensemble
CV Manager est une application web légère développée en PHP qui permet de gérer efficacement les CV et les candidatures. Elle utilise SQLite comme base de données, ce qui la rend facile à déployer sans configuration complexe de serveur de base de données.

### Fonctionnalités principales

- Upload de CV au format PDF
- Extraction automatique des informations (nom, email) depuis les PDF
- Gestion des candidats (ajout, modification, suppression)
- Suivi du statut des candidatures (Nouveau, En cours, Entretien planifié, Accepté, Refusé)
- Filtrage des candidats par statut et poste
- Visualisation des CV directement dans l'application
- Mode sombre/clair

**Version incluse :** 1.0.0

## Captures d'écran

![](./doc/screenshots/screenshot.jpg)

## Documentation et ressources

* Site officiel de l'app : [Lien vers le site officiel de cette application](https://github.com/YunoHost-Apps/cv-manager_ynh)
* Documentation officielle utilisateur : [Lien vers la documentation officielle de cette application](https://github.com/YunoHost-Apps/cv-manager_ynh/blob/master/doc/ADMIN.md)
* Documentation YunoHost pour cette app : [Lien vers la documentation YunoHost pour cette app](https://yunohost.org/app_cv-manager)
* Signaler un bug : [Issues](https://github.com/YunoHost-Apps/cv-manager_ynh/issues)

## Informations pour les développeurs

Merci de faire vos pull request sur la [branche testing](https://github.com/YunoHost-Apps/cv-manager_ynh/tree/testing).

Pour essayer la branche testing, procédez comme suit :
```
sudo yunohost app install https://github.com/YunoHost-Apps/cv-manager_ynh/tree/testing --debug
ou
sudo yunohost app upgrade cv-manager -u https://github.com/YunoHost-Apps/cv-manager_ynh/tree/testing --debug
```

**Plus d'infos sur le packaging d'applications :** https://yunohost.org/packaging_apps
