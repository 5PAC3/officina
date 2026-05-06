# Officina - Sistema Gestionale

Web app per la gestione di officine automobilistiche, ricerca servizi e ricambi.

## Stack

- Backend: PHP 8.x (Vanilla, no framework)
- Database: MySQL
- Frontend: HTML/CSS/JS vanilla
- Auth: PHPMailer (Gmail SMTP)

## Struttura

```
/api/           endpoint PHP (17 file)
/classes/       database.php, mailer.php, otp.php
/configs/       config.php
/css/           style.css
/js/            main.js, config.js (generato da .env)
*.html          pagine frontend
db.sql          schema + dati
```

## Database (14 tabelle)

- `officina` - anagrafica officine
- `dipendente` - admin/tecnico/magazziniere (ruolo + hash password)
- `cliente` - clienti registrati (OTP, isActive)
- `autoveicolo` - veicoli clienti
- `servizio` - servizi offerti
- `pezzo_ricambio` - pezzi di ricambio
- `accessorio` - accessori
- `offre` - servizi per officina
- `presenza_pezzo` / `presenza_accessorio` - magazzino per officina
- `intervento` - interventi effettuati
- `comprende_servizio` / `utilizza_pezzo` / `utilizza_accessorio` - dettaglio intervento
- `storico_movimenti` - storico carichi/scarichi magazzino

> **Attenzione:** Il file `db.sql` è stato aggiornato con la nuova tabella `storico_movimenti`. È necessario eseguire manualmente la migration sul database di produzione:
>
> ```sql
> CREATE TABLE storico_movimenti (
>     id INT PRIMARY KEY AUTO_INCREMENT,
>     officina_codice VARCHAR(10),
>     tipo ENUM('pezzo', 'accessorio'),
>     codice VARCHAR(10),
>     quantita INT,
>     operazione ENUM('carico', 'scarico', 'intervento'),
>     eseguito_da INT,
>     data_movimento DATETIME DEFAULT CURRENT_TIMESTAMP,
>     nota TEXT,
>     FOREIGN KEY (officina_codice) REFERENCES officina(codice) ON DELETE SET NULL,
>     FOREIGN KEY (eseguito_da) REFERENCES dipendente(id) ON DELETE SET NULL
> );
> ```

## Ruoli

| Ruolo | Permessi |
|-------|----------|
| admin | Gestione servizi/pezzi, dashboard completa |
| tecnico | Visualizza interventi assegnati |
| magazziniere | Gestione scorte magazzino |
| cliente | Registrazione, visualizza propri veicoli |

## Dipendenze

- [PHPMailer](https://github.com/PHPMailer/PHPMailer) (v6.x) - Libreria per l'invio email
  - Posizione: `vendor/PHPMailer-master/`
  - SMTP configurato: Gmail (smtp.gmail.com:465)

## Da implementare

- [x] Sistema ruoli completo (admin/tecnico/magazziniere/cliente)
- [x] Registrazione e login utenti (clienti)
- [x] Logout per tutti gli utenti
- [x] Dashboard tecnico (visualizza interventi)
- [x] Dashboard magazziniere (gestione scorte)
- [x] Pagina listino prezzi per officina (pubblico)
- [x] Elenco officine con magazzino (pubblico)
- [x] UI per associare servizi/pezzi alle officine
- [x] Supporto servizi/pezzi non associati a officine
- [x] Aggiungere in modo decente al readme che viene utilizzato PHPMailer
- [x] Modifiche al DB per mails codici OTP (UUID) verifica, account attivato si/no...
- [x] Funzione php per creazione di codici OTP
- [x] Pagina conferma verify
- [x] Pagina di reset password 
- [x] completare la parte del magazziniere
- [x] utenti tipologia magazziniere
- [x] associato ad 1 sola officina (filtro applicato in API + storico movimenti)
- [x] aggiungere rimuovere i n. pezzi di ricambio (tipo acquista o vende)
- [x] i pezzi sono tutti quelli presenti nel catalogo non nella singola officina


