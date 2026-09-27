# ContactBridge – Einrichtung und Einreichung

Erstveröffentlichung 1.0.0 · Stand: 27. September 2026

Das Plugin erstellt ein anpassbares Kontaktformular für WordPress. Nachrichten gehen an eine hinterlegte E-Mail-Adresse, an einen Telegram-Chat und/oder per offizieller WhatsApp Cloud API an eine festgelegte Empfängernummer. Besucher benötigen keinen Messenger-Account. Mit erweitertem Datenschutz speichert das Plugin die Originalanfrage verschlüsselt in WordPress und sendet auf dem jeweiligen Kanal nur Website-Namen und einen geschützten Administratorlink.

## Schnellstart

1. In WordPress **Plugins → Neues Plugin hinzufügen → Plugin hochladen** öffnen, die Plugin-ZIP auswählen und aktivieren.
2. **Einstellungen → ContactBridge** öffnen; der Name lautet in beiden Sprachen **ContactBridge**.
3. Mindestens einen Versandkanal aktivieren, Ziel und Zugangsdaten hinterlegen, speichern und einen Test senden.
4. Auf der Kontaktseite einen **Shortcode-Block** einfügen:

   ```text
   [contact_bridge]
   ```

5. Die veröffentlichte Seite auch ausgeloggt am Mobilgerät prüfen und eine echte Testanfrage absenden.

Der Shortcode lautet ausschließlich `[contact_bridge]`. Das Installationspaket heißt `contactbridge-1.0.0.zip`; sein Pluginordner und seine Textdomain heißen `contactbridge`. Dies ist die erste Veröffentlichung des Plugins.

Voraussetzungen: WordPress ab 6.6, PHP ab 7.4 und für Messenger ausgehende HTTPS-Verbindungen vom Webserver. Mit JavaScript wird ohne Seitenwechsel gesendet; ohne JavaScript funktioniert ein klassischer Formularversand mit anschließendem Neuladen. Für einen sicheren Produktivbetrieb sollten WordPress und PHP auf einer aktuell unterstützten Version laufen.

## Vorhandene Entwicklungsversion auf den neuen Ordner umstellen

Der aktuelle Produktname lautet überall **ContactBridge**. Der neue Pluginpfad ist `contactbridge/contactbridge.php`; Textdomain und gewünschter WordPress.org-Slug lauten `contactbridge`. WordPress.org hat den Slug **contactbridge** inzwischen zugewiesen. Das Plugin-Review ist weiterhin offen; eine Veröffentlichung ist damit noch nicht freigegeben. Das GitHub-Repository ist unter https://github.com/tsambasis-tsambasis/contactbridge vorgesehen; eine Umbenennung dort ist keine WordPress.org-Freigabe.

Bei einer bereits installierten Entwicklungsversion vor dem Ordnerwechsel:

1. Datenbank und unveränderte WordPress-Sicherheitsschlüssel geschützt sichern. Vorhandene Anfragen, Einstellungen und SMTP-Zugang prüfen.
2. Die bisherige Entwicklungsversion in der Pluginliste **deaktivieren**. **Nicht über WordPress löschen:** Die Deinstallationsroutine entfernt gespeicherte Anfragen unabhängig von der Einstellung zum Löschen der Konfiguration.
3. Nach dem Backup den deaktivierten alten Pluginordner über FTP oder die Dateiverwaltung des Hostings entfernen, **ohne die WordPress-Deinstallation aufzurufen**. Dann die neue `contactbridge-1.0.0.zip` installieren und ausschließlich das Plugin aus `contactbridge/` aktivieren. Die beiden Versionen nicht gleichzeitig aktivieren.
4. Einstellungen, Versandziele, Formular, vorhandene verschlüsselte Anfragen und gegebenenfalls SMTP-Passwort prüfen. Der Shortcode `[contact_bridge]` bleibt bestehen. WordPress-Sicherheitsschlüssel nicht ändern.
5. Das Backup bis zur abgeschlossenen Prüfung aufbewahren. Bei Unklarheiten den Wechsel auf einer geschützten Testkopie nachvollziehen; keine Löschfunktion in der WordPress-Pluginliste verwenden.

Alte gespeicherte Administratorlinks oder Lesezeichen können auf den früheren Einstellungsseiten-Slug zeigen; die aktuelle Oberfläche über **Einstellungen → ContactBridge** öffnen. Der geschützte Anfragenzugriff bleibt an die Anmeldung und Administratorberechtigung gebunden. Die neue Ordnerstruktur allein ist kein automatisches WordPress-Update des alten Pluginpfads.

## Deutsch oder Englisch auswählen

Der Kopfbereich der Einstellungen zeigt die mitgelieferte lokale Bildmarke. Dafür wird kein externes Bild geladen. Bei aktiviertem Plugin öffnet **Details anzeigen** in der WordPress-Pluginliste für berechtigte Administratoren ein lokales Detailfenster, auch vor einer Freigabe im Pluginverzeichnis. Dafür ist die WordPress-Berechtigung `install_plugins` erforderlich. **Mehr Informationen** führt auf [tsambasis.net](https://tsambasis.net/), trägt den Linktitel **Tsambasis & Tsambasis** und wird erst beim Anklicken geöffnet.

Das Plugin startet standardmäßig auf **Deutsch**, auch wenn WordPress selbst auf Englisch eingestellt ist. In den Plugin-Einstellungen stehen drei Sprachoptionen zur Wahl:

| Auswahl | Wirkung |
| --- | --- |
| Deutsch | Deutsche Pluginoberfläche und Standardmeldungen; Voreinstellung |
| English | Englische Pluginoberfläche und Standardmeldungen |
| WordPress-Sprache | Das Plugin folgt der von WordPress vorgegebenen Sprache |

Die Auswahl betrifft das Plugin im Backend, feste Formularmeldungen und Beschriftungen seiner Versandnachrichten. WordPress und andere Plugins werden dadurch nicht umgestellt. Eine Sprachänderung wird erst mit **Einstellungen speichern** wirksam. Bis dahin verwendet die Vorschau die bisher gespeicherte Sprache.

Unveränderte Standardtexte des Formulars folgen der ausgewählten Sprache. **Eigene gespeicherte Texte bleiben erhalten** und werden nicht automatisch übersetzt. Das betrifft beispielsweise eine selbst formulierte Überschrift oder Datenschutzerklärung. Auch ein direkt im Shortcode gesetzter `heading`-Text bleibt genau dein Inhalt. Bei einer englischen Kontaktseite die eigenen Texte deshalb ebenfalls auf Englisch verfassen.

Englische Ausgangstexte, deutsche und englische Sprachdateien sowie eine Übersetzungsvorlage sind im Plugin enthalten. Zusätzliche Sprachen benötigen passende Übersetzungen; die Auswahl „WordPress-Sprache“ ist kein automatischer Übersetzungsdienst.

## Eigene Formularfelder

Der Formularbaukasten erlaubt **ein bis zwanzig Felder**. Als Ausgangspunkt sind Name, E-Mail-Adresse, Betreff und Nachricht vorhanden. Du kannst diese Felder entfernen, neue ergänzen und ihre Reihenfolge ändern. Auch ein Formular ohne Namens- oder E-Mail-Feld ist möglich; mindestens ein Feld muss erhalten bleiben. Die gesonderte Datenschutzbestätigung ist unabhängig davon einstellbar.

| Feldtyp | Verwendung |
| --- | --- |
| Text | Kurze freie Angabe, beispielsweise Firma oder Kundennummer |
| E-Mail | E-Mail-Adresse mit serverseitiger Formatprüfung |
| Telefon | Telefonnummer mit mindestens drei Ziffern; übliche Trennzeichen und Durchwahl mit `x` sind erlaubt |
| Mehrzeiliger Text | Nachricht oder ausführlichere Angaben |
| Auswahl | Eine von höchstens zwanzig hinterlegten Optionen |
| Checkbox | Einzelne Ja-/Nein-Bestätigung |

Je nach Feldtyp lassen sich Beschriftung, Platzhalter, Pflichtstatus und halbe oder volle Breite festlegen. Bei einer Auswahl werden zusätzlich die erlaubten Optionen hinterlegt. Die Reihenfolge im Baukasten bestimmt die Reihenfolge im Formular und in der Benachrichtigung. Auf schmalen Geräten werden Felder untereinander dargestellt. Eine Pflicht-Checkbox muss angekreuzt sein; eine Pflicht-Auswahl muss einer gespeicherten Option entsprechen.

Die Live-Vorschau zeigt auch ungespeicherte Feldänderungen. Erst nach **Einstellungen speichern** gelten sie für Besucher. Ein entferntes Feld wird weder weiterhin als Pflichtfeld verlangt noch aus mitgesendeten alten Browserdaten in die Benachrichtigung übernommen. Durch das Entfernen eines E-Mail-Felds entfällt gegebenenfalls die Möglichkeit, direkt per E-Mail zu antworten.

Zum Bearbeiten des Baukastens ist JavaScript erforderlich; ein einmal gespeichertes Formular lässt sich von Besuchern weiterhin auch ohne JavaScript abschicken. Nach Änderungen an Feldtypen, Pflichtstatus oder Auswahloptionen müssen bereits geöffnete Formulare gegebenenfalls neu geladen werden. Änderungen ausschließlich an Beschriftungen erzwingen das nicht.

Einzelne Eingaben haben feste Grenzen: kurze eigene Textfelder 250, E-Mail 254, Telefon 50 und mehrzeilige Felder 2.000 Zeichen; die ursprünglichen Textfelder Name und Betreff bleiben auf 100 beziehungsweise 150 begrenzt. Bei aktivem WhatsApp ohne erweiterten Datenschutz gelten zusätzlich die strengeren Gesamtgrenzen im WhatsApp-Abschnitt. Unabhängig vom Kanal sind alle beschrifteten Antworten zusammen auf 12.000 Zeichen begrenzt. Für Telegram ohne erweiterten Datenschutz muss die komplette Benachrichtigung in 4.000 UTF-16-Einheiten passen; viele Emojis zählen dabei doppelt. Überschreitungen werden vor dem Versand an irgendeinen Kanal abgewiesen und nicht unbemerkt gekürzt. Mindestens ein Feld muss ausgefüllt sein, auch wenn alle Felder optional sind.

Die Standardbeschriftungen folgen der ausgewählten Sprache; eigene Feldtexte bleiben eigener Inhalt und werden nicht maschinell übersetzt.

## Gestaltung

Die Einstellungen bieten einen **einfachen Modus** mit fertigen Designvorlagen und einen **erweiterten Modus** für einzelne Gestaltungswerte. Eine Vorlage ändert ausschließlich das Aussehen. Versandkanäle, Zugangsdaten und Formulartexte werden dadurch nicht überschrieben. Beim Wechsel zwischen den Modi bleiben individuelle Werte erhalten; das bewusste Anwenden einer Vorlage ersetzt deren Gestaltungswerte.

| Vorlage | Ausgangsdesign |
| --- | --- |
| Natur | Hell, rund und mit grünem Akzent |
| Nacht | Dunkel, rund und mit hellem Akzent |
| Studio | Eckig und mit blauem Akzent |
| Pur | Kompakt, einspaltig und ohne Schatten |

**Nahtlos einbetten** steht unter **Design** direkt unter den Vorlagen zur Verfügung, im einfachen wie im erweiterten Modus. Die Option macht den äußeren Formularcontainer transparent und entfernt dessen Rand, Schatten sowie Innen- und Außenabstände. Das Formular beginnt am linken Inhaltsrand; die eingestellte maximale Breite, Feldränder, Fokusrahmen und Abstände zwischen den Feldern bleiben erhalten. Auch Erfolgs- und Fehlermeldungen behalten ihre eigene Umrahmung. Beim Ausschalten erscheint wieder die bisherige Formularkarte. Das Anwenden einer Designvorlage behält die gewählte Einbettungsoption bei.

Die Live-Vorschau zeigt die Einbettung sofort vor dem Speichern. Ihr Hintergrund ist jedoch nur ein Beispiel: Hell-/Dunkel-Theme und Textfarben müssen zum tatsächlichen Seitenhintergrund passen. Prüfe die eingebettete Darstellung deshalb nach dem Speichern auf der veröffentlichten Seite.

Im erweiterten Modus lassen sich unter anderem Formularbreite, Abstände, Schriftgröße, Spaltenlayout, Farben und Buttonausrichtung anpassen. Feldbeschriftungen und Platzhalter werden im Formularbaukasten bearbeitet. Ein- und Ausschalter steuern beispielsweise Schatten, Zeichenzähler und Buttonpfeil. Breite und Schriftgröße sind auf sinnvolle Werte begrenzt; auf kleinen Geräten bleibt das Formular innerhalb des verfügbaren Platzes.

Die **Live-Vorschau** zeigt Änderungen an Aussehen und Texten bereits vor dem Speichern. Sie versendet keine Nachrichten und ruft keine Messenger-Dienste auf. Erst **Einstellungen speichern** übernimmt die Änderungen auf der Website. Die separaten Verbindungstests senden dagegen echte Testnachrichten an die hinterlegten Empfänger.

Vorschau und öffentliches Formular verwenden denselben Renderer. Die Vorschau ist von den WordPress-Backendstilen abgeschirmt; auf der tatsächlichen Website können das aktive Theme, dessen Schriftarten und eigenes CSS zusätzlich wirken. Nach dem Speichern daher auch die echte Kontaktseite aufrufen.

### Datenschutztext verlinken

Den Datenschutzhinweis als normalen Text eingeben und `{privacy_link}` an der gewünschten Linkposition einsetzen, zum Beispiel:

```text
Ich habe die {privacy_link} gelesen und stimme der Verarbeitung meiner Angaben zur Bearbeitung meiner Anfrage zu.
```

Im Feld für den Linktext beispielsweise **Datenschutzhinweise** eintragen, im URL-Feld die vollständige `https://`-Adresse deiner Datenschutzerklärung. Die Option für einen neuen Tab legt das Öffnungsverhalten fest. Eigenes HTML ist dafür nicht erforderlich und wird nicht als HTML ausgeführt. Ein Datenschutztext ohne Marker bleibt erhalten; der Link wird am Ende ergänzt. Ohne URL wird an der Markerposition lediglich der Linktext angezeigt.

Im Hinweis unter dem Nachrichtenfeld steht `{max}` für die jeweils gültige Zeichenbegrenzung. So bleibt ein angepasster Hinweis bei aktiviertem WhatsApp automatisch korrekt.

### Shortcode-Attribute

Ohne Attribute gelten die im Backend gespeicherten Standards. Das folgende Beispiel ist dunkel und eckig:

```text
[contact_bridge theme="dark" shape="square" heading="Schreib uns"]
```

| Attribut | Werte | Wirkung |
| --- | --- | --- |
| `theme` | `light`, `dark`, `auto` | Hell, dunkel oder passend zur Systemeinstellung |
| `shape` | `rounded`, `square` | Runde oder eckige Elemente |
| `heading` | Freier Text | Überschrift dieser Formularinstanz |
| `embedded` | `true`, `false` (auch `1`, `0`) | Nahtlose Einbettung für diese Instanz ein- oder ausschalten |

Für eine einzelne nahtlose Einbettung beispielsweise `[contact_bridge embedded="true"]` verwenden. `embedded="false"` zeigt die Formularkarte auch bei global eingeschalteter Einbettung. Ohne dieses Attribut oder bei einem ungültigen Wert gilt die gespeicherte globale Einstellung.

Das Formular passt sich dem verfügbaren Platz an. Mehrere Instanzen einer Seite dürfen unterschiedliche Designs verwenden. Die Versandziele werden ausschließlich im Backend festgelegt und können nicht über den Shortcode überschrieben werden.

## Erweiterter Datenschutz und lokale Anfragen

Bei jedem Versandkanal lässt sich **Erweiterter Datenschutz** einzeln einschalten. Die Voreinstellung ist ausgeschaltet. Ein eingeschalteter Kanal überträgt nur Website-Namen und einen geschützten Link zur Anfrage im WordPress-Backend. Besuchername, Besucher-E-Mail-Adresse, Betreff, Feldbeschriftungen und Formularantworten werden darüber nicht versandt; die E-Mail-Benachrichtigung enthält auch keine Besucheradresse als `Reply-To`. Technisch notwendige Dienstzugänge, Betreiber-Empfänger und die Website-Adresse werden weiterhin beim jeweiligen Versanddienst verarbeitet.

Die Originalanfrage wird dafür verschlüsselt auf der eigenen Website gespeichert. Unter **Einstellungen → Anfragen** kannst du sie nach der Anmeldung lesen und löschen. Der Link allein erteilt keinen Zugriff: Das angemeldete WordPress-Konto benötigt die Berechtigung `manage_options`. Links können bei Mail- und Nachrichtendiensten verbleiben; der Inhalt wird erst im geschützten Backend geöffnet.

**Gemischte Einstellungen beachten:** Wenn Telegram den erweiterten Datenschutz nutzt, E-Mail aber nicht, erhält E-Mail weiterhin den vollständigen Formularinhalt. Jeden aktivierten Kanal einzeln prüfen und die Datenschutzerklärung entsprechend anpassen. Wenn keiner der aktiven Kanäle erweiterten Datenschutz verwendet, wird kein lokales Anfragenarchiv angelegt.

Unter **Datenschutz & Aufbewahrung** sind standardmäßig **30 Tage** eingestellt. Du kannst die Dauer in ganzen Tagen ändern; **0 bedeutet dauerhaft**, bis du die Anfrage selbst löschst. Werte von 1 bis 36500 werden unterstützt. Änderungen gelten **nur für neue Anfragen**. Bereits vorhandene Anfragen behalten ihr beim Eingang gespeichertes Ablaufdatum und können separat unter Anfragen gelöscht werden. Abgelaufene Einträge werden bereinigt; dafür muss WP-Cron durch Seitenaufrufe oder einen eingerichteten Servercron ausgeführt werden. Backups können ältere Daten weiterhin enthalten und benötigen eine eigene Aufbewahrungsregel.

Eine sicher gespeicherte Anfrage gilt als eingegangen, auch wenn die Benachrichtigung an sämtliche externen Kanäle fehlschlägt. Die Bestätigung verspricht daher keine Zustellung. Prüfe das Anfragen-Postfach und die Kanaldiagnosen regelmäßig; eine automatische erneute Benachrichtigung ist nicht zugesagt.

Die Verschlüsselung verwendet WordPress-Sicherheitsschlüssel. Bei Verlust oder Änderung dieser Schlüssel können bereits gespeicherte Anfragen unlesbar werden. Sichere Schlüssel und Datenbank gemeinsam in geschützten Backups; wer beide besitzt, kann die Inhalte entschlüsseln. Das Plugin ist auch in diesem Modus kein Ersatz für eine passende Datenschutzerklärung und die erforderliche Absicherung des Hostings.

Gespeicherte Anfragen sind in die WordPress-Werkzeuge zum Exportieren und Löschen personenbezogener Daten integriert. Verwende für Auskunfts- oder Löschanfragen den vorgesehenen WordPress-Bestätigungsablauf. Anfragen ohne zuordenbare E-Mail-Adresse bei Bedarf direkt im Anfragen-Postfach prüfen und löschen.

## E-Mail

Unter **Versandkanäle → E-Mail** Empfänger und Absender getrennt einstellen. Die Empfängeradresse muss existieren. Als Absender eine beim Mailanbieter zugelassene Adresse verwenden; eine Besucheradresse bleibt ausschließlich `Reply-To`. Ohne eigenen Absender verwendet WordPress seine bisherige Konfiguration, häufig `wordpress@<Website-Domain>`. Gerade bei Subdomains kann dieser Standard vom Hosting abgelehnt werden.

**WordPress-Mailversand** bleibt die Voreinstellung und verwendet die vorhandene WordPress-Mailkonfiguration samt Mailplugins. Damit kannst du zunächst nur die Absenderadresse korrigieren, speichern und **E-Mail testen**. Ein vorhandenes Mailplugin kann eigene Absenderregeln anwenden.

**Eigener SMTP-Server** ist anbieterunabhängig. Trage Host, Port, Verschlüsselung, Absender und gegebenenfalls Benutzername/Passwort ein. Der Host ist ein einzelner Servername ohne `https://` oder Pfad. Übliche Kombinationen sind Port 587 mit STARTTLS oder 465 mit implizitem TLS (oft SSL/TLS genannt). Maßgeblich sind die Angaben deines Anbieters. Zertifikatsprüfung bleibt aktiv; unverschlüsselter SMTP-Versand ist nicht vorgesehen. Ausgehende SMTP-Verbindungen und PHP OpenSSL müssen auf dem Hosting verfügbar sein. Diese Einstellungen gelten nur für E-Mails dieses Plugins.

Unterstützt werden SMTP-Passwörter und App-Passwörter. Einen OAuth-Anmeldevorgang enthält das Plugin nicht. Für Anbieter, die ausschließlich OAuth erlauben, eine passende WordPress-Mailintegration einrichten und den WordPress-Versandweg wählen. Ein anderes Mailplugin kann den Versand schon vor der SMTP-Verbindung übernehmen; das Protokoll kennzeichnet diese Übergabe gesondert.

Das SMTP-Passwort wird verschlüsselt gespeichert und im Backend nicht wieder ausgegeben. Ein leeres Passwortfeld erhält den bisherigen Wert; die Löschoption entfernt ihn beim Speichern. Alternativ `TSCB_SMTP_PASSWORD` in `wp-config.php` hinterlegen. Nach einem Wechsel der WordPress-Sicherheitsschlüssel/-Salts muss ein gespeichertes Passwort neu eingegeben werden. Die Verschlüsselung schützt eine isolierte Datenbankkopie; wer Datenbank und WordPress-Schlüssel kontrolliert, kann weiterhin entschlüsseln.

### Versandprotokoll und Fehlerhinweise

Das optionale E-Mail-Protokoll hält höchstens **50 Einträge für sieben Tage** fest: Uhrzeit, Versandweg, Test/Formular, Erfolg und sichere Fehlerkategorie. Es speichert keine Empfängeradressen, Nachrichtentexte, Betreffzeilen, Passwörter oder unbearbeiteten Serverantworten. Es lässt sich deaktivieren und über den eigenen Button löschen. Ereignisse während deaktivierter Protokollierung können nachträglich nicht rekonstruiert werden. Abgelaufene Einträge werden ausgeblendet und beim Lesen/Schreiben oder durch die stündliche Plugin-Bereinigung entfernt; die Ausführung von WP-Cron benötigt Seitenaufrufe oder einen eingerichteten Servercron.

- **Anmeldung:** Benutzername und Postfach-/App-Passwort prüfen.
- **Verbindung:** Servername, Port, Hosting-Firewall und ausgehenden SMTP-Zugriff prüfen.
- **TLS:** Port/Verschlüsselung, Zertifikatskette und PHP OpenSSL prüfen.
- **Absender/Empfänger:** Zulässige Absenderdomain beziehungsweise Adresse prüfen.
- **Durch WordPress abgefangen:** Ein Mailplugin oder Filter hat den Aufruf vor dem eigentlichen Versand beantwortet.
- **Keine nähere Ursache:** Hoster-/Mailserverprotokoll oder die vorhandene WordPress-Mailintegration prüfen; das Plugin erfindet keine Fehlerursache.

Ohne erweiterten Datenschutz werden alle konfigurierten Formularfelder mit Beschriftung und Wert in Reihenfolge übermittelt. Die erste ausgefüllte gültige E-Mail-Feldeingabe wird als Antwortadresse verwendet; ohne solche Eingabe gibt es kein Besucher-`Reply-To`. Mit erweitertem Datenschutz gehen nur Website-Name und geschützter Administratorlink an den Empfänger, ohne Besucher-`Reply-To`. Eine Versandannahme bestätigt noch keinen Posteingang: zusätzlich Posteingang und Spamordner kontrollieren.

### Beispiel: IONOS Mail Basic/Business

IONOS ist ein Beispiel; andere Nutzer tragen die Daten ihres jeweiligen Anbieters ein. Laut [IONOS-Konfiguration](https://www.ionos.de/hilfe/e-mail/problemloesungen-mail-basicmail-business/e-mails-senden-mit-wordpress-problemloesungen-fuer-smtp/) gilt: `smtp.ionos.de`, Port **465**, **implizites TLS**, Authentifizierung an, vollständige Postfachadresse als Benutzername und Postfachpasswort. Alternativ Port 587 mit STARTTLS. Die Absenderadresse muss zur Domain des sendenden Postfachs gehören. Empfänger und Absender dürfen bei einem eigenen Kontaktpostfach dieselbe Adresse sein. Persönliche Kontodaten sind im Plugin nicht voreingestellt.

## Telegram in wenigen Schritten

1. In Telegram den offiziellen **@BotFather** öffnen. Mit `/newbot` einen eigenen Bot anlegen und den Bot-Token kopieren.
2. Im Plugin **Telegram** aktivieren, Token hinterlegen und speichern. Bei gültigem gespeichertem Token erscheint ein persönlicher Bestätigungscode, der mit `TSCB-` beginnt.
3. Den neu angelegten Bot im gewünschten **privaten Chat** öffnen, gegebenenfalls **Start** drücken und den vollständigen angezeigten Code als Nachricht senden.
4. Innerhalb von **zehn Minuten** im Plugin die Telegram-Empfängerbestätigung anklicken. Erst eine Nachricht mit dem passenden Code bestätigt und speichert die Chat-ID. Ein beliebiges `/start` genügt nicht. Der Code gilt nur für diesen Administrator und Bot, wird nach Erfolg verbraucht und darf nur im gewünschten Zielchat verwendet werden. Mehrere passende Chats werden abgewiesen.
5. Die gespeicherte Ziel-ID kontrollieren und einen Test senden. Die Nachricht muss im gewünschten Chat ankommen. Tests verwenden Beispieldaten entsprechend den gespeicherten Datenschutzeinstellungen und übermitteln keine Administrator-E-Mail-Adresse.

Ein Token authentifiziert den Bot; die Chat-ID bestimmt, wohin er schreibt. Für Gruppen und Kanäle die bekannte Ziel-ID manuell eintragen und speichern; den Bot vorher hinzufügen und mit Schreibrechten ausstatten. Für Gruppen kann die Chat-ID negativ sein. Am besten einen eigenen Bot für dieses Plugin einsetzen. Nutzt derselbe Bot einen Webhook einer anderen Anwendung, funktioniert Telegram `getUpdates` zur Empfängerbestätigung nicht; dann die bekannte Chat-ID manuell einsetzen. Dieses Plugin löscht keinen bestehenden Webhook.

Ohne erweiterten Datenschutz enthalten Telegram-Nachrichten alle gespeicherten Feldbeschriftungen und Besucherwerte in der Reihenfolge des Formulars. Entfernte Felder werden nicht übermittelt. Mit erweitertem Datenschutz enthält die Benachrichtigung nur Website-Namen und geschützten Administratorlink. Die Übertragung erfolgt als Klartext ohne Telegram-HTML-Formatierung.

Den Token geheim halten. Wer ihn kennt, kann den Bot steuern. Ist er versehentlich offengelegt worden, über BotFather ersetzen. [Offizieller Telegram-Einstieg](https://core.telegram.org/bots) und [Bot-API](https://core.telegram.org/bots/api).

## WhatsApp: offizieller Zugang erforderlich

WhatsApp ist eingerichtet, sobald ein passender Cloud-API-Zugang besteht. Die Ersteinrichtung ist aufwendiger als Telegram: Ein normaler WhatsApp-Account plus ein einzelner API-Key genügt nicht.

Benötigt werden ein eingerichtetes Meta-Business-/WhatsApp-Business-Platform-Konto, ein zum Senden berechtigter Zugangstoken, eine Cloud-API-Telefonnummer samt **Phone Number ID**, eine Empfängernummer inklusive Landesvorwahl und eine von Meta freigegebene Nachrichtenvorlage. Temporäre Tokens aus der Testeinrichtung laufen ab; für den Dauerbetrieb einen dafür vorgesehenen Token verwenden. Die Empfängernummer muss dem Erhalt dieser Benachrichtigungen zugestimmt haben. Meta kann Nachrichten berechnen.

Die Benachrichtigung geht an deine konfigurierte Betreiber-Nummer. Sie wird nicht an den Websitebesucher geschickt. Die folgende Konfiguration nutzt Vorlagen für ausgehende Benachrichtigungen und ist dadurch nicht davon abhängig, dass der Empfänger gerade eine Unterhaltung mit dem Business-Konto begonnen hat.

### Vorlage für vollständige Inhalte erstellen

Bei ausgeschaltetem erweitertem Datenschutz im WhatsApp Manager eine reine Textvorlage mit **genau fünf positionalen Body-Variablen** anlegen. Reihenfolge und Bedeutung sind fest:

| Variable | Plugin-Inhalt |
| --- | --- |
| `{{1}}` | Wert des ursprünglichen Namensfelds; bei Entfernung oder leerem Wert ein Gedankenstrich |
| `{{2}}` | Erste ausgefüllte gültige E-Mail-Adresse; sonst ein Gedankenstrich |
| `{{3}}` | Wert des ursprünglichen Betrefffelds; sonst „Kontaktanfrage“ in der Plugin-Sprache |
| `{{4}}` | Alle konfigurierten Felder mit Beschriftung und Wert, einschließlich zusätzlicher Felder |
| `{{5}}` | Website-Information |

Ein kurzer Vorlagentext als Ausgangspunkt:

```text
Neue Anfrage: Name {{1}}, E-Mail {{2}}, Betreff {{3}}.
Angaben: {{4}}. Website: {{5}}. Bitte direkt antworten.
```

Keine Header, Buttons oder weiteren dynamischen Felder verwenden. Benannte Parameter werden nicht unterstützt. **Den festen Text ohne Variablen auf höchstens 124 Zeichen begrenzen.** Das Plugin begrenzt die fünf eingesetzten Parameterwerte zusammen auf 900 Zeichen; zusammen bleibt dadurch ein bewusst konservatives Budget von 1.024 Zeichen. Im Meta-Dialog passende Beispielwerte angeben und die Vorlage zur Prüfung einreichen. Kategorie und Zulässigkeit richten sich nach dem tatsächlichen Anwendungsfall und Meta; dieser Vorschlag garantiert keine Freigabe.

### Separate Vorlage für erweiterten Datenschutz

Für erweiterten Datenschutz wird eine **eigene von Meta freigegebene Vorlage mit genau zwei positionsgebundenen Textparametern im Nachrichtentext** benötigt:

| Variable | Inhalt |
| --- | --- |
| `{{1}}` | Website-Name |
| `{{2}}` | Geschützte Administrator-URL der Anfrage |

Beispielstruktur: `Neue Anfrage auf {{1}}. Geschützt öffnen: {{2}}`. Keine variablen Header, Buttons oder weiteren Parameter verwenden. Den freigegebenen Namen in **Freigegebene Vorlage für erweiterten Datenschutz** eintragen; die bisherige Fünf-Parameter-Vorlage passt dafür nicht. Beide Vorlagen verwenden den konfigurierten Sprachcode. Meta prüft und entscheidet über die Freigabe; der Vorschlag ist keine Freigabezusage.

### Plugin verbinden

Nach Freigabe im Plugin **WhatsApp** einschalten und Folgendes eintragen:

- Access Token.
- Phone Number ID der sendenden Cloud-API-Telefonnummer, nicht die Telefonnummer selbst.
- Empfängernummer im internationalen Format, beispielsweise `491701234567`.
- Exakter Vorlagenname, beispielsweise `kontaktanfrage`.
- Exakter Sprachcode der freigegebenen Vorlage, beispielsweise `de`.
- Eine unterstützte Graph-API-Version; Anfangswert des Plugins ist `v24.0`.

Speichern und testen. Bei aktivem WhatsApp **ohne erweiterten Datenschutz** müssen alle Feldbeschriftungen und Antworten zusammen in 500 Zeichen passen. Diese Grenze betrifft den Gesamtinhalt, nicht nur das Nachrichtenfeld. Zusätzlich werden die fünf Parameterwerte zusammen auf höchstens 900 Zeichen begrenzt. Mit erweitertem Datenschutz entfällt diese Inhaltsgrenze für WhatsApp, weil nur Website-Name und geschützter Link übertragen werden; die normalen Feld- und Gesamtgrenzen des Formulars bleiben bestehen. Vorlagenwerte werden zu einzeiligem Text normalisiert. Inhalte werden nicht still gekürzt. Beide Versandmodi mit der jeweils tatsächlich freigegebenen Vorlage testen.

Bei Fehlern zuerst Token-Ablauf und Berechtigungen, Phone Number ID, Empfängernummer, Vorlagenstatus, Sprache und Parameteranzahl prüfen. Im Meta-Testmodus dürfen nur dafür freigegebene Empfänger erreichbar sein. Änderungen an Accounts, API-Versionen, Vorlagen und Meta-Richtlinien können Anpassungen erforderlich machen.

[Offizielle Cloud-API-Dokumentation](https://developers.facebook.com/docs/whatsapp/cloud-api/), [offizielle Meta-API-Beispiele](https://www.postman.com/meta/whatsapp-business-platform/documentation/wlk6lh4/whatsapp-cloud-api), [WhatsApp-Messaging-Regeln](https://business.whatsapp.com/policy).

## Weshalb Signal fehlt

Signal ist in dieser Version nicht enthalten. Das verbreitete Projekt [signal-cli](https://github.com/AsamK/signal-cli) beschreibt seine Schnittstelle als inoffiziell. Es benötigt einen eigenen laufenden Dienst, Konto-Verknüpfung oder Registrierung und regelmäßige Aktualisierungen. Das lässt sich nicht seriös als einfache Token-Eingabe in einem üblichen WordPress-Hosting anbieten.

## Geheimnisse und gespeicherte Daten

Die Tokens liegen bei Eingabe im Backend in WordPress-Optionen ohne Autoload. Das ist kein verschlüsselter Geheimnistresor; insbesondere Datenbankzugriff und Backups schützen. Alternativ können in `wp-config.php` vor dem abschließenden WordPress-Ladevorgang Konstanten gesetzt werden:

```php
define( 'TSCB_TELEGRAM_TOKEN', 'DEIN_TELEGRAM_BOT_TOKEN' );
define( 'TSCB_WHATSAPP_TOKEN', 'DEIN_META_ACCESS_TOKEN' );
```

Konstanten haben Vorrang vor den gespeicherten Tokenwerten. Die Platzhalter durch eigene Tokens ersetzen, die Datei nicht veröffentlichen und keine Tokens in Screenshots oder Supportanfragen zeigen.

Bei erweitertem Datenschutz speichert das Plugin Originalanfragen verschlüsselt nach der oben beschriebenen Aufbewahrungsregel. Zusätzlich verwendet die Missbrauchs- und Duplikaterkennung zeitlich begrenzte, mit einem Schlüssel gebildete Hashes, Zähler und Statusdaten in WordPress-Datenbank oder Object Cache: maximal fünf Versuche je zehn Minuten pro IP-Hash, Duplikatstatus für eine Stunde und Verarbeitungssperren für zwei Minuten (stündliche Bereinigung abgelaufener Datenbanksperren). Der letzte Zustand je Kanal enthält für höchstens 24 Stunden nur Zeit, Erfolg/Fehler und einen sicheren Fehlercode. Für das optionale E-Mail-Protokoll gilt unabhängig vom Anfragen-Postfach die Grenze von 50 Einträgen/sieben Tagen ohne Nachrichteninhalte. Telegram-Bestätigungscodes gelten höchstens zehn Minuten und werden nach erfolgreicher Empfängerbestätigung verbraucht. Rohe IP-Adressen werden dafür nicht im Plugin gespeichert. Hosting, WordPress-Erweiterungen, Maildienst und Messenger können eigene Protokolle und Nachrichtenbestände speichern.

Unter **Einstellungen → Datenschutz** stellt das Plugin einen Textbaustein für die Datenschutzerklärung bereit. Diesen an die tatsächlich verwendeten Kanäle, Empfänger, Aufbewahrung und Verträge anpassen. Ein Häkchen im Formular oder dieser Textbaustein garantiert keine rechtliche Konformität.

Bei der Deinstallation werden **gespeicherte Anfragen immer gelöscht**, unabhängig von der Löschoption. Diese Option steuert zusätzlich die Entfernung gespeicherter Einstellungen und Zugangsdaten. Laufzeitdaten in der Datenbank und bekannte Status-Transients werden ebenfalls bereinigt. Eine bloße Deaktivierung löscht keine Anfragen oder Einstellungen. Verbleibende anonyme Schlüssel in einem externen Object Cache laufen nach ihrer Lebensdauer ab. Bereits verschickte Nachrichten bei E-Mail-, Telegram- oder WhatsApp-Empfängern sowie Backups werden nicht entfernt. Konstanten in `wp-config.php` bei Bedarf selbst löschen.

## Betrieb und Fehlerbehebung

- **Nur einzelne Kanäle funktionieren:** Eine Bestätigung erscheint, sobald eine Anfrage für erweiterten Datenschutz sicher lokal gespeichert oder von mindestens einem Versandziel angenommen wurde. Auch bei vollständig fehlgeschlagener Benachrichtigung kann die Anfrage daher bereits im Postfach liegen. Anfragen und Kanaldiagnosen prüfen. Bereits akzeptierte Nachrichten können beim erneuten Ausfüllen und Senden nochmals ankommen; eine automatische Wiederholung oder Zustell-Webhooks sind nicht vorgesehen.
- **Formular lädt nicht oder sendet nicht:** Sicherheits- und Cache-Erweiterungen dürfen `wp-admin/admin-ajax.php` und die Formularaktionen nicht blockieren oder zwischenspeichern. Ohne JavaScript die Formularseite von langlebigem Seitencaching ausnehmen, damit der Formular-Nonce nicht abläuft.
- **Zu viele Anfragen:** Kurz warten. Die Begrenzung bezieht sich unter anderem auf die vom Server erkannte IP; mehrere Personen hinter derselben IP können sich ein Limit teilen. Proxyeinstellungen des Hostings beeinflussen dies.
- **Ausgehende API-Aufrufe schlagen fehl:** Der Webserver muss gültige HTTPS-Verbindungen zu `api.telegram.org` und bei WhatsApp zu `graph.facebook.com` aufbauen können.
- **Spam:** Honeypot, Zeitprüfung, Limits und Duplikatschutz reduzieren einfachen Missbrauch. Sie ersetzen keine Schutzmaßnahmen des Hostings gegen verteilte Angriffe.

## Einreichung bei WordPress.org

Die installierbare ZIP ist das technische Paket für die manuelle Prüfung. Eine Freigabe kann ausschließlich das WordPress.org-Plugin-Team erteilen. Folgendes bleibt vom Herausgeber zu erledigen:

1. Als öffentlicher Autor und Hersteller ist **[Tsambasis & Tsambasis](https://tsambasis.net/)** eingetragen. Das technische WordPress.org-Konto und der `Contributors`-Eintrag lauten **solutionfirst**. Produktname, Plugin-Metadaten und Oberfläche lauten in beiden Sprachen **ContactBridge**. WordPress.org hat den Verzeichnis-Slug `contactbridge` zugewiesen. Das Review ist weiterhin offen.
2. Das mitgelieferte Prüfprotokoll lesen; dort sind tatsächlich ausgeführte Tests und verbleibende Grenzen dokumentiert. Echten E-Mail-, Telegram- und WhatsApp-Versand mit den eigenen Produktionszugängen prüfen.
3. Den bestätigten Prüfstand einschließlich des offiziellen **Plugin Check** im Prüfprotokoll kontrollieren. Bei späteren Codeänderungen oder WordPress-Versionen erneut prüfen und den `Tested up to`-Wert nur entsprechend tatsächlich ausgeführter Tests anpassen.
4. Aktuelle [Plugin-Richtlinien](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/) beachten. Das Readme dokumentiert externe Dienste, übermittelte Daten, Lizenz und Servicebedingungen. Alle Quelldateien sind enthalten.
5. Mit dem eigenen Konto über [WordPress.org → Add Your Plugin](https://wordpress.org/plugins/developers/add/) die Plugin-ZIP einreichen und eventuelle Rückfragen des Review-Teams beantworten.

Es wurde nichts bei WordPress.org eingereicht und keine Messenger-Nachricht an echte Empfänger verschickt, sofern das Prüfprotokoll nicht ausdrücklich einen vom Betreiber eingerichteten Live-Test nennt. Ein öffentlicher Plugin-Slug ist erst nach Bestätigung durch WordPress.org gesichert.

Eine ausführlichere Einreichungsanleitung und eine englische Beschreibung zum Übernehmen stehen in der mitgelieferten Datei **WORDPRESS-ORG-EINREICHUNG.md**. Die Verzeichnis-Screenshots zeigen das tatsächlich laufende Plugin; englische Aufnahmen und deutsche Varianten können nach Freigabe in den separaten WordPress.org-Assetordner übernommen werden.
