# Kontelio: Kurzanleitung

Kontelio erstellt Kontaktformulare für E-Mail, Telegram und die WhatsApp Business Platform. Das Plugin ist kostenlos und enthält keine Werbung oder Entwickler-Tracking. Hosting-, E-Mail- und Meta-Dienste können eigene Gebühren berechnen.

Version **1.0.0** befindet sich noch im WordPress.org-Review. Der gewünschte neue Slug `kontelio` muss dort erst bestätigt werden; die bestehende Einreichung läuft noch unter `contactbridge`.

## Einrichten

1. Falls bereits eine ältere Entwicklungsversion installiert ist, zuerst [UPGRADING.md](UPGRADING.md) beachten. Die alte Version nicht über WordPress **Löschen** entfernen: Dabei können gespeicherte Anfragen verloren gehen.
2. Für eine deutsche Oberfläche zuerst das separate deutsche Sprachpaket gemäß [TRANSLATIONS.md](TRANSLATIONS.md) installieren. Bei bestehenden deutschen Installationen muss dies vor der Aktivierung geschehen. Anschließend die vorbereitete `kontelio-1.0.0.zip` über **Plugins → Neues Plugin hinzufügen → Plugin hochladen** installieren und aktivieren.
3. **Einstellungen → Kontelio** öffnen, Empfänger und gewünschte Kanäle konfigurieren und speichern.
4. Einen Verbindungstest auslösen. Dieser sendet tatsächlich an den eingestellten Empfänger; die Designvorschau sendet nichts.
5. In eine Seite einen Shortcode-Block mit `[kontelio]` einfügen.
6. Felder und Darstellung anpassen, Datenschutzhinweise ergänzen und die veröffentlichte Seite auf Handy und Desktop prüfen.

Beispiel: `[kontelio theme="dark" shape="square"]`. Mit `embedded="true"` entfällt die äußere Formularkarte. Bestehende `[contact_bridge]`-Shortcodes funktionieren weiterhin.

## Deutsch anzeigen

Neue Einstellungen folgen der WordPress-Sprache. Englisch ist die Quell- und Rückfallsprache. Die vollständige deutsche Übersetzung liegt separat unter `translations/` beziehungsweise im Sprachpaket vor; sie gehört nicht zur Plugin-Installations-ZIP.

Solange kein freigegebenes WordPress.org-Sprachpaket verfügbar ist, `kontelio-de_DE.mo` nach `wp-content/languages/plugins/` kopieren und im Plugin Deutsch oder die deutsche WordPress-Sprache wählen. Details stehen in [TRANSLATIONS.md](TRANSLATIONS.md). Bereits gespeicherte eigene Texte und die bisherige Sprachwahl bleiben erhalten.

## Versand und geschützter Posteingang

Für E-Mail funktionieren vorhandener WordPress-Mailversand oder eigener SMTP-Server. Telegram benötigt Bot-Token und Chat-ID; die Einstellungen bieten einen Bestätigungscode für einen privaten Chat. WhatsApp benötigt die offizielle Business Platform einschließlich Token, Telefonnummer-ID und genehmigter Vorlage. Ein privates WhatsApp-Konto allein genügt nicht. Signal ist nicht enthalten.

**Erweiterter Datenschutz** lässt sich je Kanal aktivieren. Dann enthält dessen Benachrichtigung nur einen allgemeinen Hinweis, den Websitenamen und einen geschützten Admin-Link. Die Anfrage wird verschlüsselt lokal gespeichert. Andere Kanäle können weiterhin den vollständigen Inhalt erhalten.

Standardmäßig werden Anfragen **30 Tage** aufbewahrt. **0** bedeutet dauerhaft bis zur Löschung. Eine Änderung gilt nur für neue Anfragen. Die Bereinigung benötigt funktionierendes WP-Cron; Backups haben eigene Aufbewahrungsregeln. Gesicherte WordPress-Schlüssel sind zum späteren Entschlüsseln nötig.

Gesicherte lokale Speicherung gilt auch dann als Annahme, wenn externe Benachrichtigungen scheitern. Deshalb Posteingang und Diagnose prüfen; es gibt keine automatische Wiederholungswarteschlange. Das Plugin garantiert keine Zustellung und keine DSGVO-Konformität der gesamten Website.

Hersteller und Kontakt: [Tsambasis & Tsambasis](https://tsambasis.net/).
