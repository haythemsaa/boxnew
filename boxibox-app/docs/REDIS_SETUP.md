# Configuration Redis pour BoxiBox

## Pourquoi Redis?

Redis améliore significativement les performances:
- **Cache**: 10-100x plus rapide que le cache fichier
- **Sessions**: Sessions partagées pour multi-serveur
- **Queues**: Traitement plus rapide des jobs en arrière-plan
- **Rate Limiting**: Protection contre les abus

## Installation Redis (Debian/Ubuntu)

```bash
# Installer Redis
sudo apt update
sudo apt install redis-server

# Activer et démarrer Redis
sudo systemctl enable redis-server
sudo systemctl start redis-server

# Vérifier l'installation
redis-cli ping
# Devrait retourner: PONG
```

## Configuration .env

Ajoutez ces lignes à votre fichier `.env`:

```env
# Redis Configuration
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Utiliser Redis pour le cache (recommandé)
CACHE_DRIVER=redis

# Utiliser Redis pour les sessions (recommandé)
SESSION_DRIVER=redis

# Utiliser Redis pour les queues (optionnel, database fonctionne bien)
QUEUE_CONNECTION=redis
```

## Configuration Sécurité Redis (Production)

1. Éditez `/etc/redis/redis.conf`:

```conf
# Écouter uniquement sur localhost
bind 127.0.0.1

# Définir un mot de passe
requirepass your-secure-password-here

# Désactiver les commandes dangereuses
rename-command FLUSHDB ""
rename-command FLUSHALL ""
rename-command CONFIG ""
```

2. Mettez à jour le `.env`:

```env
REDIS_PASSWORD=your-secure-password-here
```

3. Redémarrez Redis:

```bash
sudo systemctl restart redis-server
```

## Vérification

```bash
# Vérifier la connexion depuis Laravel
php artisan tinker
>>> Cache::store('redis')->put('test', 'value', 60);
>>> Cache::store('redis')->get('test');
# Devrait retourner: "value"
```

## Surveillance

```bash
# Monitorer Redis en temps réel
redis-cli monitor

# Statistiques Redis
redis-cli info stats
```

## Impact Performance Estimé

| Métrique | Avant (File) | Après (Redis) |
|----------|--------------|---------------|
| Lecture cache | 5-10ms | 0.1-0.5ms |
| Écriture cache | 10-20ms | 0.2-1ms |
| Sessions | 5-15ms | 0.1-0.5ms |
| Requêtes/sec supportées | ~100 | ~1000+ |
