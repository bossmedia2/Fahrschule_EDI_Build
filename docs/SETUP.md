# Live-Instanz – Sicherheits-Checkliste (manuell, noch offen)

> **Wichtiger Hinweis:** Diese Session hatte zu keinem Zeitpunkt Zugriff auf die
> echte/live WordPress-Installation (nur eine private Tailnet-Instanz, auf die
> der Kunde ausdrücklich keinen Zugriff gewähren wollte). **Kein einziger Punkt
> dieser Liste wurde von mir durchgeführt** – alles hier ist eine reine
> Anleitung für den Kunden bzw. die betreuende Agentur. `inc/security.php`
> (siehe Theme) deckt bereits die Theme-seitigen Gegenmaßnahmen ab, die ohne
> Live-Zugriff möglich waren; alles unten Stehende erfordert Admin-Zugriff auf
> die echte Installation und wurde **nicht** erledigt.

Checkliste zum Abhaken – Status aller Punkte: ⬜ offen.

---

## 1. Standard-Admin-Benutzer umbenennen/entfernen

- [ ] Neuen, persönlich benannten Administrator-Account anlegen (nicht `admin`/`Admin`).
- [ ] Alle Rechte/Inhalte (Beiträge, Seiten, Medien) auf den neuen Account übertragen.
- [ ] Den alten `admin`/`Admin`-Account anschließend löschen.
- **Warum:** `admin` ist das erste, was jeder automatisierte Login-Bruteforce-Versuch
  ausprobiert. Ein unvorhersehbarer Benutzername entfernt diesen Angriffsvektor komplett.

## 2. REST-User-Enumeration auch auf Plugin-Ebene prüfen

- [ ] Nach Aktivierung aller Plugins erneut `/wp-json/wp/v2/users` (ausgeloggt) aufrufen
      und prüfen, dass **kein** Plugin diesen Endpunkt wieder öffnet (manche SEO-/Member-
      ship-Plugins registrieren eigene, ähnliche Routen).
- **Theme-Ebene bereits erledigt:** `inc/security.php` blockt `/wp/v2/users` bereits
  für nicht angemeldete Besucher. Dieser Punkt ist nur die Kontrolle, dass kein
  Plugin eine eigene, parallele Enumerationsmöglichkeit öffnet.

## 3. Plugin-Updates einspielen

- [ ] **Yoast SEO** (`wordpress-seo`): v28.3 → **≥ v28.4**
- [ ] **Advanced Custom Fields** (`advanced-custom-fields`): v6.8.8 → **≥ v6.8.9**
- [ ] **Matomo** (`matomo`): v5.12.1 → **≥ v5.13.0**
- [ ] **Akismet** und **Hello Dolly** deinstallieren, falls ungenutzt (Standard-WordPress-
      Mitgieferungen, die oft niemand aktiv braucht – jedes ungenutzte Plugin ist
      unnötige Angriffsfläche).

## 4. XML-RPC auch serverseitig blocken

- **Theme-Ebene bereits erledigt:** `inc/security.php` setzt `add_filter( 'xmlrpc_enabled', '__return_false' )`.
- [ ] Zusätzlich serverseitig (Apache/`.htaccess`) sperren, damit der Endpunkt gar
      nicht erst von PHP/WordPress verarbeitet wird:

  ```apache
  <Files xmlrpc.php>
  Order Deny,Allow
  Deny from all
  </Files>
  ```

  In die `.htaccess` im WordPress-Root einfügen (oberhalb des
  `# BEGIN WordPress`-Blocks). Bei **nginx** stattdessen im Server-Block:

  ```nginx
  location = /xmlrpc.php {
      deny all;
  }
  ```

## 5. Aufräumen & Zugriffsschutz

- [ ] `readme.html` im WordPress-Root löschen (verrät sonst die genaue WP-Version).
- [ ] Verzeichnis-Indexierung für Theme- und Plugin-Ordner abschalten
      (`Options -Indexes`, z. B. in `wp-content/themes/.htaccess` und
      `wp-content/plugins/.htaccess`, oder zentral im Root):

  ```apache
  Options -Indexes
  ```

- [ ] Die eigenen `.md`-Dokus dieses Themes (`FARBSYSTEM.md`, `SEO-UND-AUDIT.md`,
      `BLOCKS-PATTERNS.md`, `SYSTEM-REPORT.md`, alles unter `docs/`) genauso vor
      direktem HTTP-Zugriff schützen – sie enthalten interne Entwickler-Notizen,
      die nicht öffentlich einsehbar sein sollten:

  ```apache
  <FilesMatch "\.md$">
  Order Deny,Allow
  Deny from all
  </FilesMatch>
  ```

  (Diese Regel in die `.htaccess` im Theme-Root `wp-content/themes/fahrschule-edi/`
  einfügen.)

## 6. WP-Cron auf echten Server-Cronjob umstellen

- [ ] In `wp-config.php` ergänzen:

  ```php
  define( 'DISABLE_WP_CRON', true );
  ```

- [ ] Stattdessen einen echten Server-Cronjob einrichten, der `wp-cron.php`
      regelmäßig aufruft (z. B. alle 5–15 Minuten), unabhängig vom Besucher-Traffic:

  ```cron
  */10 * * * * curl -s https://www.fahrschule-edi.de/wp-cron.php >/dev/null 2>&1
  ```

- **Warum:** Der Standard-WP-Cron läuft nur bei einem eingehenden Seitenaufruf
  mit – bei wenig Traffic verzögern sich geplante Aufgaben (Backups, Updates,
  geplante Beiträge); bei sehr viel Traffic wird er dagegen bei jedem Aufruf
  unnötig mitgeprüft. Ein echter Server-Cronjob ist zuverlässiger und günstiger.

## 7. Suchmaschinen-Indexierung in Produktion prüfen

- [ ] **Einstellungen → Lesen → „Sichtbarkeit für Suchmaschinen" → „Suchmaschinen
      vom Indexieren dieser Website abhalten"** muss in der **Produktionsumgebung
      ausgeschaltet** sein (Haken **nicht** gesetzt).
- **Warum das hier wichtig ist:** `inc/seo.php`s `robots.txt`-Logik
  (`fahrschule_robots_txt()`, siehe Task 5 im Projekt) prüft genau diesen WordPress-
  Schalter (`$public`) und sperrt bei aktivierter „Diskourage indexing"-Einstellung
  automatisch **die gesamte Website** für Suchmaschinen aus – korrektes Verhalten
  für Staging, aber fatal für die Live-Seite, falls versehentlich aktiviert
  (z. B. nach einem Staging→Live-Umzug, bei dem diese Einstellung oft übernommen
  wird, ohne dass es auffällt).

---

## Kundentexte (PDF) als Abschnitte – Zuordnung

Grundlage: „Texte_Webseite_Fahrschule" (Kundenvorgabe). Alle zehn Abschnitte
stehen wortgleich in `inc/pdf-texts.php` → `fahrschule_pdf_sections()` – die
einzige Quelle für die Ausgabe (`inc/render-callbacks.php`) **und** für die
Standardwerte der Blöcke im Editor (`inc/blocks-config.php`). Im HTML trägt jede
dieser Sektionen `data-pdf-section="F1"` … `"F10"`.

| PDF | Label · Überschrift | Startseite | Unterseite | Block (`fahrschule/…`) |
|---|---|---|---|---|
| F1 | Startseite / Hero · Fahrschule edi – Mit Kompetenz zum Führerschein | Hero, alle vier Absätze | – | `video-hero` |
| F2 | Über uns · Ihre Fahrschule im Ruhrgebiet | vollständig | /ueber-uns/ | `about-intro` |
| F3 | Führerscheinklassen · Unsere Führerscheinklassen | Teaser | /fuehrerscheinklassen/ | `license-overview` |
| F4 | Unsere Leistungen · Alles rund um den Führerschein aus einer Hand | Teaser | /leistungen/ | `services` |
| F5 | Berufskraftfahrer · Ausbildung & Weiterbildung für Berufskraftfahrer | Teaser | /berufskraftfahrer/ | `professional-drivers` |
| F6 | ASF & FES · Aufbauseminare und Punkteabbau | Teaser | /asf-fes/ | `seminars` |
| F7 | Moderne Ausbildung · Lernen mit moderner Technik | Teaser | /moderne-ausbildung/ | `modern-training` |
| F8 | Standorte · Unsere Standorte | vollständig | /standorte/, /anmeldung/; Adressen auch auf den drei Standortseiten | `locations` |
| F9 | Unser Team · Persönlich. Geduldig. Erfahren. | Teaser | /ueber-uns/team/ | `about-team` |
| F10 | Anmeldung · Jetzt bei der Fahrschule edi anmelden | Abschluss | /anmeldung/ | `anmeldung` |

Teaser = Label, Überschrift und erster Satz des Abschnitts (wortgleich) plus Link
auf die Unterseite. Auf den Unterseiten zeigt der Seitenkopf Label und Überschrift
(Schlüssel `pdf` in `inc/pages.php`), der Block darunter läuft mit `head = 0`
ohne doppelte Überschrift. Die Platzierungsangabe „Startseite / Hero" und die
Foto-Hinweise „(Fotos)" werden nicht als Text ausgegeben.

**Weiterhin vorhanden, aber kein PDF-Abschnitt** (jeweils nach den
PDF-Abschnitten; mit dem Kunden klären, ob sie bleiben sollen): Team Spirit
(Startseite, Team-Seite), Kundenbewertungen (blenden sich ohne echte Einträge
aus), CTA „Bereit für Ihren Führerschein?" mit Zahlen (30+, 5000+, 3 Standorte,
5 Sprachen) am Ende vieler Unterseiten, Zeitleiste und Philosophie (Über uns),
Flotte & Einrichtungen (Moderne Ausbildung), Preise & Rechner,
Theorieunterricht, Fahrlehrerausbildung, FAQ, Erfahrungen, Standort-
Detailseiten (Hero-Texte, Rechner, Entfernung).

**Text ändern:** Wortlaut in `inc/pdf-texts.php` anpassen – wirkt auf
Startseite, Unterseite und Editor-Vorgabe zugleich.

## 8. Theme-Update einspielen, Seiten mit eigenem Editor-Inhalt prüfen

- [ ] Theme `fahrschule-edi` in Version 1.1.0 einspielen.
- [ ] Unter **Seiten** jede Seite prüfen, deren Editor **nicht leer** ist. Dort hat
      der Editor-Inhalt Vorrang vor der Registry (`page.php`). Blöcke, die über ein
      Pattern eingefügt oder im Editor bearbeitet wurden, speichern ihre Texte im
      Seiteninhalt und zeigen weiterhin den **alten** Wortlaut. Abhilfe: Block
      entfernen und neu einfügen (übernimmt die neuen Standardwerte) oder den
      Seiteninhalt leeren, damit wieder die Registry greift. Die alte Fassung bleibt
      unter „Revisionen" erhalten.
- [ ] Startseite und je eine Unterseite aus der Tabelle oben ansehen: Hero mit vier
      Absätzen, Teaser, Seitenkopf mit Label + Überschrift, Abschnitt ohne doppelte
      Überschrift.
- **Warum:** Die neuen Texte stecken im Theme-Code. Was im Editor gespeichert ist,
  liegt dagegen in der Datenbank der Live-Instanz – darauf hatte diese Session
  keinen Zugriff.

## 9. Cache leeren

- [ ] Page-Cache/CDN nach dem Update leeren. CSS/JS werden jetzt mit `?ver=1.1.0`
      ausgeliefert (Browser laden die geänderte `main.css` damit automatisch), ein
      Page-Cache würde aber weiter das alte HTML zeigen.

---

## Zusammenfassung

| # | Punkt | Von mir erledigt? |
|---|---|---|
| 1 | Admin-Benutzer umbenennen/löschen | ⬜ Nein – erfordert Live-Zugriff |
| 2 | REST-Enumeration auch mit allen Plugins prüfen | ⬜ Nein – erfordert Live-Zugriff |
| 3 | Yoast/ACF/Matomo-Updates, Akismet/Hello-Dolly entfernen | ⬜ Nein – erfordert Live-Zugriff |
| 4 | XML-RPC serverseitig (`.htaccess`/nginx) blocken | ⬜ Nein – erfordert Server-Zugriff |
| 5 | `readme.html` löschen, `Options -Indexes`, `.md`-Dateien schützen | ⬜ Nein – erfordert Server-Zugriff |
| 6 | `DISABLE_WP_CRON` + echter Server-Cronjob | ⬜ Nein – erfordert Server-Zugriff |
| 7 | „Discourage indexing" in Produktion ausgeschaltet prüfen | ⬜ Nein – erfordert Live-Zugriff |
| 8 | Theme 1.1.0 einspielen, Seiten mit eigenem Editor-Inhalt auf alte Texte prüfen | ⬜ Nein – erfordert Live-Zugriff |
| 9 | Page-Cache/CDN leeren | ⬜ Nein – erfordert Live-Zugriff |

Theme-seitig bereits umgesetzt (siehe `inc/security.php`): XML-RPC deaktiviert,
Pingback-Header entfernt, REST-User-Enumeration blockiert, `?author=`-Enumeration
auf die Startseite umgeleitet.
