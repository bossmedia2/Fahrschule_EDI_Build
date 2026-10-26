# Einrichtung und Live-Checkliste

Abschnitt 0 beschreibt die Einrichtung von Theme 3.3 (Block-Theme). Die
Abschnitte 1–9 sind die manuelle Checkliste für die Live-Instanz.

---

## 0. Einrichtung Theme 3.3

### 0.0 Prüfkette auf dem eigenen Rechner

`bin/gate.sh` (aus jedem Verzeichnis) prüft in Schritt 0, was die Kette
braucht, und nennt für alles Fehlende den Befehl:

| fehlt | nachrüsten | sonst |
|---|---|---|
| PHP ≥ 8.0 | `sudo apt install php-cli` | PHP-Schritte übersprungen |
| mbstring | `sudo apt install php8.3-mbstring` (Version wie `php -v`) | nur Hinweis – Theme läuft ohne |
| Python 3 | `sudo apt install python3` | Python-Schritte übersprungen |
| Node.js | nodejs.org (LTS) | JS-Syntax und Browser übersprungen |
| zip, unzip, bc | `sudo apt install zip unzip bc` | Build übersprungen |
| Playwright | `npm i -D playwright && npx playwright install chromium` | Browser übersprungen |

`bin/gate.sh --schnell` lässt den Browser bewusst aus. Exit-Code ≠ 0 nur bei
echten Fehlern; was übersprungen wurde, steht am Ende als Liste.

### 0.1 Plugins (nicht im Theme-ZIP)

| Plugin | wofür | ohne es |
|---|---|---|
| Contact Form 7 | alle Formulare | eingebaute Formulare (Kontakt, Schnellanmeldung) per `wp_mail()`; Bewerbung nur per E-Mail-Hinweis |
| Flamingo | speichert jede Einsendung im Backend | Einsendungen nur als E-Mail |
| WP Job Manager | Stellenanzeigen | Karriereseite zeigt den Leer-Text |
| edi-integrationen (`dist/edi-integrationen.zip`) | legt die vier CF7-Formulare und die Stellenkategorien an, setzt Empfänger aus den Stammdaten, Matomo | wie ohne CF7 |

Reihenfolge:
1. Contact Form 7, Flamingo und WP Job Manager installieren.
2. Danach edi-integrationen aktivieren. Beim Aktivieren legt es an:
   - die Formulare Kontakt, Anmeldung, Schnellanmeldung und Bewerbung;
   - die Stellenkategorien Fahrlehrer, Büro & Ansprechpartner, Quereinstieg & Ausbildung.

Unter *Einstellungen → edi-integrationen* sind die Formulare verlinkt.

**WP-Job-Manager-Assistent:** Seiten für das Einreichen von Stellen und das
Arbeitgeber-Dashboard **nicht** anlegen lassen. Die Fahrschule schreibt
Stellen im Backend aus (*Job Listings → Neu*). Angezeigt werden sie unter
`/ueber-uns/karriere/`, jede Stelle hat eine eigene URL.

**Formulare – was eingebaut ist:**
- **Empfänger:** kommt aus `inc/seo.php`. Wählt jemand einen Standort,
  geht die Mail an diesen Standort, sonst an `info@`. Im Formular steht
  keine Adresse.
- **Spamschutz:** Honeypot, ohne reCAPTCHA oder andere Drittanbieter.
- **Einwilligungen:** Pflicht-Häkchen für Datenschutz und für Minderjährige
  (Art. 8 DSGVO) auf allen Formularen.
- **Bewerbung:** PDF bis 10 MB als Mailanhang. Die Stelle (Titel und ID)
  wird mitgeschickt.

### 0.2 Theme

1. *Design → Themes → Theme hochladen* → `dist/fahrschule-edi.zip` → aktivieren.
2. Beim Aktivieren entstehen alle Seiten der Registry, Elternseiten zuerst,
   jede mit Vorlage und Inhalt: dem eigenen Block-Stapel der Registry oder
   dem Startmuster ihrer Vorlage. Die Startseite wird gesetzt. Ein
   Einmal-Hinweis im Backend listet die angelegten Seiten.
3. Bestehende Seiten werden nie überschrieben; eine zweite Aktivierung ändert
   nichts. Seiten bestehender Installationen mit leerem Inhalt füllt der
   erste Admin-Aufruf (Abschnitt 8).
4. Header, Footer und Vorlagen bearbeiten: *Design → Editor*. Neue Seiten:
   im Muster-Dialog ein Startmuster wählen (Kategorie „Fahrschule edi –
   Seiten“) und rechts die passende Vorlage.
5. **Fläche je Abschnitt:** Jeder Block hat rechts das Feld „Fläche“
   (Vollpink, Hellpink, Leiste, Foto). Faustregel: nach zwei Vollpink ein
   Hellpink; Leiste nur für schmale Bänder; Foto braucht einen Medien-Slug.

### 0.3 Bilder und Hero-Video

`bin/edi-bilder.py` im Bildordner ausführen und nur `umbenannt/` importieren.
Vorher die Slug-Prüfung aus `umbenannt/bericht.txt` laufen lassen.
Einzelheiten: `docs/assets_image_list.md`. Der Test des Skripts läuft
manuell, nicht im Gate: `python3 bin/checks/test_edi_bilder.py`.

**Hero der Startseite** – Video und Standbild gehören in die Mediathek:

- [ ] Prüfen, ob das Video schon da ist:
      `wp post list --post_type=attachment --name=edi-video-hero --format=count`
      (0 = fehlt).
- [ ] `edi-video-hero.mp4` hochladen (optional zusätzlich eine WebM mit dem
      Slug `edi-video-hero-webm`).
- [ ] Ein Standbild `edi-video-hero-standbild.jpg` (oder `.webp`) hochladen –
      es ist Poster, Hintergrund, Editor-Vorschau und das Bild für Besucher
      mit „Bewegung reduzieren“.
- Solange etwas fehlt, nennt ein Hinweis im Backend die Datei. Ohne beides
  zeigt der Hero Vollpink.

### 0.4 Vor dem Livegang beim Kunden zu klären

- [ ] **Klasse B96** steht nicht in der Kunden-PDF (F3) – wird sie angeboten?
- [ ] **Kurzdaten der Klassenseiten:** Werte aus FeV/FahrschAusbO (Mindestalter,
      Theorie, Pflichtfahrten) und Richtpreise aus dem ursprünglichen Build
      bestätigen lassen (Quellen als Kommentar in `inc/pages.php`). Leere Felder
      sind Absicht – nichts ohne Beleg.
- [ ] **Datenschutzerklärung:**
  - [ ] Absatz zu OpenStreetMap ergänzen (Karte auf den Standortseiten, lädt erst
        nach Klick oder Einwilligung);
  - [ ] Absatz zu Contact Form 7/Flamingo (gespeicherte Einsendungen) ergänzen;
  - [ ] Absatz zu Bewerbungsdaten ergänzen.
- [ ] **Einwilligungen** für Fotos mit Minderjährigen (Bild-Briefing).
- [ ] **Vollständige Namen** der Fahrlehrer, Standort von Abdullah und Mesut.
- [ ] **TikTok-Video** (Erfahrungen): im Block „TikTok-Video (Player)“ die
      URL eines Videos eintragen (`https://www.tiktok.com/@edi_fahrschule/video/…`;
      `/photo/…` und Links mit `?is_from_webapp=…` gehen auch). Der Editor
      zeigt den Platzhalter mit der erkannten Post-ID – ist die URL nicht
      lesbar, sagt er das. Das Video ist von Hand gewählt und aktualisiert
      sich nicht; für ein neues Video die URL austauschen.
- [ ] **Datenschutz – TikTok juristisch prüfen lassen:** Cookie-Banner und
      Datenschutzerklärung (Abschnitt „Eingebettete Videos (TikTok)“) nennen
      Anbieter (TikTok Technology Limited, Dublin), Daten, Drittland,
      Rechtsgrundlage und Widerruf. Ohne Einwilligung steht kein Iframe im
      HTML; „Video laden“ gilt für die Sitzung, „Alle akzeptieren“ bis zum
      Widerruf. Die Formulierung ist ein Entwurf nach TikToks eigenen
      Angaben, keine Rechtsberatung.
- **Betrieb:** TikTok begrenzt die Nutzung des Players je Domain. Mit einem
  Player ist das unkritisch; fällt er doch aus, zeigt das Iframe TikToks
  eigene Meldung – daran lässt sich von außen nichts ändern.

---

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
| F2 | Über uns · Ihre Fahrschule im Ruhrgebiet | Anriss | /ueber-uns/ | `about-intro` |
| F3 | Führerscheinklassen · Unsere Führerscheinklassen | vier Kacheln (Label, Überschrift, Klassen) | /fuehrerscheinklassen/ | `license-overview` |
| F4 | Unsere Leistungen · Alles rund um den Führerschein aus einer Hand | Teaser | /leistungen/ | `services` |
| F5 | Berufskraftfahrer · Ausbildung & Weiterbildung für Berufskraftfahrer | Teaser | /berufskraftfahrer/ | `professional-drivers` |
| F6 | ASF & FES · Aufbauseminare und Punkteabbau | Teaser | /asf-fes/ | `seminars` |
| F7 | Moderne Ausbildung · Lernen mit moderner Technik | Teaser | /moderne-ausbildung/ | `modern-training` |
| F8 | Standorte · Unsere Standorte | kompakt (ohne Fotos/Absatz) | /standorte/; kompakt auf /kontakt/, /anmeldung/ | `locations` |
| F9 | Unser Team · Persönlich. Geduldig. Erfahren. | Teaser | /ueber-uns/team/ | `about-team` |
| F10 | Anmeldung · Jetzt bei der Fahrschule edi anmelden | vollständig (Abschluss) | /anmeldung/: nur Seitenkopf + Formular | `anmeldung` |

Teaser/Anriss = Label, Überschrift und erster Satz bzw. Absatz des Abschnitts
(wortgleich) plus Link auf die Unterseite. Kein Abschnitt steht voll auf
Startseite und Unterseite zugleich (geprüft von `bin/checks/startseite.py`). Auf den Unterseiten zeigt der Seitenkopf Label und Überschrift
(Schlüssel `pdf` in `inc/pages.php`), der Block darunter läuft mit `head = 0`
ohne doppelte Überschrift. Die Platzierungsangabe „Startseite / Hero" und die
Foto-Hinweise „(Fotos)" werden nicht als Text ausgegeben.

**Weiterhin vorhanden, aber kein PDF-Abschnitt** (mit dem Kunden klären, ob
sie bleiben sollen): Vertrauensleiste (Startseite), Team Spirit (Team-Seite),
Bewertungen (Google-Link, keine Zitate), CTA „Bereit für Ihren Führerschein?"
mit belegten Zahlen (Jahre seit Gründung 1993, Zahl der Standorte) am Ende
vieler Unterseiten, Zeitleiste und Philosophie (Über uns),
Flotte & Einrichtungen (Moderne Ausbildung), Preise & Rechner,
Theorieunterricht, Fahrlehrerausbildung, FAQ, Erfahrungen, Standort-
Detailseiten (Hero-Texte, Rechner, Entfernung).

**Text ändern:** Wortlaut in `inc/pdf-texts.php` anpassen – wirkt auf
Startseite, Unterseite und Editor-Vorgabe zugleich.

## 8. Update auf 3.0 (Block-Theme): Seiteninhalte prüfen

Bis 2.x rendert das Theme leere Seiten zur Laufzeit aus der Registry. Ab 3.0
rendert die Block-Vorlage den Seiteninhalt (`post_content`).

- [ ] Theme 3.3.0 einspielen und einmal das Backend aufrufen. Die Migration
      (`fahrschule_migrate_content()`) füllt jede Registry-Seite mit **leerem**
      Inhalt und setzt ihre Vorlage. Seiten **mit** Inhalt bleiben unangetastet.
- [ ] Unter **Seiten** jede Seite mit eigenem Inhalt öffnen. Seit 3.2 steht
      alles außer den Brotkrumen im Inhalt (siehe 8.2).
- [ ] Jeder Seite die passende Vorlage geben (Seite → Vorlage); welche, steht in
      `docs/BLOCKS-PATTERNS.md`, Abschnitt 4.
- [ ] *Design → Editor* öffnen: Header, Footer und je eine Vorlage ansehen.
- **Warum:** Was im Editor gespeichert ist, liegt in der Datenbank der
  Live-Instanz – darauf hatte keine Session Zugriff.

### 8.1 Update auf 3.1

- [ ] **404-Vorlage zurücksetzen**, falls die Live-Seite bei einer unbekannten
      URL zuerst „Offene Stellen“ zeigt: Diese Ausgabe kommt nicht aus dem
      Theme (`templates/404.html` enthält nur die Fehlerseite: Meldung,
      Suche, Wege zurück), sondern aus einer im Site-Editor gespeicherten
      oder älteren Vorlage. *Design → Editor → Vorlagen → Seite: 404* → ⋮ →
      „Anpassungen zurücksetzen“. Ebenso jede andere Vorlage mit dem Hinweis
      „angepasst“ prüfen.
- [ ] Bestehende Seiten: Blöcke ohne gewählte Fläche zeigen jetzt Vollpink.
      Seiten mit eigenem Inhalt öffnen und je Abschnitt die Fläche setzen
      (Faustregel siehe 0.2). Leere Bereichsseiten füllt die Migration
      (Inhaltsstand 4) mit dem Startmuster.
- [ ] Startseite: Hero-Video und Standbild hochladen (0.3).

### 8.2 Update auf 3.2: Unterseiten ganz bearbeitbar

Seit 3.2 tragen die Vorlagen nur noch den Rahmen (Header, Brotkrumen,
Inhalt, Footer). Seitenkopf, Handlungsaufruf, Klassenkacheln, Standorte und
Karte stehen im Seiteninhalt – auf neuen Installationen von selbst.
Bestehende Seiten zeigen nach dem Update **nur ihren bisherigen Inhalt**,
ohne Seitenkopf und Handlungsaufruf.

- [ ] *Seiten* → alle Seiten auswählen → Sammelaktion „Aus Startmuster neu
      aufbauen“ → die Bestätigungsseite nennt jede Seite und warnt → „Seiten
      jetzt ersetzen“. Die vorige Fassung bleibt als Revision.
- [ ] Seiten mit redaktioneller Arbeit vorher einzeln prüfen: der Neuaufbau
      ersetzt den ganzen Inhalt. Alternativ dort Seitenkopf und
      Handlungsaufruf von Hand einfügen (Blöcke „Seitenkopf“, „CTA“).
- [ ] *Design → Editor → Vorlagen*: Vorlagen mit Hinweis „angepasst“
      zurücksetzen – sonst gilt die alte Vorlage mit ihren Abschnitten weiter
      und Seitenkopf/CTA erscheinen doppelt.

## 9. Cache leeren

- [ ] Page-Cache/CDN nach dem Update leeren. CSS/JS werden jetzt mit `?ver=3.3.0`
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
| 8 | Theme 3.3.0 einspielen, Seiten aus Startmuster neu aufbauen (Sammelaktion), angepasste Vorlagen (404 u. a.) zurücksetzen, Flächen prüfen | ⬜ Nein – erfordert Live-Zugriff |
| 9 | Page-Cache/CDN leeren | ⬜ Nein – erfordert Live-Zugriff |

Theme-seitig bereits umgesetzt (siehe `inc/security.php`): XML-RPC deaktiviert,
Pingback-Header entfernt, REST-User-Enumeration blockiert, `?author=`-Enumeration
auf die Startseite umgeleitet.
