# Prüfprotokoll - ContactBridge 1.0.0

Stand: 27. September 2026. Die aktuelle Bereinigung von Produktname, Pluginpfad und Textdomain wurde gezielt geprüft. Frühere Releasepakete bleiben unverändert. Frühere vollständige Versand- und Sicherheitsprüfungen werden hier nicht als erneut ausgeführte Vollläufe dargestellt.

## Identität und Review-Status

Der Produktname lautet in Deutsch und Englisch überall **ContactBridge**. Hersteller/Autor bleibt **Tsambasis & Tsambasis** (https://tsambasis.net/). Pluginpfad: `contactbridge/contactbridge.php`; Textdomain: `contactbridge`; Shortcode: `[contact_bridge]`; Version: **1.0.0**. Bestehende interne Datenkennungen bleiben für den Datenerhalt unverändert.

WordPress.org hat den Slug `contactbridge` zugewiesen. Das Plugin-Review ist weiterhin offen; die Slugzuweisung ist keine Veröffentlichungsfreigabe. GitHub-Ziel: https://github.com/tsambasis-tsambasis/contactbridge. `Contributors: solutionfirst` bleibt das technische WordPress.org-Konto und ist von der Herstellerangabe getrennt.

## Aktuell ausgeführte Prüfungen

Umgebung: separate lokale QA auf Port **8790**, WordPress **7.1.2**, PHP **8.5.7**, SQLite, Chrome/Playwright. Die bestehende Demo auf Port 8787 wurde nicht angesprochen oder verändert.

| Prüfung | Ergebnis | Beleg im lokalen Delivery-Arbeitsbereich |
| --- | --- | --- |
| Vorbereitung und sicherer Pluginpfadwechsel | 4 + 22 Prüfungen bestanden | rename-prepare-results.json, rename-verify-results.json |
| Sprachumschaltung und Übersetzungen | 30 Prüfungen bestanden | rename-i18n-results.json |
| Plugin-Metadaten und lokaler Detailsdialog | 29 Prüfungen bestanden | rename-plugin-info-results.json |
| Echte EN/DE-Oberfläche, Formular und Screenshotmotive | 17 Prüfungen bestanden | wordpress-org-assets/screenshot-checks.json |
| Header, Pluginzeile und Detailsdialog EN/DE | 12 Prüfungen bestanden | browser-branding-results.json |
| WordPress Plugin Check 2.1.0 einschließlich niedriger Fehler-/Warnungsstufen | Keine Fehler oder Warnungen | plugin-check-1.0.0.txt, plugin-check-results.json |
| Runtime-Syntax | 15 PHP-Dateien geprüft, ohne Fehler | final-local-verification.json |
| Kataloge und Paketidentität | 411 vollständige Meldungen je Sprache, 27 Runtime-Dateien, keine abgelösten öffentlichen Bezeichner | release-identity-results.json |

Die vollständige gespeicherte Einstellung wurde beim getesteten Ordnerwechsel **bytegleich erhalten**: 70 Einstellungswerte in 3.471 Datenbank-Bytes, davor und danach SHA256 `0937f137a2da9e78d6c0aaa4cf3379380f9bf69c6e94ec356768ff52e01bc05d`. Der Wechsel wurde mit einer verschlüsselten Beispielanfrage und unveränderten WordPress-Schlüsseln geprüft.

Acht HTML-Varianten stimmen nach dokumentierter Normalisierung überein: Deutsch/Englisch, Standardformular, nahtlose Einbettung sowie Fehler-/Erfolgsvorschau. Normalisiert wurden ausschließlich laufzeitabhängige Nonces, UUIDs, Zeitwerte und der alte/neue öffentliche Pluginverzeichnispfad. Alle vier Formular-/Admin-CSS- und JavaScript-Dateien sind bytegleich zum vorherigen Paket. Der Formularrenderer ist nach Textdomain- und Zeilenendennormalisierung identisch. Belege: rename-verify-results.json und rename-assets-results.json.

## Screenshots und Bereinigung

Alle **16 nummerierten Verzeichnisbilder** wurden im aktuellen Plugin in Deutsch und Englisch neu aufgenommen und einzeln visuell geprüft. Dazu kommen aktuelle Aufnahmen von Verwaltungsheadern bei 1540, 390 und 320 Pixeln sowie Pluginzeile und lokalem Detailsdialog. Der Produktname ist auch in den Plugin-Metadaten exakt ContactBridge; die separate WordPress-Autorzeile nennt weiterhin den Hersteller.

Die UI-Prüfungen kontrollieren unter anderem Sprache, Vorschau, Formularfelder, leere Passwort-/Tokenfelder, mobile Darstellung, dunkles Theme, nahtlose Einbettung und das Lesen einer ausdrücklich markierten verschlüsselten Beispielanfrage. Die beiden SMTP-Ausschnitte wurden anschließend als vollständige reale Einstellungen aufgenommen; smtp-capture-results.json bestätigt die Wiederherstellung. Es erfolgte keine Bildmanipulation und kein Versand an externe Empfänger.

Einstellungen und QA-Seitentitel wurden wiederhergestellt. Eigene temporäre Seiten und Beispielanfragen wurden entfernt; keine Screenshot-Sicherungsoption blieb zurück. HTTP/Mail-Isolation war aktiv. Die Informationslinks wurden geprüft, ohne externe Herstellerseiten oder Messaging-Dienste aufzurufen.

## Sicherer Wechsel und seine Grenze

Der neue Ordner/Hauptdateiname ist ein Wechsel der Pluginidentität, kein gewöhnliches Update desselben Pluginpfads. Geprüfter Ablauf: Datenbank und WordPress-Sicherheitsschlüssel sichern, alte Version deaktivieren, deren Ordner über FTP/Dateimanager entfernen **ohne WordPress-Deinstallation**, neues Paket installieren und ausschließlich die neue Version aktivieren. Danach Einstellungen, SMTP-Zugang und gespeicherte Anfragen prüfen.

**WordPress „Löschen“ am alten Plugin darf für diesen Wechsel nicht verwendet werden:** Der alte Uninstaller löscht das lokale Anfragenarchiv unabhängig von der Option zum Entfernen der Einstellungen. Beide Versionen dürfen nicht gleichzeitig aktiv sein. WordPress-Schlüssel müssen unverändert bleiben, damit gespeicherte Inhalte lesbar bleiben.

## Fertige Pakete

| Paket | Inhalt | Größe | SHA256 |
| --- | --- | ---: | --- |
| contactbridge-1.0.0.zip | 27 Runtime-Dateien | 237.851 Bytes | deac58e5427eb28770bf7dbe0073b28b300c3cd83dcd7bcc93c0d0f38663d343 |
| contactbridge-wordpress-org-assets-1.0.0.zip | 22 PNGs | 3.132.008 Bytes | b35417959bc71d29ff15026193591a5ec2b02301d15fa5394f9c540fab94178e |
| contactbridge-branding-1.0.0.zip | 6 PNGs | 1.844.674 Bytes | 774719472bb02ee91afb21687c683af7dd69791d151005aee3c2e463f3ec1eba |

ZIP-CRC und Paketidentität sind geprüft. Das Runtime-Readme umfasst **9.890 Bytes** und ist mit Quellstand, QA-Kopie und Paket bytegleich. Die 22 PNG-Einträge im Verzeichnis-ZIP entsprechen den finalen aktuellen Grafiken einschließlich der neu aufgenommenen SMTP-Ausschnitte. Die Grafiken sind vom installierbaren Plugin getrennt.

Die genannten JSON-/HTML-Prüfbelege liegen im lokalen Release-/Delivery-Arbeitsbereich; sie werden nicht in den installierbaren Pluginordner aufgenommen. Anleitung und Einreichungstext sind mit den aktuellen öffentlichen Repository-Dokumenten synchron.

## Grenzen

Die Mindestanforderungen WordPress 6.6 und PHP 7.4 sind keine vollständig geprüfte Versionsmatrix. Isolierte Tests ersetzen keine produktive Zustellprüfung mit den tatsächlichen E-Mail-, Telegram- und WhatsApp-Zugängen. Technische Prüfungen garantieren weder Fehlerfreiheit noch Rechtskonformität, Meta-Vorlagenfreigabe oder WordPress.org-Aufnahme. Eine neue Prüfung sämtlicher früherer Funktionssuiten wird mit diesem Namens-/Pfadwechsel nicht behauptet.
