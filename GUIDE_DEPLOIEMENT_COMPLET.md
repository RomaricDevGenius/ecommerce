# Guide complet : déployer le backend Laravel sur un VPS (étape par étape)

Ce guide est fait pour quelqu’un qui n’a jamais déployé. Tu peux le suivre dans l’ordre : chaque section dit **où tu es**, **ce que tu fais**, **quelle commande taper** et **ce qu’elle fait**.

---

## Vue d’ensemble

On va faire **3 grandes parties** :

1. **Partie A** : Mettre le projet sur **GitHub** (depuis ton PC).
2. **Partie B** : Préparer le **VPS Linux** (installer Nginx, PHP, MySQL, etc.).
3. **Partie C** : **Déployer** le projet sur le serveur (cloner, configurer, faire marcher le site).

Ensuite : **Partie D** (ce qui change entre local et production), **Partie E** (HTTPS), **Partie F** (cron pour les tâches planifiées), **Partie G** (comment faire les mises à jour).

**Ce dont tu as besoin :**

- Ton projet Laravel sur ton PC (déjà le cas).
- Un **compte GitHub** (gratuit).
- Un **VPS Linux** (Ubuntu 22.04 recommandé) avec une IP publique.
- (Optionnel) Un **nom de domaine** pointant vers l’IP du VPS.

**Index – Où suis-je ?**

| Partie | Où tu travailles | Résumé |
|--------|------------------|--------|
| **A**  | Sur ton PC       | Mettre le code sur GitHub (A.1 à A.4) |
| **B**  | Sur le VPS (SSH) | Installer Nginx, PHP, MySQL, Composer (B.1 à B.7) |
| **C**  | Sur le VPS (SSH) | Cloner le projet, configurer Laravel, Nginx (C.1 à C.8) |
| **D**  | Lecture          | Différences local / production + config Flutter |
| **E**  | Sur le VPS       | HTTPS avec Certbot |
| **F**  | Sur le VPS       | Cron pour les tâches planifiées (Moov) |
| **G**  | PC + VPS         | Comment faire les mises à jour (git push / git pull) |

---

# PARTIE A : Mettre le projet sur GitHub (sur ton PC)

**Objectif :** Que tout le code soit sur GitHub pour pouvoir le récupérer proprement sur le serveur.

---

## Étape A.1 – Vérifier que `.env` n’est pas envoyé sur GitHub

Le fichier `.env` contient tes mots de passe et clés. Il **ne doit jamais** être sur GitHub.

- Ouvre le fichier **`.gitignore`** à la racine du projet.
- Vérifie qu’il contient bien une ligne **`.env`** (sans chemin devant).  
  Si oui, c’est bon : Git ignorera `.env`.

**À ne pas faire :** Ne supprime pas `.env` du `.gitignore`.

---

## Étape A.2 – Initialiser Git (si ce n’est pas déjà fait)

Sur ton PC, ouvre un terminal dans le dossier du projet (ex. `C:\xampp\htdocs\ecommerce`).

**Commande :**

```bash
git status
```

- Si tu vois des noms de fichiers (ou "not a git repository") : passe à l’étape suivante.
- Si la commande dit **"fatal: not a git repository"**, initialise le dépôt :

```bash
git init
```

**Ce que ça fait :** `git init` crée un dépôt Git dans le dossier actuel.

---

## Étape A.3 – Créer le dépôt sur GitHub

1. Va sur **https://github.com** et connecte-toi.
2. Clique sur **"New repository"** (ou "Create repository").
3. Donne un nom (ex. **ecommerce**).
4. Ne coche **pas** "Add a README" si ton projet a déjà des fichiers (pour éviter les conflits).
5. Clique sur **"Create repository"**.

Tu obtiendras une URL du type :  
`https://github.com/TON-PSEUDO/ecommerce.git`  
Garde cette URL pour la suite.

---

## Étape A.4 – Envoyer le code sur GitHub (première fois)

Dans le même terminal, à la racine du projet :

```bash
git add .
```

**Ce que ça fait :** Met tous les fichiers (sauf ceux dans `.gitignore`) en zone de préparation.

```bash
git commit -m "Premier envoi du projet ecommerce"
```

**Ce que ça fait :** Crée une "photo" du projet (commit) avec le message indiqué.

```bash
git branch -M main
```

**Ce que ça fait :** Renomme la branche principale en `main` (convention GitHub).

```bash
git remote add origin https://github.com/TON-PSEUDO/ecommerce.git
```

**Ce que ça fait :** Associe ton dépôt local au dépôt GitHub. **Remplace** `TON-PSEUDO/ecommerce` par ton vrai dépôt.

```bash
git push -u origin main
```

**Ce que ça fait :** Envoie le code sur GitHub. On te demandera ton identifiant GitHub et un mot de passe (ou un **Personal Access Token** si la 2FA est activée).

**Tu es au bon endroit si :** Sur GitHub, tu vois tous tes dossiers (app, config, routes, etc.) et **pas** de fichier `.env`.

---

# PARTIE B : Préparer le VPS (serveur Linux)

**Objectif :** Avoir un serveur Ubuntu avec Nginx, PHP 8.2, MySQL et Composer installés.

On suppose que tu as un VPS Ubuntu 22.04 et que tu peux te connecter en **SSH** (avec un logiciel comme PuTTY, ou la commande `ssh`).

---

## Étape B.1 – Se connecter au VPS

Sur ton PC (PowerShell ou CMD), ou avec PuTTY :

```bash
ssh root@IP_DU_SERVEUR
```

Remplace **IP_DU_SERVEUR** par l’IP fournie par ton hébergeur (ex. `51.xxx.xxx.xxx`).  
Tu entres le mot de passe (ou la clé) quand c’est demandé.

**Ce que ça fait :** Ouvre une session sur le serveur. Les commandes que tu tapes s’exécutent sur le VPS.

---

## Étape B.2 – Mettre à jour le serveur

Une fois connecté, exécute :

```bash
apt update && apt upgrade -y
```

**Ce que ça fait :**  
- `apt update` : met à jour la liste des paquets.  
- `apt upgrade -y` : installe les mises à jour (-y = oui à tout).  
Peut prendre 1 à 2 minutes.

---

## Étape B.3 – Installer Nginx

```bash
apt install nginx -y
```

**Ce que ça fait :** Installe le serveur web Nginx.

Vérifier que Nginx tourne :

```bash
systemctl status nginx
```

Tu dois voir **"active (running)"**. Pour quitter l’affichage : touche **q**.

---

## Étape B.4 – Installer PHP 8.2 et les extensions nécessaires

Laravel a besoin de PHP 8.2 (ou 8.3) et de plusieurs extensions.

```bash
apt install software-properties-common -y
add-apt-repository ppa:ondrej/php -y
apt update
apt install php8.2-fpm php8.2-mysql php8.2-xml php8.2-mbstring php8.2-curl php8.2-zip php8.2-gd php8.2-intl php8.2-bcmath unzip -y
```

**Ce que ça fait :**  
- Ajoute le dépôt qui fournit PHP 8.2.  
- Installe PHP 8.2 avec FPM (pour Nginx) et toutes les extensions demandées par Laravel (MySQL, XML, mbstring, curl, zip, gd, intl, bcmath).

Vérifier la version de PHP :

```bash
php -v
```

Tu dois voir quelque chose comme **PHP 8.2.x**.

---

## Étape B.5 – Installer MySQL (base de données)

```bash
apt install mysql-server -y
```

**Ce que ça fait :** Installe le serveur MySQL.

Sécuriser MySQL (mot de passe root, etc.) :

```bash
mysql_secure_installation
```

- Tu peux répondre **Y** à tout, et définir un **mot de passe fort** pour l’utilisateur `root` de MySQL. **Note ce mot de passe.**

Créer une base et un utilisateur pour Laravel :

```bash
mysql -u root -p
```

(Entre le mot de passe root MySQL que tu viens de définir.)

Dans le prompt MySQL, exécute (en adaptant **MOT_DE_PASSE** par un mot de passe que tu choisis) :

```sql
CREATE DATABASE ecommerce_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'ecommerce_user'@'localhost' IDENTIFIED BY 'MOT_DE_PASSE';
GRANT ALL PRIVILEGES ON ecommerce_db.* TO 'ecommerce_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

**Ce que ça fait :**  
- Crée la base **ecommerce_db**.  
- Crée l’utilisateur **ecommerce_user** avec le mot de passe que tu as mis.  
- Donne tous les droits sur **ecommerce_db** à cet utilisateur.

---

## Étape B.6 – Installer Composer

Composer permet d’installer les dépendances PHP du projet (Laravel, etc.).

```bash
cd /tmp
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer
```

**Ce que ça fait :** Télécharge Composer et le met dans un dossier accessible partout.

Vérifier :

```bash
composer -V
```

Tu dois voir un numéro de version (ex. Composer version 2.x).

---

## Étape B.7 – Vérification rapide

Récap de ce qui doit être en place :

| Élément   | Commande de vérification | Attendu        |
|----------|---------------------------|-----------------|
| Nginx    | `systemctl status nginx`  | active (running)|
| PHP      | `php -v`                  | PHP 8.2.x       |
| MySQL    | `systemctl status mysql`  | active (running)|
| Composer | `composer -V`             | Version 2.x     |

**Tu es au bon endroit si :** Ces quatre éléments sont OK. On peut passer au déploiement du projet.

---

# PARTIE C : Déployer l’application sur le serveur

**Objectif :** Récupérer le code depuis GitHub, installer les dépendances, configurer Laravel et faire répondre le site via Nginx.

---

## Étape C.1 – Choisir le dossier du site et cloner le projet

On met le site dans **/var/www/ecommerce**.

```bash
cd /var/www
```

**Ce que ça fait :** Va dans le dossier où on met les sites web.

```bash
git clone https://github.com/TON-PSEUDO/ecommerce.git
```

**Remplace** `TON-PSEUDO/ecommerce` par l’URL réelle de ton dépôt GitHub.  
Si le dépôt est **privé**, GitHub demandera un identifiant et un **Personal Access Token** (à créer dans GitHub : Settings → Developer settings → Personal access tokens).

**Ce que ça fait :** Copie tout le code du dépôt dans `/var/www/ecommerce`.

Vérifier :

```bash
ls -la /var/www/ecommerce
```

Tu dois voir les dossiers **app**, **config**, **routes**, **vendor** (absent pour l’instant), etc.

**Important – Dossier `public` et point d’entrée :**  
En local (XAMPP), ton projet a peut-être **index.php à la racine**. Sur le serveur, Nginx **doit** pointer vers le dossier **public** (pour la sécurité : on n’expose pas .env, .git, etc.). Il faut donc que **public/index.php** existe et charge Laravel depuis le dossier parent.

- Si **public/index.php** existe déjà après le clone : ne rien faire.
- Si le dossier **public** est vide ou sans index.php, crée-le sur le serveur :

```bash
nano /var/www/ecommerce/public/index.php
```

Colle exactement ce contenu (c’est le point d’entrée Laravel standard) :

```php
<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
```

Enregistre (**Ctrl+O**, **Entrée**) et quitte (**Ctrl+X**).  
Ensuite : `chown www-data:www-data /var/www/ecommerce/public/index.php`

---

## Étape C.2 – Installer les dépendances PHP (Composer)

```bash
cd /var/www/ecommerce
composer install --no-dev --optimize-autoloader
```

**Ce que ça fait :**  
- Télécharge toutes les librairies PHP du projet (Laravel, etc.).  
- `--no-dev` : n’installe pas les outils de développement (tests, debug).  
- `--optimize-autoloader` : optimise le chargement des classes en production.

Cela peut prendre 1 à 2 minutes.

---

## Étape C.3 – Créer le fichier .env

Le fichier `.env` n’est pas sur GitHub. On le crée à partir de l’exemple.

```bash
cp .env.example .env
```

**Ce que ça fait :** Copie `.env.example` en `.env`.

Ouvrir le fichier pour le modifier :

```bash
nano .env
```

Tu dois modifier **au minimum** les lignes suivantes (adapte les valeurs à ton serveur et ton domaine) :

| Variable      | En local (exemple)           | En production (à mettre)                    |
|---------------|------------------------------|---------------------------------------------|
| APP_ENV       | local                        | **production**                              |
| APP_DEBUG     | true                         | **false**                                   |
| APP_URL       | http://localhost/ecommerce   | **https://tondomaine.com** (ou http://IP)    |
| DB_DATABASE   | ecommerce_db                 | **ecommerce_db** (ou le nom que tu as mis)  |
| DB_USERNAME   | root                         | **ecommerce_user**                          |
| DB_PASSWORD   | (vide)                       | **MOT_DE_PASSE** (celui de ecommerce_user) |

- Pour éditer dans `nano` : déplace-toi avec les flèches, modifie, puis **Ctrl+O** pour enregistrer, **Entrée**, puis **Ctrl+X** pour quitter.

---

## Étape C.4 – Générer la clé Laravel

```bash
php artisan key:generate
```

**Ce que ça fait :** Génère une clé secrète et la met dans `.env` (variable `APP_KEY`). Laravel en a besoin pour les sessions et le chiffrement.

---

## Étape C.5 – Base de données, stockage et cache

Exécuter les migrations (créer les tables) :

```bash
php artisan migrate --force
```

**Ce que ça fait :** Crée toutes les tables dans la base **ecommerce_db**. `--force` est nécessaire en production.

Créer le lien symbolique pour les fichiers uploadés (images, etc.) :

```bash
php artisan storage:link
```

**Ce que ça fait :** Fait en sorte que le dossier `storage/app/public` soit accessible via l’URL `/storage`. Sans ça, les images uploadées ne s’affichent pas.

Mettre en cache la configuration et les routes (recommandé en production) :

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

**Ce que ça fait :** Enregistre la config, les routes et les vues en cache pour que le site soit plus rapide.

---

## Étape C.6 – Permissions

Nginx et PHP tournent sous l’utilisateur **www-data**. Il doit pouvoir écrire dans **storage** et **bootstrap/cache**.

```bash
chown -R www-data:www-data /var/www/ecommerce
chmod -R 755 /var/www/ecommerce
chmod -R 775 /var/www/ecommerce/storage /var/www/ecommerce/bootstrap/cache
```

**Ce que ça fait :**  
- Donne la propriété du projet à **www-data**.  
- 755 = lecture + exécution pour tous, écriture pour le propriétaire.  
- 775 sur **storage** et **bootstrap/cache** = www-data peut écrire (logs, cache, uploads).

---

## Étape C.7 – Configurer Nginx (dire à Nginx où est le site)

**Point très important :** La "racine web" doit être le dossier **public** du projet, pas la racine du projet. C’est le dossier **public** qui contient `index.php` (point d’entrée Laravel).

Créer le fichier de configuration du site :

```bash
nano /etc/nginx/sites-available/ecommerce
```

Colle **exactement** ce contenu (en remplaçant **tondomaine.com** par ton domaine ou l’IP du serveur) :

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name tondomaine.com www.tondomaine.com;
    root /var/www/ecommerce/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        fastcgi_read_timeout 300;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

- **server_name** : mets ton domaine (ex. **monsite.com**) ou l’**IP du VPS** si tu n’as pas de domaine (ex. **51.xxx.xxx.xxx**).  
- **root** : doit rester **/var/www/ecommerce/public** (obligatoire pour Laravel).

Enregistre (**Ctrl+O**, **Entrée**) et quitte (**Ctrl+X**).

Activer le site :

```bash
ln -s /etc/nginx/sites-available/ecommerce /etc/nginx/sites-enabled/
```

**Ce que ça fait :** Crée un lien symbolique pour que Nginx charge cette config.

Tester la configuration Nginx :

```bash
nginx -t
```

Tu dois voir **"syntax is ok"** et **"test is successful"**.

Recharger Nginx :

```bash
systemctl reload nginx
```

**Ce que ça fait :** Applique la nouvelle configuration sans couper le service.

---

## Étape C.8 – Vérifier que le site répond

- Si tu as un **domaine** pointant vers l’IP du serveur : ouvre **http://tondomaine.com** dans ton navigateur.  
- Sinon : ouvre **http://IP_DU_SERVEUR** (ex. http://51.xxx.xxx.xxx).

Tu dois voir la page d’accueil de ton site Laravel (ou la page de connexion admin).  
Si tu vois une erreur 502 ou une page blanche : vérifier les logs :

```bash
tail -50 /var/log/nginx/error.log
tail -50 /var/www/ecommerce/storage/logs/laravel.log
```

**Tu es au bon endroit si :** Le site s’affiche correctement en HTTP.

---

# PARTIE D : Ce qui change entre local et production

En local (XAMPP), tu avais par exemple :

- **APP_URL** = `http://localhost/ecommerce`
- **APP_DEBUG** = true
- **DB_** = base locale

Sur le serveur, tu as changé ça dans le **.env** (Partie C.3). Résumé :

| Élément        | Rôle |
|----------------|------|
| **APP_URL**    | Doit être l’URL réelle du site (ex. **https://tondomaine.com**). Toutes les URLs générées (liens, images, API) en dépendent. |
| **APP_DEBUG**  | En **false** en production : pas d’affichage des erreurs détaillées (sécurité). |
| **DB_***       | Doit pointer vers la base MySQL **sur le serveur** (ecommerce_db, ecommerce_user, etc.). |
| **FORCE_HTTPS**| Une fois le SSL en place, tu peux mettre **"On"** dans .env si ton app le gère, pour forcer les liens en https. |

**Pour l’app Flutter (mobile) :**  
Quand le backend est en production, il faudra changer dans l’app Flutter l’URL de l’API : au lieu de `10.0.2.2/ecommerce` ou `localhost/ecommerce`, mettre **https://tondomaine.com** (ou l’URL de ton API). C’est dans la config (ex. **DOMAIN_PATH** ou **baseUrl**) du projet Flutter.

---

# PARTIE E : Mettre en place HTTPS (recommandé)

Pour avoir **https://** et un cadenas dans le navigateur, on utilise **Let’s Encrypt** (gratuit).

**Condition :** Avoir un **nom de domaine** qui pointe déjà vers l’IP du serveur (enregistre A vers l’IP du VPS).

Sur le serveur :

```bash
apt install certbot python3-certbot-nginx -y
certbot --nginx -d tondomaine.com -d www.tondomaine.com
```

**Ce que ça fait :** Certbot installe un certificat SSL et modifie la config Nginx pour utiliser HTTPS. Tu réponds aux questions (email, accepter les conditions). À la fin, ton site sera accessible en **https://tondomaine.com**.

Ensuite, dans **.env** :

- **APP_URL=https://tondomaine.com**
- Si ton app le supporte : **FORCE_HTTPS="On"**

Puis re-cacher la config :

```bash
cd /var/www/ecommerce && php artisan config:cache
```

---

# PARTIE F : Planificateur (cron) – obligatoire pour ton projet

Ton application Laravel a une tâche planifiée : **moov:handle-transactions** (toutes les minutes). Pour que ça tourne, il faut que le **scheduler** Laravel soit exécuté chaque minute par le cron du serveur.

Sur le serveur :

```bash
crontab -u www-data -e
```

Si on te demande un éditeur, choisis **nano** (souvent le 1).

Ajoute **une seule ligne** à la fin du fichier :

```
* * * * * cd /var/www/ecommerce && php artisan schedule:run >> /dev/null 2>&1
```

Enregistre et quitte (**Ctrl+O**, **Entrée**, **Ctrl+X**).

**Ce que ça fait :** Toutes les minutes, le cron exécute `php artisan schedule:run`, qui lance les commandes planifiées (dont **moov:handle-transactions**).

---

# PARTIE G : Faire des mises à jour (après déploiement)

Quand tu modifies le code **sur ton PC** et que tu veux que le serveur ait la nouvelle version :

---

## Sur ton PC (après avoir modifié le code)

```bash
cd C:\xampp\htdocs\ecommerce
git add .
git commit -m "Description courte de la modif"
git push origin main
```

**Ce que ça fait :** Envoie tes changements sur GitHub.

---

## Sur le serveur (récupérer et appliquer la nouvelle version)

Connecte-toi en SSH au VPS, puis :

```bash
cd /var/www/ecommerce
git pull origin main
```

**Ce que ça fait :** Récupère la dernière version du code depuis GitHub.

```bash
composer install --no-dev --optimize-autoloader
```

**Ce que ça fait :** Met à jour les paquets PHP si tu as changé **composer.json** ou **composer.lock**.

Si tu as ajouté ou modifié des migrations (fichiers dans **database/migrations**) :

```bash
php artisan migrate --force
```

**Ce que ça fait :** Applique les changements de structure de base de données.

Ensuite, vider et recréer les caches :

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

**Ce que ça fait :** Remet la config, les routes et les vues en cache pour la production.

Optionnel (si tu as changé des permissions ou des propriétaires) :

```bash
chown -R www-data:www-data /var/www/ecommerce
chmod -R 775 /var/www/ecommerce/storage /var/www/ecommerce/bootstrap/cache
```

**En résumé – à chaque mise à jour sur le serveur :**

1. `git pull origin main`
2. `composer install --no-dev --optimize-autoloader`
3. `php artisan migrate --force` (seulement s’il y a de nouvelles migrations)
4. `php artisan config:cache` puis `php artisan route:cache` puis `php artisan view:cache`

---

# Récapitulatif : ordre des étapes

| Phase | Où | Quoi |
|-------|-----|-----|
| A    | PC | Mettre le projet sur GitHub (.env ignoré, git add, commit, push) |
| B    | VPS | Installer Nginx, PHP 8.2, MySQL, Composer |
| C    | VPS | Cloner le repo, composer install, .env, key:generate, migrate, storage:link, permissions, config Nginx (root = public), reload nginx |
| D    | -  | Comprendre APP_URL, APP_DEBUG, DB, et config Flutter pour l’API en prod |
| E    | VPS | Certbot pour HTTPS |
| F    | VPS | Crontab www-data pour schedule:run |
| G    | PC + VPS | Mises à jour : push sur GitHub, puis sur le serveur pull + composer + migrate si besoin + caches |

En suivant ce guide dans l’ordre, tu devrais aboutir à un backend Laravel correctement déployé sur ton VPS avec Nginx, puis à des mises à jour simples via GitHub.
