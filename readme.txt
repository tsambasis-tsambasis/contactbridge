=== ContactBridge ===
Contributors: solutionfirst
Tags: contact form, telegram, whatsapp, email, shortcode
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html

Free contact forms with email, Telegram, WhatsApp, a field builder and optional private inbox. No ads or tracking.

== Description ==

ContactBridge connects a flexible WordPress contact form to email, Telegram and the WhatsApp Cloud API. Enable any combination of channels, or send private notifications that link to an encrypted local inbox.

* Free: no ads, paid features, analytics or remote assets.
* WordPress mail/TLS SMTP, Telegram and WhatsApp Cloud API.
* Private notifications, encrypted inbox and adjustable/permanent retention.
* Six field types, 1–20 fields, ordering and required/optional controls.
* Four presets, light/dark/auto, round/square, seamless embedding and live preview.
* German by default, English or WordPress language; custom text and styling.
* Server validation, honeypot, timing, rate limits and duplicate protection.

Hosting, email providers and Meta may charge. WhatsApp needs business onboarding, a Cloud API number, token and approved template, not a personal account. No affiliation with Telegram, WhatsApp or Meta; no legal-compliance guarantee.

= Extended privacy and the local inbox =

Enable Extended privacy per channel. It sends only a generic notice, site name and admin link: no visitor name, email, phone, subject or answers. Email omits visitor Reply-To. Provider credentials and routing metadata remain necessary. Channels with this mode off still receive full content.

If an active channel uses extended privacy, validated details are stored in this site's database using AES-256-GCM before delivery. OpenSSL is required. Storage failure blocks ALL channels; there is no plaintext fallback. Successful storage counts as acceptance even if notifications fail. Check the inbox and diagnostics; there is no retry queue.

Links contain an ID, not an access token. Access requires WordPress login and manage_options. No public/search/REST exposure. Admins can delete inquiries; WordPress personal-data export/erasure matches email fields.

Retention: 30 days by default, 1–36500 whole days or 0 permanently. Unsupported timestamp ranges are rejected. Changes affect NEW inquiries only. Expired items become unreadable and receive bounded hourly/admin cleanup. WP-Cron needs visits or server cron. Permanent items remain until deletion/uninstall; backups may retain copies.

Encryption uses WordPress security salts. Losing/changing keys makes existing inquiries unreadable; securely back them up. Database plus keys permit decryption. Use HTTPS; protect accounts, hosting and backups. Disabling privacy leaves existing inquiries.

= Fields and delivery limits =

Use text, email, telephone, textarea, select or checkbox fields. Set labels, placeholders, requirements, widths and up to 20 options. Original fields are removable; consent stays separate. Editing needs JavaScript; submissions do not. Unknown/removed fields are ignored; at least one answer is required.

Full-content email/Telegram include ordered labelled answers; the first completed valid email supplies Reply-To only for full-content email. Limits: 12,000 combined characters, plus field limits; full-content Telegram 4,000 UTF-16 units; full-content WhatsApp 500 labelled characters and 900 across five parameters. Provider content limits exclude privacy notifications. Oversized submissions are rejected, not truncated.

= Shortcode, design and language =

Use `[contact_bridge]`. Override an instance with `theme="light|dark|auto"`, `shape="rounded|square"`, `heading="Your heading"` or `embedded="true|false"`.

Seamless embedding removes the outer card, keeping fields/maximum width. Match colors to your theme. Preview never saves/sends; tests send notifications. Presets preserve content and privacy settings.

Set the consent link's label, URL and tab behavior. `{privacy_link}` positions it; otherwise it is appended. Without a URL it is text. Executable HTML/URL schemes are rejected.

Language changes apply after saving; custom text is preserved. Gettext catalogs/template included.

= External services =

Providers are contacted for enabled deliveries, requested tests and Telegram confirmation, never form/preview views. They see the server connection and routing/account data; notifications omit visitor IPs. Tests use sample data or a privacy notification with an inbox overview link, never the administrator's email as sample content.

**Email**

Use WordPress mail or your provider's SMTP host. Sender/recipient are separate. SMTP supports STARTTLS/implicit TLS and password/app-password login with certificate verification. No OAuth client is included. Settings affect this plugin only; existing mail integrations may take over. Full content includes labelled answers and optional visitor Reply-To; privacy sends notice, site name and admin URL. Provider/recipient retention applies. Acceptance does not prove arrival.

**Telegram Bot API**

Uses `https://api.telegram.org/bot<TOKEN>/sendMessage`: token, chat ID and full content or privacy notification. Link previews are disabled. Requested `getUpdates` confirmation saves a private chat only after matching the admin's temporary code. Chat members can read messages; bot chats are not Secret Chats.

* API: https://core.telegram.org/bots/api
* Terms: https://telegram.org/tos
* Developer terms: https://telegram.org/tos/bot-developers
* Privacy: https://telegram.org/privacy

**WhatsApp Business Platform / Meta Cloud API**

Uses `https://graph.facebook.com/<API_VERSION>/<PHONE_NUMBER_ID>/messages`: Bearer token, sender ID, operator number, template name/language and parameters. Full content uses five body parameters: name, email, subject, labelled details, site name. Privacy requires a SEPARATE approved template with two: site name, admin URL. Meta/recipient process this data; the recipient must agree to notifications. Meta determines approval, availability and charges.

* API: https://developers.facebook.com/docs/whatsapp/cloud-api/
* Terms: https://www.whatsapp.com/legal/WhatsApp-Terms-for-WhatsApp-Business-Platform
* Messaging policy: https://business.whatsapp.com/policy
* Privacy: https://www.whatsapp.com/legal/privacy-policy/
* Data-processing terms: https://www.whatsapp.com/legal/business-data-processing-terms

= Other stored data =

Full-content-only setups create no inquiry archive. Keyed IP/request hashes enforce five attempts/ten minutes; done status lasts one hour, locks two minutes, health 24 hours and Telegram codes ten minutes. No raw IP is stored. Optional email logs: up to 50 events/seven days, containing time, method, context, result and safe error category. No addresses, subjects, bodies, passwords or raw server replies. Admins can clear/disable logs. Hosting/software may keep other records.

SMTP passwords use salt-based encryption or `TSCB_SMTP_PASSWORD`; changed salts require re-entry. Messenger tokens are unencrypted, non-autoloading options, or use `TSCB_TELEGRAM_TOKEN` / `TSCB_WHATSAPP_TOKEN` in wp-config.php. Protect backups. Uninstall removes inquiries, database runtime data and logs; settings/credentials only if enabled. External-cache keys expire. Provider messages, backups and wp-config constants need separate handling.

== Installation ==

1. Upload/activate the ZIP in Plugins > Add New.
2. Open Settings > ContactBridge (German: Einstellungen > ContactBridge).
3. Configure channels, privacy and retention; save and test.
4. Add `[contact_bridge]` to a Shortcode block.
5. Adapt your privacy notice; test the public form and inbox.

Folder migration: back up the database and WordPress keys, deactivate an earlier build and remove its folder via FTP/file manager without uninstall; then install the contactbridge/ ZIP. Do NOT use WordPress Delete on the old plugin: uninstall removes inquiries. Activate only the new build and verify saved settings/inquiries. Keep site keys unchanged. WordPress.org has assigned contactbridge; plugin review remains pending.

Plugin details are local. Tsambasis & Tsambasis opens https://tsambasis.net/ when clicked.

= Telegram =

Create a bot with @BotFather and save its token. Send the TSCB-... code to the intended private chat and confirm within ten minutes; /start alone is insufficient. For groups/channels or existing webhooks, enter the chat ID manually and grant posting permissions. Webhooks are not removed.

= WhatsApp =

Enter Graph version (default v24.0), production token, Phone Number ID, international recipient, template name/language. Full-content templates require five positional body variables and at most 124 fixed-text characters. No header, button or named parameters.

Privacy needs its separate approved two-variable template, e.g. `New inquiry on {{1}}. Open securely: {{2}}`. Meta decides approval. Switching privacy off uses the configured five-variable template.

== Frequently Asked Questions ==

= Is Signal supported? =

No. Unofficial signal-cli needs a maintained service.

= Can caching or spam affect the form? =

Do not cache/block admin-ajax.php or admin pages. Without JavaScript, exclude form pages from long-lived caching. Changed fields may require reload. Shared IPs share quotas; distributed attacks need hosting protection.

== Screenshots ==

1. Simple design mode with presets and live preview.
2. Advanced layout, spacing, colors and appearance.
3. Responsive contact form on mobile.
4. Dark theme on desktop.
5. Delivery channels with extended privacy controls.
6. Form builder with custom fields and ordering.
7. Email, SMTP and content-free diagnostics.
8. Protected local inquiry inbox and retention information.

== Changelog ==

= 1.0.0 =

* Initial release.
