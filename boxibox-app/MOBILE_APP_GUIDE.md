# Boxibox Mobile App - Guide Complet

## Table des matieres
1. [Test de l'application PWA](#test-pwa)
2. [Installation sur iPhone/Android](#installation-pwa)
3. [Creation d'un APK Android avec Capacitor](#creation-apk)
4. [Publication sur les stores](#publication)

---

## 1. Test de l'application PWA {#test-pwa}

### Test en developpement

```bash
# Demarrer le serveur Laravel
cd boxibox-app
php artisan serve --host=0.0.0.0 --port=8000

# Dans un autre terminal, compiler les assets
npm run dev
```

### Acceder a l'application mobile

1. **Sur PC** : Ouvrir `http://localhost:8000/mobile` dans Chrome
2. **Sur mobile** :
   - Connecter votre telephone au meme reseau WiFi
   - Trouver l'IP de votre PC : `ipconfig` (Windows) ou `ifconfig` (Mac/Linux)
   - Ouvrir `http://VOTRE_IP:8000/mobile` sur votre telephone

### Test des fonctionnalites PWA

1. **Mode responsive** : Dans Chrome DevTools (F12), cliquer sur l'icone mobile
2. **Lighthouse** : DevTools > Lighthouse > Generate report (categorie PWA)
3. **Service Worker** : DevTools > Application > Service Workers

---

## 2. Installation sur iPhone/Android {#installation-pwa}

### Installation sur iPhone (Safari)

1. Ouvrir l'URL de l'application dans **Safari**
2. Appuyer sur l'icone **Partager** (carre avec fleche)
3. Faire defiler et appuyer sur **"Sur l'ecran d'accueil"**
4. Donner un nom et appuyer sur **"Ajouter"**

### Installation sur Android (Chrome)

1. Ouvrir l'URL de l'application dans **Chrome**
2. Appuyer sur les **3 points** en haut a droite
3. Appuyer sur **"Installer l'application"** ou **"Ajouter a l'ecran d'accueil"**
4. Confirmer l'installation

### Test de l'installation

- L'application apparait sur l'ecran d'accueil
- Elle s'ouvre en plein ecran (sans barre d'URL)
- Elle fonctionne hors ligne (pages en cache)

---

## 3. Creation d'un APK Android avec Capacitor {#creation-apk}

### Prerequis

1. **Node.js** (v18+)
2. **Java JDK 17+** : [Telechargement](https://adoptium.net/)
3. **Android Studio** : [Telechargement](https://developer.android.com/studio)
4. Configurer les variables d'environnement :

```bash
# Windows (ajouter a Path)
ANDROID_HOME = C:\Users\VOTRE_NOM\AppData\Local\Android\Sdk
JAVA_HOME = C:\Program Files\Eclipse Adoptium\jdk-17.x.x

# Dans Path, ajouter :
%ANDROID_HOME%\platform-tools
%ANDROID_HOME%\tools
```

### Etape 1 : Installer Capacitor

```bash
cd boxibox-app

# Installer Capacitor
npm install @capacitor/core @capacitor/cli
npm install @capacitor/android

# Initialiser Capacitor
npx cap init "Boxibox" "com.boxibox.app" --web-dir=public
```

### Etape 2 : Configurer capacitor.config.ts

Creer/modifier `capacitor.config.ts` :

```typescript
import { CapacitorConfig } from '@capacitor/cli';

const config: CapacitorConfig = {
  appId: 'com.boxibox.app',
  appName: 'Boxibox',
  webDir: 'public',
  server: {
    // Pour le developpement, pointer vers le serveur Laravel
    // url: 'http://192.168.1.x:8000',
    // cleartext: true,

    // Pour la production, utiliser les fichiers locaux
    androidScheme: 'https'
  },
  plugins: {
    SplashScreen: {
      launchShowDuration: 2000,
      backgroundColor: '#4f46e5',
      showSpinner: false,
    },
    StatusBar: {
      style: 'DARK',
      backgroundColor: '#4f46e5'
    },
    PushNotifications: {
      presentationOptions: ['badge', 'sound', 'alert']
    }
  },
  android: {
    allowMixedContent: true,
    captureInput: true,
    webContentsDebuggingEnabled: true // Mettre a false en production
  }
};

export default config;
```

### Etape 3 : Ajouter la plateforme Android

```bash
# Build des assets
npm run build

# Ajouter Android
npx cap add android

# Copier les fichiers web
npx cap copy android

# Synchroniser
npx cap sync android
```

### Etape 4 : Configuration Android

Modifier `android/app/src/main/res/values/strings.xml` :

```xml
<?xml version="1.0" encoding="utf-8"?>
<resources>
    <string name="app_name">Boxibox</string>
    <string name="title_activity_main">Boxibox</string>
    <string name="package_name">com.boxibox.app</string>
    <string name="custom_url_scheme">com.boxibox.app</string>
</resources>
```

### Etape 5 : Generer les icones

1. Creer une icone 1024x1024 pixels (PNG)
2. Utiliser [Android Asset Studio](https://romannurik.github.io/AndroidAssetStudio/icons-launcher.html)
3. Placer les fichiers generes dans `android/app/src/main/res/`

### Etape 6 : Ouvrir dans Android Studio

```bash
npx cap open android
```

### Etape 7 : Generer l'APK de debug

Dans Android Studio :
1. **Build** > **Build Bundle(s) / APK(s)** > **Build APK(s)**
2. L'APK sera dans : `android/app/build/outputs/apk/debug/app-debug.apk`

### Etape 8 : Generer l'APK de release (signe)

1. **Build** > **Generate Signed Bundle / APK**
2. Choisir **APK**
3. Creer un nouveau keystore ou utiliser un existant :
   - Key store path: `boxibox-keystore.jks`
   - Password: (votre mot de passe)
   - Alias: `boxibox`
   - Validity: 25 years
4. Choisir **release**
5. L'APK signe sera dans : `android/app/release/app-release.apk`

### Script automatise (Windows)

Creer `build-apk.bat` :

```batch
@echo off
echo === Build Boxibox APK ===

echo 1. Building web assets...
call npm run build

echo 2. Syncing with Capacitor...
call npx cap sync android

echo 3. Building APK...
cd android
call gradlew assembleDebug
cd ..

echo === Build complete! ===
echo APK location: android\app\build\outputs\apk\debug\app-debug.apk
pause
```

---

## 4. Publication sur les stores {#publication}

### Google Play Store

1. **Compte developpeur** : Creer un compte sur [Google Play Console](https://play.google.com/console) (25$ une fois)
2. **Creer une application** dans la console
3. **Preparer les assets** :
   - Icone 512x512 (PNG)
   - Feature graphic 1024x500
   - Screenshots (min 2)
   - Description courte/longue
4. **Upload l'AAB** (Android App Bundle recommande, pas APK)
5. **Politique de confidentialite** : Obligatoire
6. **Soumission** pour review

### App Store (iOS)

1. **Compte developpeur Apple** : 99$/an
2. **Ajouter la plateforme iOS** :

```bash
npm install @capacitor/ios
npx cap add ios
npx cap open ios
```

3. **Configurer dans Xcode** :
   - Signing & Capabilities
   - Bundle Identifier
   - Version
4. **Archiver et uploader** via Xcode

---

## Commandes utiles

```bash
# Mettre a jour apres modifications
npm run build && npx cap sync

# Ouvrir Android Studio
npx cap open android

# Ouvrir Xcode (Mac seulement)
npx cap open ios

# Voir les logs Android
npx cap run android --livereload --external

# Lancer sur emulateur
npx cap run android

# Lancer sur appareil connecte
npx cap run android --target=DEVICE_ID
```

---

## Troubleshooting

### L'APK ne s'installe pas
- Activer "Sources inconnues" dans Parametres > Securite
- Verifier la version Android minimale (API 22+)

### Ecran blanc
- Verifier que `public/build/` contient les assets
- Executer `npx cap sync android`

### Erreur de build Gradle
- Verifier JAVA_HOME pointe vers JDK 17
- Executer `cd android && gradlew clean`

### Service Worker ne fonctionne pas
- HTTPS requis en production
- Verifier `public/sw.js` existe

---

## Structure des fichiers mobile

```
resources/js/
├── Layouts/
│   └── MobileLayout.vue      # Layout principal mobile
├── Pages/
│   └── Mobile/
│       ├── Dashboard.vue     # Page d'accueil
│       ├── Boxes/
│       │   └── Index.vue     # Liste des box
│       ├── Invoices/
│       │   ├── Index.vue     # Liste factures
│       │   └── Show.vue      # Detail facture
│       ├── Payments/
│       │   ├── Index.vue     # Liste paiements
│       │   └── Show.vue      # Detail paiement
│       ├── Contracts/
│       │   ├── Index.vue     # Liste contrats
│       │   └── Show.vue      # Detail contrat
│       ├── Reserve/
│       │   └── Index.vue     # Reservation
│       ├── Pay/
│       │   └── Index.vue     # Paiement
│       ├── Access/
│       │   └── Index.vue     # Acces au box
│       ├── More/
│       │   └── Index.vue     # Menu Plus
│       ├── Profile/
│       │   └── Index.vue     # Profil utilisateur
│       ├── Documents/
│       │   └── Index.vue     # Documents
│       ├── PaymentMethods/
│       │   └── Index.vue     # Moyens de paiement
│       ├── Settings/
│       │   └── Index.vue     # Parametres
│       ├── Support/
│       │   └── Index.vue     # Support client
│       └── Help/
│           └── Faq.vue       # FAQ
```

---

## Contact & Support

Pour toute question technique, consulter la documentation officielle :
- [Capacitor](https://capacitorjs.com/docs)
- [Laravel](https://laravel.com/docs)
- [Vue.js](https://vuejs.org/guide)
- [Inertia.js](https://inertiajs.com)
