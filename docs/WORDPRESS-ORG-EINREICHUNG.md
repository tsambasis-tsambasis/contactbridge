# ContactBridge – Einreichung bei WordPress.org

Erstveröffentlichung 1.0.0 · Stand: 27. September 2026 · Herausgeberkonto: [solutionfirst](https://profiles.wordpress.org/solutionfirst/)

Diese Unterlagen begleiten den laufenden Review-Vorgang. WordPress.org hat den Slug `contactbridge` zugewiesen; die Pluginprüfung ist weiterhin offen. Die Slugzuweisung ist keine Veröffentlichungsfreigabe.

## Welche Dateien wofür bestimmt sind

| Datei | Verwendung |
| --- | --- |
| `contactbridge-1.0.0.zip` | Installierbares Plugin und Paket für die Erstprüfung |
| `contactbridge-wordpress-org-assets-1.0.0.zip` | Verzeichnispaket: sechs Branding-PNGs und 16 Screenshots, insgesamt 22 PNGs |
| `contactbridge-branding-1.0.0.zip` | Nur die sechs Icons/Banner für die aktuelle Gestaltung |
| `ANLEITUNG.md` | Deutsche Einrichtung, Versandkanäle, Sprachen und Design |
| `PRUEFPROTOKOLL.md` | Tatsächlich ausgeführte Prüfungen und ihre Grenzen |
| `SHA256.txt` | Prüfsummen des Plugin-Pakets und des Verzeichnisgrafik-Pakets |
| `wordpress-org-assets/screenshot-*.png` | Echte Aufnahmen für die spätere Verzeichnisseite |
| `wordpress-org-assets/icon-*.png`, `wordpress-org-assets/banner-*.png` | Plugin-Icon sowie englische und deutsche Banner in Standard- und Retina-Auflösung |

In der ZIP liegen die Laufzeitdateien, Sprachressourcen, das englische `readme.txt` und die GPL-Lizenz. Der Produktname lautet in Oberfläche, Pluginheader, Readme-Titel und beiden Sprachen ausschließlich **ContactBridge**. Hersteller und Autor bleiben separat **Tsambasis & Tsambasis**. Pluginordner, Textdomain und zugewiesener Verzeichnis-Slug lauten `contactbridge`. Das Plugin verwendet ausschließlich den Shortcode `[contact_bridge]`. WordPress.org hat `contactbridge` als Slug zugewiesen; die Freigabe des Plugins steht weiterhin aus. Lokale Tests, Testzugänge, gespeicherte Anfragen und die isolierte WordPress-Testinstallation gehören nicht in das Uploadpaket. Die öffentliche Beschreibung der Verzeichnisseite wird aus dem Readme erzeugt. [Offizielle Readme-Dokumentation](https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/).

## Vorgehen

1. In der eigenen Testinstallation die ZIP hochladen, aktivieren und die gewünschten Kanäle mit den eigenen Zugangsdaten testen. Insbesondere Zustellung, Telegram-Ziel, beide WhatsApp-Vorlagentypen bei entsprechender Nutzung, geschützte Anfragen und Aufbewahrung prüfen. Lokale technische Prüfungen und deren Grenzen stehen im Prüfprotokoll; Produktionszustellung ist separat zu prüfen.
2. Mit dem Konto **solutionfirst** den bestehenden [Plugin-Review-Vorgang](https://wordpress.org/plugins/developers/add/) öffnen und das aktuelle ZIP gemäß den Vorgaben des Review-Teams zu diesem Vorgang nachreichen; keine doppelte Einreichung anlegen. Für ein optionales Beschreibungs- oder Kommentarfeld stehen unten passende englische Texte bereit.
3. Rückfragen des Review-Teams beantworten und gegebenenfalls angeforderte Änderungen vornehmen. Eine bestandene lokale Prüfung ersetzt die Verzeichnisprüfung nicht.
4. Nach Freigabe den bereitgestellten SVN-Zugang verwenden. Laufzeitdateien in `trunk/` und für die Erstveröffentlichung 1.0.0 in `tags/1.0.0/` veröffentlichen. Die Versionsangabe im Haupt-PHP-Header und `Stable tag: 1.0.0` im Readme müssen zusammenpassen. [Offizielle Informationen zu Readme und Stable Tag](https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/).
5. Die Verzeichnisgrafiken separat in das oberste SVN-Verzeichnis `assets/` neben `trunk/` legen. Die nummerierten Readme-Captions bestimmen die Bildunterschriften. [Offizielle Asset-Dokumentation](https://developer.wordpress.org/plugins/wordpress-org/plugin-assets/).

Die öffentliche Autor-/Herstellerangabe im Plugin lautet **[Tsambasis & Tsambasis](https://tsambasis.net/)**. `Contributors` und das technische Einreichungskonto lauten **solutionfirst**. Das WordPress.org-Profil wird durch die Plugin-Metadaten weder umbenannt noch geändert; einen anderen öffentlichen Profil-Anzeigenamen müsste der Kontoinhaber dort selbst pflegen. [WordPress.org zur Autor-/Contributor-Anzeige](https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/).

## Icon und Banner

Das vollständige Grafikpaket enthält diese sechs offiziellen PNG-Dateinamen zusätzlich zu den 16 Screenshots:

| Datei | Exakte Abmessungen | Dateigröße des gelieferten Assets |
| --- | --- | --- |
| `icon-128x128.png` | 128 × 128 Pixel | unter 1 MB |
| `icon-256x256.png` | 256 × 256 Pixel, Retina | unter 1 MB |
| `banner-772x250.png` | 772 × 250 Pixel | unter 4 MB |
| `banner-1544x500.png` | 1544 × 500 Pixel, Retina | unter 4 MB |
| `banner-772x250-de_DE.png` | 772 × 250 Pixel, deutsche Variante | unter 4 MB |
| `banner-1544x500-de_DE.png` | 1544 × 500 Pixel, deutsche Retina-Variante | unter 4 MB |

Die Abmessungen müssen zum Dateinamen passen. Jede Retina-Bannerversion ergänzt die normale Version derselben Sprache und kann sie nicht ersetzen. Alle sechs Dateien zusammen mit den Screenshots in das **oberste SVN-Verzeichnis `assets/` auf derselben Ebene wie `trunk/` und `tags/`** übernehmen. Das Grafik-ZIP enthält dafür bereits den Ordner `assets/`. Diese Dateien gehören nicht in `trunk/assets/` und nicht in die installierbare Plugin-ZIP. [Offizielle Vorgaben für Icons, Banner und Speicherort](https://developer.wordpress.org/plugins/wordpress-org/plugin-assets/).

Die aktuellen Verzeichnisbanner verwenden in beiden Sprachen ausschließlich den Produktnamen **ContactBridge** und jeweils passenden Nutzentext. Herstellerangaben sind vom Produktnamen getrennt. Die Icons bleiben sprachneutral und benötigen keine identischen deutschen Duplikate. Das vollständige Verzeichnisgrafik-Paket enthält 22 PNGs; das ergänzende Branding-Paket nur die sechs Icons/Banner. Beide sind vom installierbaren Plugin getrennt. Das kleine lokale Logo im Einstellungs-Kopfbereich ist dagegen ausdrücklich Bestandteil der Runtime.

Die Banner wurden mit dem eingebauten Imagegen-Werkzeug bearbeitet. Die unveränderten Eingaben sind in `branding/PROMPTS.md` dokumentiert; anschließend wurden die PNG-Master mechanisch auf die exakten WordPress-Abmessungen exportiert. Die Verzeichnis-Screenshots sind Aufnahmen des tatsächlich laufenden Plugins.

Der Nutzen wird als kostenloses Kontaktformular-Plugin ohne Werbung und ohne eigenes Tracking beschrieben, mit Versand an E-Mail, Telegram und WhatsApp. Kostenlos bezieht sich auf das Plugin; für die externe WhatsApp Business Platform können Gebühren entstehen. [Offizielle WhatsApp-Preise](https://whatsappbusiness.com/products/platform-pricing/).

Das Branding enthält kein DSGVO-Prüfsiegel und keine pauschale Rechtskonformitätsgarantie. Datenschutzkonformer Betrieb hängt von der Einrichtung, den ausgewählten Diensten und deren tatsächlicher Nutzung ab; die Verantwortlichkeiten des Websitebetreibers bleiben bestehen. [EDPB zu Verantwortlichen und Auftragsverarbeitern](https://www.edpb.europa.eu/sme/learn-the-basics/data-controller-or-data-processor_en).

## Screenshot-Zuordnung

Die Aufnahmen zeigen das tatsächlich gerenderte Plugin in der lokalen WordPress-Testinstallation. Es handelt sich um Benutzeroberflächen-Aufnahmen, nicht um gestaltete Produktmockups.

| Englische Standarddatei | Deutsche Variante | Inhalt / englische Bildunterschrift |
| --- | --- | --- |
| `screenshot-1.png` | `screenshot-1-de_DE.png` | Simple design mode with four presets and a live preview of unsaved changes. |
| `screenshot-2.png` | `screenshot-2-de_DE.png` | Advanced controls for layout, spacing, colors and appearance. |
| `screenshot-3.png` | `screenshot-3-de_DE.png` | Responsive contact form on a mobile screen. |
| `screenshot-4.png` | `screenshot-4-de_DE.png` | Dark contact-form theme on a desktop page. |
| `screenshot-5.png` | `screenshot-5-de_DE.png` | Delivery channel settings for email, Telegram and the official WhatsApp Cloud API. |
| `screenshot-6.png` | `screenshot-6-de_DE.png` | Form builder with custom fields, required options and ordering controls. |
| `screenshot-7.png` | `screenshot-7-de_DE.png` | Provider-neutral email settings and content-free delivery diagnostics. |
| `screenshot-8.png` | `screenshot-8-de_DE.png` | Protected inquiry inbox for extended-privacy notifications and local retention. |

Die nummerierten Basisnamen folgen dem WordPress-Schema; die `-de_DE`-Varianten ergänzen den deutschen Sprachcode für die lokalisierte Verzeichnisseite. Die Bilder gehören später in den **SVN-Verzeichnis-Assetordner**, nicht in den gleichnamigen CSS-/JavaScript-Ordner des installierbaren Plugins. [WordPress.org: Screenshots und lokalisierte Assets](https://developer.wordpress.org/plugins/wordpress-org/plugin-assets/).

## Englische Kurzbeschreibung

Free contact forms with email, Telegram and WhatsApp delivery, optional private notifications, a field builder and live design preview.

## Englische Beschreibung

ContactBridge combines a flexible shortcode contact form with email, Telegram and official WhatsApp Cloud API delivery. Use `[contact_bridge]` to add a responsive form, then choose one or several notification channels. Per-channel Extended privacy can keep submitted answers in an encrypted local inbox while sending only a protected administrator link and the site name. The plugin is free, without advertising or plugin tracking; external service charges may apply.

Build your form using 1–20 fields: text, email, telephone, textarea, select and checkbox. Add, remove and reorder fields, including the original name, email, subject and message fields. Configure labels, placeholders, required status, width and selection options. With extended privacy off, email and Telegram include all configured fields in order, and WhatsApp includes the complete labelled answers in the fourth parameter of its five-parameter template. Delivery limits are enforced without silently truncating answers. Removing the email field is allowed; full-content email notifications then have no visitor reply address unless another email field is filled in.

Enable Extended privacy separately for email, Telegram or WhatsApp. An enabled channel receives only the website name and a protected administrator link, with no submitted answers, visitor name, email address or subject. The original inquiry is encrypted and stored on the website. Channels with this option off still receive the full content, so mixed configurations must be reviewed per channel. Protected links require WordPress login and the manage_options capability; possession of the link does not grant access. Privacy-mode email notifications have no visitor Reply-To.

The protected inquiry inbox defaults to 30 days of retention. Set a different whole-day value or 0 for indefinite storage until manual deletion. Changes apply only to new inquiries; existing records retain their stored expiry. Expired records are cleaned through WordPress scheduled tasks. The inbox integrates with WordPress personal-data export and erasure tools. Database backups need their own retention policy. Encryption depends on WordPress site keys: losing or changing those keys can make existing records unreadable, and access to both keys and database permits decryption. Uninstall always removes locally stored inquiries, independently of the optional settings/credentials deletion choice.

Choose one of four design presets or adjust colors, spacing, form width, layout and button alignment in advanced mode. Field labels and placeholders are edited in the form builder. The live preview uses the same renderer as the public form and shows unsaved changes without sending messages. Light, dark and automatic themes work with rounded or square elements. An inline privacy link can be configured without writing HTML.

Seamless embedding is available directly below the design presets in both editing modes. It removes the outer card background, border, shadow, padding and margins while retaining field borders, field spacing, focus indicators, status boxes and the configured maximum width. Presets preserve this choice. The live preview shows unsaved changes against an example background; choose a light or dark theme and text colors that suit the actual page background. Use `[contact_bridge embedded="true"]` or `embedded="false"` to override the global setting for one form; `1` and `0` are also supported. Omitting the attribute uses the saved global setting.

The plugin starts in German. Switch its interface and standard messages to English, or let it follow the WordPress language. Language changes take effect after saving. Customized form text remains under your control and is preserved when switching languages.

The product name is ContactBridge throughout both languages, including plugin metadata, the interface and banners. Tsambasis & Tsambasis is identified separately as the author and manufacturer. The settings header uses a bundled local logo. While the plugin is active, View details opens a local WordPress dialog for administrators with the install_plugins capability, even before directory approval. The More information link, titled Tsambasis & Tsambasis, opens https://tsambasis.net/ only when selected; loading the settings does not fetch that website.

Email uses the existing WordPress mail transport or a provider-neutral SMTP configuration with STARTTLS/implicit TLS and certificate verification. Sender and recipient are configured separately. SMTP passwords are encrypted using WordPress site keys, or supplied through wp-config.php. Optional diagnostics retain at most 50 content-free events for seven days, with safe failure categories and administrator-only clearing. These SMTP settings apply only to this plugin; providers requiring OAuth can be used through an existing WordPress mail integration. Telegram requires a bot token and destination chat ID; an optional setup action confirms a private recipient using a temporary code sent to the bot. WhatsApp uses the official Business Platform Cloud API and requires business onboarding, an authorized access token, a Cloud API phone number and a Meta-approved body-only template. Full-content mode uses five positional text parameters; extended privacy requires a separately approved template with exactly two: website name and protected administrator URL. Connection tests respect the saved privacy settings and do not include the administrator's email address. Meta may charge for messages. Signal is not included.

The plugin includes server-side validation, a honeypot, timing checks, keyed rate limiting and duplicate-submission protection. Inquiry content is stored locally when needed for extended privacy; temporary operational hashes and status records remain separate. No remote fonts or frontend scripts are loaded, and no developer telemetry is collected. Messenger services are contacted only after configuration and through submissions or explicit administrator actions. External service endpoints, transmitted data, terms and privacy policies are documented in the included Readme.

A successful submission means that the inquiry was securely stored for extended privacy or that at least one configured transport accepted it. A stored inquiry can therefore succeed even when all external notifications fail; check the inbox and channel diagnostics. Success is not a delivery or read receipt, and failed notifications are not automatically retried. Actual provider delivery depends on the site's mail configuration and messaging accounts. The plugin is independent software and is not affiliated with Telegram, WhatsApp or Meta.

## Optionaler englischer Hinweis für das Review-Team

ContactBridge is submitted under GPLv2 or later. The plugin identifies Tsambasis & Tsambasis (https://tsambasis.net/) as its author; the WordPress.org contributor/submission account is solutionfirst. The assigned directory slug and plugin text domain are contactbridge. WordPress.org has assigned the slug contactbridge; the plugin review is still pending. The GitHub repository is https://github.com/tsambasis-tsambasis/contactbridge. All runtime PHP, CSS and JavaScript source is included, together with translation resources. There is no remote executable code, licensing server, tracking or telemetry. Telegram and WhatsApp integrations use their official HTTPS APIs; the Readme lists the endpoints, purposes, transmitted data and provider terms/privacy links. Messenger channels are disabled until an administrator configures and enables them.

Administrative settings, transport tests, inquiry actions and the read-only design preview use capability and nonce checks where applicable. Inquiry access requires an authenticated administrator with manage_options; links alone do not authorize access. The preview has no submission action and does not save options or send messages. Contact data is validated before processing. Credentials are not emitted in public form HTML or saved password-field values. Locally stored inquiries are encrypted and support configured retention, individual deletion and WordPress personal-data export/erasure; separate abuse-prevention and health records contain keyed identifiers and safe status information. The local plugin-details dialog and bundled logo require no external provider request.

The accompanying verification report covers the current identity and safe folder migration: 4 preparation and 22 migration checks, 30 language checks, 29 plugin-information checks, 17 current UI/screenshot checks and 12 branding checks passed. The official WordPress Plugin Check 2.1.0 reported no errors or warnings, including low-severity checks. All 16 directory screenshots show the actual current plugin. Settings remained byte-identical during the tested folder change, and encrypted example inquiries remained readable with unchanged WordPress keys. Earlier complete transport/security suites are not represented as newly rerun. External provider delivery requires the site operator's own provider setup.

When replacing an earlier development folder, deactivate the old plugin but do not use WordPress Delete: its uninstall handler removes locally stored inquiries. Back up the database and WordPress security keys, deactivate the old build, remove its folder through FTP or the hosting file manager without running uninstall, then install and activate only contactbridge/contactbridge.php. Verify saved settings and encrypted inquiries. Keep the security keys unchanged. Any removal of the old files must avoid running its uninstall handler.

## Was das Paket nicht zusagt

Die Sicherheits- und Funktionstests beziehen sich auf den im Prüfprotokoll genannten Stand und Umfang. Sie sind keine unabhängige Zertifizierung und beweisen nicht die Abwesenheit sämtlicher Fehler. Ebenso garantiert das Plugin weder WordPress.org-Freigabe, Meta-Template-Freigabe noch Rechtskonformität. Datenschutzinformationen und die eingesetzten Dienste müssen zum tatsächlichen Betrieb passen. [WordPress.org-Pluginrichtlinien](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/).
