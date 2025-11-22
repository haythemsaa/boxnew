# Boxibox Mobile App

Application mobile React Native pour la gestion des contrats de self-stockage Boxibox.

## 🚀 Fonctionnalités

- ✅ Authentification (Login / Register)
- ✅ Tableau de bord avec statistiques
- ✅ Gestion des contrats
- ✅ Consultation des factures
- ✅ Gestion du profil utilisateur
- ✅ Navigation intuitive avec onglets
- ✅ Actualisation des données (Pull to refresh)

## 📋 Prérequis

- Node.js (version 14 ou supérieure)
- npm ou yarn
- Expo CLI (installé automatiquement)
- Un émulateur Android/iOS ou l'application Expo Go sur votre téléphone

## 🛠️ Installation

1. **Cloner le projet** (si ce n'est pas déjà fait)
```bash
cd boxibox-mobile
```

2. **Installer les dépendances**
```bash
npm install
```

3. **Configurer l'URL de l'API**

Ouvrez le fichier `src/constants/config.js` et modifiez l'URL de base de l'API selon votre environnement :

```javascript
export const API_CONFIG = {
  BASE_URL: __DEV__
    ? 'http://YOUR_LOCAL_IP:8000/api/v1'  // Remplacez YOUR_LOCAL_IP par votre IP locale
    : 'https://api.boxibox.fr/api/v1',
};
```

**Important** : Pour tester sur un appareil physique, utilisez votre adresse IP locale (ex: `http://192.168.1.100:8000/api/v1`) au lieu de `localhost`.

## 🏃 Démarrage de l'application

### Démarrer le serveur de développement
```bash
npm start
```

### Lancer sur Android
```bash
npm run android
```

### Lancer sur iOS (Mac uniquement)
```bash
npm run ios
```

### Lancer sur le Web
```bash
npm run web
```

### Utiliser Expo Go (recommandé pour les tests)

1. Installez l'application **Expo Go** sur votre téléphone :
   - [Android - Google Play](https://play.google.com/store/apps/details?id=host.exp.exponent)
   - [iOS - App Store](https://apps.apple.com/app/expo-go/id982107779)

2. Lancez le projet :
```bash
npm start
```

3. Scannez le QR code affiché dans le terminal avec :
   - **Android** : L'application Expo Go
   - **iOS** : L'application Appareil photo native

## 📁 Structure du projet

```
boxibox-mobile/
├── App.js                      # Point d'entrée de l'application
├── src/
│   ├── components/            # Composants réutilisables
│   │   ├── Button.js
│   │   ├── Card.js
│   │   ├── Input.js
│   │   ├── Loading.js
│   │   └── StatusBadge.js
│   ├── constants/            # Constantes et configuration
│   │   ├── colors.js
│   │   └── config.js
│   ├── context/              # Contextes React
│   │   └── AuthContext.js
│   ├── navigation/           # Navigation
│   │   └── AppNavigator.js
│   ├── screens/              # Écrans de l'application
│   │   ├── LoginScreen.js
│   │   ├── RegisterScreen.js
│   │   ├── DashboardScreen.js
│   │   ├── ContractsScreen.js
│   │   ├── ContractDetailsScreen.js
│   │   ├── InvoicesScreen.js
│   │   ├── InvoiceDetailsScreen.js
│   │   └── ProfileScreen.js
│   ├── services/             # Services API
│   │   ├── api.js
│   │   ├── authService.js
│   │   ├── contractService.js
│   │   ├── customerService.js
│   │   └── invoiceService.js
│   └── utils/                # Utilitaires
│       └── storage.js
├── package.json
└── README.md
```

## 🔧 Configuration de l'API Backend

Assurez-vous que votre API Laravel Boxibox est en cours d'exécution et accessible.

### Pour le développement local :

1. Démarrez votre serveur Laravel :
```bash
cd boxibox-app
php artisan serve
```

2. Si vous testez sur un appareil physique, assurez-vous que :
   - Votre ordinateur et votre téléphone sont sur le même réseau WiFi
   - Vous utilisez l'adresse IP locale de votre ordinateur dans `config.js`
   - Le serveur Laravel accepte les connexions depuis cette IP

### Autoriser les connexions externes (Laravel)

Dans `boxibox-app/.env`, assurez-vous que :
```
APP_URL=http://YOUR_LOCAL_IP:8000
```

Démarrez Laravel avec :
```bash
php artisan serve --host=0.0.0.0
```

## 📱 Écrans de l'application

### Écrans d'authentification
- **Login** : Connexion avec email et mot de passe
- **Register** : Création de compte

### Écrans principaux
- **Dashboard** : Vue d'ensemble avec statistiques et contrats actifs
- **Contrats** : Liste de tous les contrats
- **Détails du contrat** : Informations complètes d'un contrat
- **Factures** : Liste de toutes les factures
- **Détails de la facture** : Informations complètes d'une facture
- **Profil** : Informations du compte utilisateur

## 🎨 Personnalisation

### Couleurs

Modifiez les couleurs dans `src/constants/colors.js` :

```javascript
export const COLORS = {
  primary: '#3B82F6',      // Couleur principale
  secondary: '#10B981',    // Couleur secondaire
  // ...
};
```

### API Base URL

Modifiez l'URL de l'API dans `src/constants/config.js`.

## 🐛 Dépannage

### Problème de connexion à l'API

1. Vérifiez que le serveur Laravel est bien démarré
2. Vérifiez l'URL dans `src/constants/config.js`
3. Pour les tests sur appareil physique, utilisez l'IP locale, pas `localhost`
4. Vérifiez les logs dans le terminal Expo

### Erreur de dépendances

```bash
# Supprimer node_modules et réinstaller
rm -rf node_modules
npm install

# Ou avec yarn
rm -rf node_modules
yarn install
```

### Réinitialiser le cache Expo

```bash
expo start -c
# ou
npm start -- --reset-cache
```

## 📦 Build pour production

### Android (APK)

```bash
expo build:android
```

### iOS (IPA - nécessite un compte Apple Developer)

```bash
expo build:ios
```

### Utiliser EAS Build (recommandé)

```bash
# Installer EAS CLI
npm install -g eas-cli

# Login
eas login

# Configurer le projet
eas build:configure

# Build Android
eas build -p android

# Build iOS
eas build -p ios
```

## 🔐 Sécurité

- Les tokens d'authentification sont stockés de manière sécurisée avec `expo-secure-store`
- Les mots de passe ne sont jamais stockés localement
- Toutes les requêtes API utilisent HTTPS en production

## 📚 Documentation complémentaire

- [Documentation Expo](https://docs.expo.dev/)
- [Documentation React Navigation](https://reactnavigation.org/)
- [API Boxibox Documentation](../API_MOBILE.md)

## 🤝 Support

Pour toute question ou problème, contactez l'équipe de développement.

## 📄 Licence

Propriétaire - Boxibox © 2024
