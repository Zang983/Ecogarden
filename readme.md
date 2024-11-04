# Api EcoGarden & Co

## Description

Ce projet, du parcours de formation PHP/Symfony proposé par Openclassrooms consiste en la création d'une API permettant
de :

- Gérer les utilisateurs
- Gérer des conseils de jardinage valables selon les mois de l'année
- Obtenir des infos météo de l'API Openweathermap

## Installation

Télécharger le projet et utiliser la commande : "composer install" pour installer les dépendances.
Renommer le fichier template.env.local en "env.local" et ajouter les informations demandées :
- DATABASE_URL : url de la base de données  
- JWT_SECRET_KEY : prérempli par défaut
- JWT_PUBLIC_KEY : prérempli par défaut
- JWT_PASSPHRASE : Passphrase aléatoire pour la génération des clés JWT
- API_KEY : Clé d'API Openweathermap obtenue sur le site de l'API après inscription

Concernant l'installation et la génération des clés SSL vous pouvez vous référer à la documentation officielle du bundle
sur le site de Symfony : https://symfony.com/bundles/LexikJWTAuthenticationBundle/current/index.html#installation

## Utilisation

Pour lancer le serveur, utiliser la commande : "symfony serve" pour lancer le serveur en local.
Vous pouvez utiliser un logiciel tel que Postman pour tester les différentes routes de l'API dont les informations sont
fournies dans les fichiers swagger.json / swagger.yaml

--------------------------------------------

## Description
This project, from the Openclassrooms PHP/Symfony training course, involves creating an API that allows:

Managing users

Managing gardening advice valid according to the months of the year

Obtaining weather information from the OpenWeatherMap API

## Installation
Download the project and use the following command to install the dependencies: "composer install".

Rename the template.env.local file to .env.local and add the required information:

DATABASE_URL: URL of the database

JWT_SECRET_KEY: pre-filled by default

JWT_PUBLIC_KEY: pre-filled by default

JWT_PASSPHRASE: Random passphrase for generating JWT keys

API_KEY: OpenWeatherMap API key (you need to sign up to get a key)

For installation and generating SSL keys, you can refer to the official documentation of the bundle on the Symfony website: https://symfony.com/bundles/LexikJWTAuthenticationBundle/current/index.html#installation

## Usage
To start the server, use the following command: "symfony serve" to start the local server.

You can use software like Postman to test the different API routes, with information provided in the swagger.json / swagger.yaml files.

Let me know if you need any further adjustments or clarifications!