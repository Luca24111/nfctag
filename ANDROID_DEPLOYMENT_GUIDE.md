# 📱 Guida Deploy Android - Sistema RFID

## 🎯 **Obiettivo**
Trasferire il sistema RFID testato su Mac e farlo funzionare su Android per la tua app mobile.

## 📋 **Passi per il Deploy su Android**

### **1. Preparazione File**

#### **A. File da Trasferire:**
```
android_rfid_final_test.html  ← Test finale per Android
templates/nfc/index.html.twig ← Codice RFID della tua app
```

#### **B. Metodi di Trasferimento:**
- **Email**: Invia i file a te stesso
- **Cloud**: Google Drive, Dropbox, iCloud
- **USB**: Copia su chiavetta e trasferisci
- **Web**: Carica su server web temporaneo

### **2. Test su Android**

#### **A. Apri il Test:**
1. **Trasferisci** `android_rfid_final_test.html` sul telefono
2. **Apri Chrome** o Firefox su Android
3. **Vai su** `file:///path/to/android_rfid_final_test.html`
4. **Oppure** carica su un server web e accedi via URL

#### **B. Verifica Funzionamento:**
1. **Controlla** le informazioni del dispositivo
2. **Seleziona "RFID"** dallo switch
3. **Collega il lettore** USB-C
4. **Scansiona un tag** e osserva i log

### **3. Integrazione nella Tua App**

#### **A. Se il Test Funziona:**
Il codice è pronto per essere integrato nella tua app mobile.

#### **B. Se il Test NON Funziona:**
Potrebbe servire un approccio diverso.

## 🔧 **Alternative per Android**

### **1. Web App (PWA)**
```javascript
// Manifest per PWA
{
  "name": "Tagly RFID App",
  "short_name": "Tagly",
  "start_url": "/nfc",
  "display": "standalone",
  "background_color": "#1a1a1a",
  "theme_color": "#34c759"
}
```

### **2. App Nativa (Capacitor)**
```bash
# Installa Capacitor
npm install @capacitor/core @capacitor/cli

# Inizializza progetto
npx cap init

# Aggiungi piattaforma Android
npx cap add android

# Build e sync
npx cap build
npx cap sync android
```

### **3. App Ibrida (Cordova)**
```bash
# Installa Cordova
npm install -g cordova

# Crea progetto
cordova create tagly-rfid-app

# Aggiungi piattaforma Android
cordova platform add android

# Build
cordova build android
```

## 📱 **Test Specifici per Android**

### **1. Test Browser Mobile**
- ✅ **Chrome per Android**: Migliore supporto WebUSB
- ✅ **Firefox per Android**: Alternativa valida
- ❌ **Safari**: Non supporta WebUSB

### **2. Test Hardware**
- ✅ **Lettori HID**: Funzionano come tastiera
- ✅ **USB-C**: Supporto nativo Android
- ⚠️ **Permessi**: Potrebbero essere richiesti

### **3. Test Permessi**
```javascript
// Richiedi permessi USB
if ('usb' in navigator) {
    try {
        await navigator.usb.requestDevice({
            filters: []
        });
        console.log('Permessi USB concessi');
    } catch (error) {
        console.log('Permessi USB negati:', error);
    }
}
```

## 🚀 **Deploy Finale**

### **1. Opzione Web (Raccomandata)**
```bash
# Carica su server web
# Esempio: Netlify, Vercel, GitHub Pages
# URL: https://tuo-dominio.com/nfc
```

### **2. Opzione App Nativa**
```bash
# Build APK
npx cap build android

# APK generato in:
# android/app/build/outputs/apk/debug/app-debug.apk
```

### **3. Opzione PWA**
```javascript
// Service Worker per funzionalità offline
// Manifest per installazione app-like
// HTTPS richiesto per WebUSB
```

## 🔍 **Debug su Android**

### **1. Chrome DevTools**
1. **Collega telefono** al PC via USB
2. **Abilita debug USB** su Android
3. **Apri Chrome DevTools** su PC
4. **Debug remoto** del telefono

### **2. Log Android**
```bash
# Log del sistema
adb logcat

# Log specifici per la tua app
adb logcat | grep "Tagly"
```

### **3. Test Manuale**
```javascript
// Test manuale RFID
function testManualRFID() {
    const testTag = prompt('Inserisci codice RFID:');
    if (testTag) {
        processRFIDTag(testTag);
    }
}
```

## ⚠️ **Problemi Comuni su Android**

### **1. Permessi USB**
**Sintomi:**
- ❌ "USB permission denied"
- ❌ Lettore non riconosciuto

**Soluzioni:**
- ✅ Usa Chrome invece di altri browser
- ✅ Concedi permessi esplicitamente
- ✅ Verifica impostazioni USB del telefono

### **2. Focus Perso**
**Sintomi:**
- ❌ Input RFID non catturato
- ❌ App perde focus

**Soluzioni:**
- ✅ Mantieni focus sull'input nascosto
- ✅ Gestisci eventi di visibilità
- ✅ Usa modalità fullscreen

### **3. Timing Issues**
**Sintomi:**
- ❌ Tag RFID incompleti
- ❌ Caratteri mancanti

**Soluzioni:**
- ✅ Aumenta delay di processamento
- ✅ Implementa buffer robusto
- ✅ Gestisci diversi tipi di lettori

## 🎯 **Prossimi Passi**

### **1. Test Immediato:**
1. **Trasferisci** il file di test su Android
2. **Testa** con il tuo lettore RFID
3. **Verifica** che funzioni correttamente

### **2. Se Funziona:**
1. **Integra** il codice nella tua app
2. **Testa** in ambiente reale
3. **Deploy** finale

### **3. Se NON Funziona:**
1. **Prova** approccio app nativa
2. **Considera** alternative hardware
3. **Valuta** soluzioni ibride

## 📞 **Supporto**

Se hai problemi durante il deploy:
1. **Controlla** i log della console
2. **Verifica** permessi del browser
3. **Testa** con browser diversi
4. **Considera** approccio app nativa

**Il sistema RFID è ora pronto per Android!** 🚀 