# Guide de Deploiement BoxiBox

## Configuration Serveur

- **Serveur**: 2emeservice.be
- **Chemin**: `/var/www/vhosts/2emeservice.be/httpdocs/boxnew/boxibox-app`

---

## Deploiement Rapide (2 etapes)

### Etape 1: Sur votre ordinateur local

```bash
cd C:/laragon/www/boxnew/boxibox-app
git add .
git commit -m "Description des modifications"
git push origin main
```

### Etape 2: Sur le serveur (SSH)

```bash
cd /var/www/vhosts/2emeservice.be/httpdocs/boxnew/boxibox-app
./deploy-simple.sh
```

---

## Deploiement Manuel (si le script ne fonctionne pas)

```bash
# 1. Se connecter au serveur
ssh user@2emeservice.be

# 2. Aller au projet
cd /var/www/vhosts/2emeservice.be/httpdocs/boxnew/boxibox-app

# 3. Recuperer le code
git pull origin main

# 4. Installer les dependances PHP
composer install --no-dev --optimize-autoloader

# 5. Installer et compiler les assets
npm ci && npm run build

# 6. Migrations (si necessaire)
php artisan migrate --force

# 7. Vider les caches
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 8. Permissions
chmod -R 775 storage bootstrap/cache
```

---

## Commandes Utiles

### Verifier l'etat
```bash
php artisan about
php artisan migrate:status
```

### Logs d'erreurs
```bash
tail -f storage/logs/laravel.log
```

### Rollback migration
```bash
php artisan migrate:rollback
```

### Redemarrer les queues
```bash
php artisan queue:restart
```

---

## Premier Deploiement

Si c'est la premiere installation sur le serveur:

```bash
# 1. Cloner le repo
git clone https://github.com/VOTRE_REPO/boxibox-app.git

# 2. Installer les dependances
composer install
npm ci && npm run build

# 3. Configurer l'environnement
cp .env.example .env
php artisan key:generate

# 4. Editer .env avec les bonnes valeurs
nano .env

# 5. Creer la base de donnees et migrer
php artisan migrate --seed

# 6. Lien storage
php artisan storage:link
```

---

## Checklist Post-Deploiement

- [ ] Verifier que le site est accessible
- [ ] Tester la connexion/deconnexion
- [ ] Verifier les logs d'erreurs
- [ ] Tester les fonctionnalites principales
