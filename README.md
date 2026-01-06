# Secure File Submission - WordPress Plugin

Plugin WordPress per la raccolta sicura di file ZIP fino a 500MB con validazione dati anagrafici e codice fiscale italiano.

## Funzionalità

### Frontend
- Form di submission con campi anagrafici completi
- Validazione codice fiscale italiano con algoritmo ufficiale
- Validazione email con controllo DNS MX records
- Blocco email temporanee/usa e getta
- Upload file ZIP fino a 500MB
- Progress bar upload in tempo reale
- Google reCAPTCHA v3 integrato
- Controllo spazio disco disponibile
- Responsive design

### Backend
- Lista submissions con ricerca e paginazione
- Download sicuro file (solo per utenti autorizzati)
- Eliminazione submissions con file
- Ruolo WordPress personalizzato "File Submission Manager"
- Dashboard con statistiche spazio disco
- Configurazione completa via pannello admin
- Email automatiche (admin + utente)
- Alert email quando lo spazio disco è insufficiente

### Sicurezza
- File protetti da download diretto (.htaccess)
- Validazione server-side completa
- Nonce WordPress per CSRF protection
- Verifica MIME type file
- Sanitizzazione tutti gli input
- Download con token temporaneo

## Requisiti

- WordPress 5.0+
- PHP 7.4+ (consigliato 8.0+)
- MySQL 5.7+
- Estensioni PHP: `fileinfo`, `zip`
- Almeno 2GB spazio disco disponibile

### Configurazione Server Raccomandata

Nel file `php.ini` o `.htaccess`:

```ini
upload_max_filesize = 512M
post_max_size = 512M
max_execution_time = 600
max_input_time = 600
memory_limit = 256M
```

## Installazione

1. **Carica il plugin:**
   - Carica la cartella `secure-file-submission` nella directory `/wp-content/plugins/`
   - Oppure carica il file ZIP tramite WordPress admin (Plugin > Aggiungi nuovo > Carica plugin)

2. **Attiva il plugin:**
   - Vai su Plugin > Plugin installati
   - Trova "Secure File Submission" e clicca su "Attiva"

3. **Configurazione iniziale:**
   - Vai su "File Submissions > Impostazioni"
   - Configura le chiavi reCAPTCHA (se desideri usarlo)
   - Verifica email amministratore
   - Imposta dimensione massima file e spazio minimo libero

4. **Aggiungi il form a una pagina:**
   - Crea una nuova pagina o modifica una esistente
   - Inserisci lo shortcode: `[secure_file_form]`
   - Pubblica la pagina

## Configurazione

### Google reCAPTCHA v3 (Opzionale ma Raccomandato)

1. Vai su https://www.google.com/recaptcha/admin
2. Registra un nuovo sito con reCAPTCHA v3
3. Copia Site Key e Secret Key
4. Inseriscile in "File Submissions > Impostazioni"

### Email

Il plugin invia automaticamente due email:
- **Admin:** Notifica con tutti i dati e link download
- **Utente:** Conferma ricezione submission

Puoi personalizzare gli oggetti delle email nelle impostazioni.

Se le email non vengono inviate, considera l'installazione di un plugin SMTP come "WP Mail SMTP".

## Shortcode

### Shortcode Base
```
[secure_file_form]
```

### Con Titolo Personalizzato
```
[secure_file_form title="Invia la tua documentazione"]
```

## Gestione Ruoli e Permessi

Il plugin crea un ruolo personalizzato chiamato **File Submission Manager** con il permesso `manage_file_submissions`.

### Assegnare il Ruolo a un Utente

1. Vai su "Utenti" nel pannello WordPress
2. Modifica l'utente desiderato
3. Seleziona "File Submission Manager" come ruolo
4. Salva

Gli amministratori hanno automaticamente accesso a tutte le funzionalità.

## Utilizzo

### Per gli Amministratori

1. **Visualizzare le submissions:**
   - Vai su "File Submissions" nel menu admin
   - Usa la ricerca per filtrare per nome, codice fiscale o email

2. **Visualizzare i dettagli:**
   - Clicca su "Visualizza dettagli" sotto il nome

3. **Scaricare un file:**
   - Clicca sul pulsante "Download" (richiede login WordPress)

4. **Eliminare una submission:**
   - Clicca sul pulsante "Elimina" (conferma richiesta)
   - Questo elimina sia il record database che il file dal server

### Per gli Utenti

1. Compilare tutti i campi obbligatori
2. Selezionare il file ZIP (max 500MB)
3. Cliccare su "Invia Documentazione"
4. Attendere il completamento dell'upload
5. Ricevere email di conferma

## Validazioni

Il plugin effettua le seguenti validazioni:

### Codice Fiscale
- Lunghezza: 16 caratteri
- Formato: corretto secondo standard italiano
- Carattere di controllo: validato con algoritmo ufficiale
- Unicità: non può essere usato due volte

### Email
- Formato: validazione RFC standard
- DNS: controllo MX records del dominio
- Disposable: blocco email temporanee comuni

### File
- Estensione: solo `.zip`
- MIME type: verifica contenuto reale
- Dimensione: max configurabile (default 500MB)
- Spazio disco: controllo spazio disponibile prima dell'upload

## Manutenzione

### Pulizia File Vecchi

Il plugin NON elimina automaticamente i file vecchi per motivi di sicurezza. È consigliato:

1. Impostare una policy di retention (es: 30 giorni)
2. Eliminare manualmente le submissions obsolete dal pannello admin
3. Oppure creare un cron job personalizzato

### Backup

I file caricati si trovano in:
```
/wp-content/uploads/secure-submissions/
```

Assicurati di includerli nei backup del sito.

### Monitoraggio Spazio Disco

Controlla regolarmente la sezione "Informazioni Sistema" nelle impostazioni per verificare:
- Spazio totale utilizzato
- Spazio libero disponibile

## Risoluzione Problemi

### Upload fallisce con "Maximum upload size exceeded"

**Soluzione:** Aumenta i limiti PHP:
```ini
upload_max_filesize = 512M
post_max_size = 512M
```

### Email non vengono inviate

**Soluzione:** 
1. Verifica che le email di WordPress funzionino (test con password reset)
2. Installa "WP Mail SMTP" per configurare SMTP

### Errore "Spazio disco insufficiente"

**Soluzione:**
1. Elimina vecchie submissions non più necessarie
2. Aumenta lo spazio disco del server
3. Riduci il "Spazio Minimo Libero" nelle impostazioni (con cautela)

### File non scaricabili

**Verifica:**
1. L'utente deve essere loggato in WordPress
2. L'utente deve avere il permesso `manage_file_submissions`
3. Il file deve esistere sul server

### reCAPTCHA non funziona

**Verifica:**
1. Site Key e Secret Key corrette
2. Dominio registrato correttamente su Google reCAPTCHA
3. Console browser per errori JavaScript

## Disinstallazione

Se disattivi il plugin:
- I dati nel database vengono CONSERVATI
- I file caricati vengono CONSERVATI
- Il ruolo personalizzato viene RIMOSSO

Per eliminare completamente tutti i dati:
1. Vai in "File Submissions" e elimina tutte le submissions
2. Elimina manualmente la cartella `/wp-content/uploads/secure-submissions/`
3. Disattiva ed elimina il plugin
4. (Opzionale) Esegui query SQL per eliminare la tabella:
```sql
DROP TABLE IF EXISTS wp_sfs_submissions;
```

## Supporto

Per problemi o domande:
- Controlla questo README
- Verifica i log di errore PHP
- Controlla la console browser per errori JavaScript

## Crediti

**Validazione Codice Fiscale:** Basato sull'algoritmo ufficiale italiano
**reCAPTCHA:** Google Inc.

## Changelog

### Version 1.0.0
- Release iniziale
- Form frontend con tutti i campi richiesti
- Validazione codice fiscale e email
- Upload file ZIP fino a 500MB
- Google reCAPTCHA v3
- Backend admin completo
- Ruolo personalizzato
- Sistema email automatico
- Controllo spazio disco

## Licenza

GPL v2 or later

---

**Sviluppato con ❤️ per la gestione sicura di submission documentali**
