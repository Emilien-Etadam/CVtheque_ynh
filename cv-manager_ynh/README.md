# CV Manager pour YunoHost

[![Niveau d'intégration](https://dash.yunohost.org/integration/cv-manager.svg)](https://dash.yunohost.org/appci/app/cv-manager) ![](https://ci-apps.yunohost.org/ci/badges/cv-manager.status.svg) ![](https://ci-apps.yunohost.org/ci/badges/cv-manager.maintain.svg)  
[![Installer CV Manager avec YunoHost](https://install-app.yunohost.org/install-with-yunohost.svg)](https://install-app.yunohost.org/?app=cv-manager)

*[Lire ce README en français.](./README_fr.md)*

> *This package allows you to install CV Manager quickly and simply on a YunoHost server.  
If you don't have YunoHost, please consult [the guide](https://yunohost.org/#/install) to learn how to install it.*

## Overview
CV Manager is a lightweight web application developed in PHP that allows you to efficiently manage CVs and job applications. It uses SQLite as a database, making it easy to deploy without complex database server configuration.

### Main features

- PDF CV upload
- Automatic information extraction (name, email) from PDFs
- Candidate management (add, edit, delete)
- Application status tracking (New, In progress, Interview scheduled, Accepted, Rejected)
- Filtering candidates by status and position
- CV visualization directly in the application
- Dark/light mode

**Shipped version:** 1.0.0

## Screenshots

![](./doc/screenshots/screenshot.jpg)

## Documentation and resources

* Official app website: [Link to the official website of this app](https://github.com/YunoHost-Apps/cv-manager_ynh)
* Official admin documentation: [Link to the official admin documentation of this app](https://github.com/YunoHost-Apps/cv-manager_ynh/blob/master/doc/ADMIN.md)
* YunoHost documentation for this app: [Link to the YunoHost documentation for this app](https://yunohost.org/app_cv-manager)
* Report a bug: [Issues](https://github.com/YunoHost-Apps/cv-manager_ynh/issues)

## Developer info

Please send your pull request to the [testing branch](https://github.com/YunoHost-Apps/cv-manager_ynh/tree/testing).

To try the testing branch, please proceed like that:
```
sudo yunohost app install https://github.com/YunoHost-Apps/cv-manager_ynh/tree/testing --debug
or
sudo yunohost app upgrade cv-manager -u https://github.com/YunoHost-Apps/cv-manager_ynh/tree/testing --debug
```

**More info regarding app packaging:** https://yunohost.org/packaging_apps
