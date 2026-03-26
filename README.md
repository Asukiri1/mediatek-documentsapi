<h1>Présentation de l'API</h1>
La présentation de l'API d'origine est disponible sur le dépôt officiel initial (https://github.com/CNED-SLAM/rest_mediatekdocuments). Ce document présente uniquement les évolutions techniques.


##  Nouvelles Fonctionnalités Backend

###  Routage Transactionnel et Sécurisé
* **Transactions SQL :** L'API gère désormais les doubles insertions (ex: création d'une Commande puis de ses détails d'Abonnement). En cas d'échec d'une sous-requête, un `rollBack` garantit l'absence de données orphelines.
* **Exceptions Métier :** Création d'une `AppException` permettant d'intercepter les erreurs renvoyées par les Triggers MySQL et de retourner des messages d'erreurs HTTP 400 personnalisés au client.

###  Nouvelles Routes
* `/authentification` : Vérifie les identifiants en base et retourne le profil de l'employé avec jointure de son service.
* `/gestion_cmd` et `/gestion_abonnement` : Centralisent les opérations  liées aux commandes avec génération automatique des identifiants (`0000X`).
* `/abonnements_expirants` : Retourne les abonnements expirant dans moins de 30 jours.

###  Sécurité Renforcée
* Modification du `.htaccess` pour bloquer  l'accès direct et vide à la racine du répertoire.
* Support du déploiement en ligne via la conservation des en-têtes d'autorisation (contournement des restrictions d'hébergeurs mutualisés).

<h1>Installation de l'API en local</h1>

* Environnement : Installez un serveur local type WampServer.
* Déploiement : Clonez ou téléchargez ce dépôt et placez le contenu dans le dossier `www/rest_mediatekdocuments`.
* Dépendances : Ouvrez un terminal dans ce dossier et lancez la commande `composer install`.
* Base de données :
* Créez une base nommée `mediatek86` dans phpMyAdmin.
* Importez le fichier `mediatek86.sql` fourni à la racine de ce projet (il contient déjà les Triggers métiers et les tables d'authentification).
* Le .env doit ressembler à ça avec les modifications lié dans le C# de MediatekDockument pour l'utilisé avec l'application. Plus d'info ici: https://github.com/Asukiri1/mediatek-documents
* AUTHENTIFICATION=basic
* AUTH_USER=admin
* AUTH_PW=adminpwd
* BDD_LOGIN=root
* BDD_PWD=
* BDD_BD=mediatek86
* BDD_SERVER=localhost
* BDD_PORT=3306

<h1>Documentation Technique</h1>
La documentation Technique est disponible dans le dossier DocumentationTechnique du projet
