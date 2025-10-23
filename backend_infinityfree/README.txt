# Backend GameZone pour InfinityFree

## Installation

1. Uploadez tout le contenu de ce dossier vers /htdocs/ sur InfinityFree

2. Structure finale sur InfinityFree :
   /htdocs/
   ├── api/
   ├── uploads/
   ├── images/
   └── .htaccess

3. Créez api/.env avec vos informations InfinityFree

4. Importez api/database/schema.sql dans phpMyAdmin

5. Modifiez .htaccess avec votre URL Vercel

6. Testez : https://votre-domaine.infinityfreeapp.com/api/auth/check.php

## Configuration CORS

Dans .htaccess, remplacez :
  Header set Access-Control-Allow-Origin "https://gamezone.vercel.app"

Par votre vraie URL Vercel.

## Variables .env

Copiez api/.env.example vers api/.env et remplissez avec vos valeurs InfinityFree.
