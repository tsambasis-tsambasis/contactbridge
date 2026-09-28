# Kontelio – Prüfprotokoll

Stand: 28. September 2026, 18:51 Uhr (Europe/Berlin). Review-Version **1.0.0**, noch nicht auf WordPress.org veröffentlicht. Vollständiger Name: **Kontelio - Contact Forms**, deutsch **Kontelio - Kontaktformulare**. Menü und Produktkurzname: **Kontelio**.

## Belegte Ergebnisse

Die folgenden Prüfberichte liegen für die isolierte lokale WordPress-Installation vor. Sie enthalten insgesamt **171 bestandene Einzelprüfungen**; diese Zahl umfasst unterschiedliche Prüfarten und ist keine Aussage über eine vollständige Sicherheits- oder Kompatibilitätsprüfung.

| Bereich | Bestanden | Nachweis |
| --- | ---: | --- |
| Vorbereitung des Wechsels vom bisherigen Namen | 4 | `rename-prepare-results.json` |
| Daten- und Ausgabeerhaltung nach dem Wechsel | 23 | `rename-verify-results.json` |
| Gezielte Sicherheitsregressionen | 26 | `kontelio-dev/review-security-results.json` |
| Sprache und separate Sprachpakete | 42 | `kontelio-dev/i18n-integration-results.json` |
| Echte Browserprüfung von Namen, Anmeldung, Detaildialog und Mobilansicht | 12 | `browser-branding-results.json` |
| Screenshot-, Sprachwechsel- und Formularprüfung im Browser | 17 | `screenshot-checks.json` |
| Lokaler WordPress-Plugin-Informationsdialog | 29 | `plugin-info-results.json` |
| Statische PHP-/Asset-/Paketstrukturprüfung | 18 | `static-review-results.json` |

Die nicht mit einem Verzeichnis versehenen Nachweise befinden sich unter `output/kontelio-delivery/`. Die Integrationsberichte nennen **WordPress 7.1.2** und **PHP 8.5.7**. Die Browserprüfungen verwendeten Chrome gegen `http://127.0.0.1:8791`; sie wurden nicht auf einer produktiven Website ausgeführt. Die Mindestanforderungen WordPress 6.6 und PHP 7.4 wurden mit diesen Läufen nicht separat auf entsprechenden alten Laufzeiten geprüft.

## Umfang und Grenzen

Beim Namenswechsel blieb die gespeicherte Einstellungsoption einschließlich ihrer 70 Werte bytegleich. Acht englische/deutsche Varianten der öffentlichen Formular- und Vorschauausgabe waren ebenfalls gleich, nachdem ausschließlich anfrageabhängige Nonces, IDs, Zeitwerte und der geänderte öffentliche Plugin-Verzeichnispfad normalisiert wurden.

Die Sicherheitsregressionen prüfen unter anderem ungültige und verschachtelte Nonce-Eingaben, falsche Nonce-Aktionen, HTML- und Zeilenumbruchbehandlung, Mail-Header-Injection und den tatsächlichen WordPress-AJAX-Pfad. Die gültige Testmail wurde lokal abgefangen. Es wurden keine realen Nachrichten an SMTP, Telegram oder WhatsApp gesendet; Erreichbarkeit, Authentifizierung und Zustellung bei echten Anbietern sind damit nicht bestätigt.

Die Sprachprüfungen decken die WordPress-Sprache als Voreinstellung für neue Optionen, die englische Rückfallsprache, das separate deutsche Sprachpaket, eigene gespeicherte Texte und unveränderte bestehende Spracheinstellungen ab. Der lokale Informationsdialog wurde auf die richtige Anfrage und Berechtigung begrenzt geprüft; dabei wurden keine HTTP-Anfragen ausgelöst.

## Screenshots und Wiederherstellung

**16 unbearbeitete Browseraufnahmen** zeigen acht Ansichten jeweils auf Englisch und Deutsch: Designer, erweiterte Darstellung, mobiles Formular, dunkles Formular, Empfangskanäle, Feldeditor, E-Mail/SMTP und geschützte Anfrage. Alle wurden visuell kontrolliert. Bild 7 enthält die Diagnoseprotokoll-Option samt Hinweis auf inhaltsfreie Protokollierung; Bild 8 kennzeichnet die Anfrage ausdrücklich als Beispieldaten. Dateimaße und SHA256-Werte stehen in `screenshot-assets-verification.json`.

Die Aufnahmen zeigen nur den kurzen Namen Kontelio, nicht den anschließend gekürzten formalen Plugin-Titel. Deshalb müssen diese 16 Bilder wegen der Titelkürzung nicht neu aufgenommen werden. Separate Diagnosebilder der Pluginliste sind keine Bestandteile dieser 16 Verzeichnis-Screenshots und enthalten noch den früheren ausführlichen Titel.

Einstellungen und Website-Titel wurden nach den Browserläufen nachweislich hashgleich wiederhergestellt. Der temporäre Administrator, seine lokale Credentials-Datei und die eigenen Anfrage-/Seitenfixtures wurden entfernt. Ein von WordPress angelegter Autoentwurf landete bei der Benutzerlöschung zunächst im Papierkorb und wurde anschließend samt Revision endgültig entfernt. `browser-restoration.json` dokumentiert diesen Zusatzschritt; der zunächst abweichende Gesamttabellen-Hash wurde mangels aufbewahrtem Ausgangshash nicht nachträglich als erfolgreich umdeklariert.

## Abschließender Plugin Check

**Plugin Check 2.1.0 meldet im abschließenden Lauf 0 Fehler und 0 Warnungen.** Geprüft wurden alle Standardprüfungen einschließlich Runtime und niedriger Schweregrade, ohne ausgeschlossene Prüfungen oder ignorierte Meldungen. Nachweis: `plugin-check-results.json`, Exit-Code 0. Der vorherige Lauf meldete den geschützten Begriff WhatsApp im formalen Plugin-Namen. Deshalb lautet dieser jetzt „Kontelio - Contact Forms“; die unterstützten Dienste bleiben in den Beschreibungen genannt. Der abschließende Plugin-Info-Test bestand erneut alle 29 Prüfungen. Das ist keine WordPress.org-Freigabe.

Die Änderung des WordPress.org-Slugs von `contactbridge` zu `kontelio`, die Review-Freigabe sowie Import/Freigabe und automatische Veröffentlichung des deutschen Sprachpakets sind noch zu bestätigen. Das finale ZIP enthält 20 Dateien und wurde byteweise gegen die geprüften Quelldateien verglichen. SHA256 des Plugin-ZIPs: `3e2f015bfb49bcfdb7dd1e7d3fce0c1c34f5da7303fdfa8ea236486efe8d3184`. Die Prüfsumme des separaten deutschen Pakets steht in `dist/SHA256.txt`.
