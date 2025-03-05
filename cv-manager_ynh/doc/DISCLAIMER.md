## Avertissement

* Cette application utilise SQLite comme base de données, ce qui est adapté pour une utilisation avec un nombre limité d'utilisateurs. Pour une utilisation intensive avec de nombreux utilisateurs simultanés, il pourrait être nécessaire d'envisager une migration vers une base de données plus robuste comme MySQL ou PostgreSQL.

* L'extraction automatique des informations depuis les PDF peut ne pas fonctionner parfaitement avec tous les formats de CV. Les résultats peuvent varier en fonction de la structure et du format du PDF.

* L'application ne dispose pas d'un système d'authentification intégré et s'appuie sur le système d'authentification de YunoHost (SSO). Assurez-vous de configurer correctement les permissions d'accès.

* Les fichiers PDF uploadés sont stockés dans le répertoire `uploads/`. Assurez-vous que ce répertoire est correctement sauvegardé si vous stockez des données importantes.

* Cette application n'est pas conçue pour gérer un très grand volume de CV (plusieurs milliers). Si vous avez besoin de gérer un grand nombre de candidats, envisagez une solution plus robuste.
