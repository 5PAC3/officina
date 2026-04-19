# Officina - Sistema Gestionale

Web app per la gestione di officine automobilistiche, ricerca servizi e ricambi.

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
- [ ] Pagina di reset password 
