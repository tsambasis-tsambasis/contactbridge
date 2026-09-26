# ContactBridge by Tsambasis

![ContactBridge icon](assets/plugin-icon.png)

A free WordPress contact-form plugin with email, Telegram and WhatsApp delivery. No ads, paid features or plugin tracking.

The German interface is named **ContactBridge von Tsambasis**. Built by [Tsambasis & Tsambasis](https://tsambasis.net/).

## Features

- Add a responsive form with `[contact_bridge]`.
- Combine email, Telegram and the official WhatsApp Cloud API.
- Use WordPress mail or configure your own SMTP server with TLS.
- Build forms with text, email, telephone, textarea, select and checkbox fields.
- Choose design presets or customize layout and colors with a live preview; light, dark and automatic themes support rounded or square fields.
- Enable private notifications per channel, with an encrypted local inquiry inbox.
- Use German, English or the WordPress language. German is the default; custom form text stays under your control.

## Requirements

- WordPress 6.6 or later; tested with WordPress 7.1.2.
- PHP 7.4 or later.
- PHP OpenSSL for encrypted inquiry storage and SMTP password storage; outbound connections for the selected delivery providers.

## Installation

1. Download the packaged **[contact-bridge-1.0.0.zip](https://github.com/tsambasis-tsambasis/tsambasis-contact-bridge/raw/refs/heads/main/dist/contact-bridge-1.0.0.zip)**. GitHub's automatically generated **Source code** archives are not the installation package.
2. In WordPress, open **Plugins → Add New → Upload Plugin**, upload that ZIP and activate it.
3. Open **Settings → ContactBridge by Tsambasis** (German: **Einstellungen → ContactBridge von Tsambasis**).
4. Configure at least one delivery channel, save the settings and send a connection test.
5. Add a Shortcode block to a page:

   ```text
   [contact_bridge]
   ```

For example, override the appearance of one form:

```text
[contact_bridge theme="dark" shape="square" heading="Contact us"]
```

Use `embedded="true"` to remove the outer form card. Verify the published page on desktop and mobile, and adapt your site's privacy notice to the channels you enable.

## Delivery setup

| Channel | Setup |
| --- | --- |
| Email | Recipient address and the existing WordPress mail configuration, or your SMTP server, sender address and credentials. |
| Telegram | A bot token and destination chat ID. A temporary confirmation code can help connect a private chat. |
| WhatsApp | Official Business Platform Cloud API access, a phone-number ID, an authorized token, a recipient and a Meta-approved template. |

WhatsApp uses a five-parameter body template for full-content notifications, or a separate two-parameter template for private notifications. A personal WhatsApp account alone is insufficient. Hosting, mail providers and Meta may charge for their services. Signal is not included.

Connection tests send notifications to your configured recipients. The design preview does not send messages. Transport acceptance does not guarantee delivery to an inbox or a read receipt.

## Privacy and storage

**Extended privacy is optional and configured separately for each channel.** An enabled channel receives only a generic notice, the site name and a protected administrator link. The original inquiry is encrypted in your WordPress database; access requires login and the `manage_options` capability. Channels with this option disabled still receive the submitted content.

The local inbox defaults to **30 days** of retention. Choose another supported whole-day value, or **0** for indefinite storage until deletion. Changes apply only to new inquiries. Cleanup relies on WordPress scheduled tasks; backups need their own retention policy. WordPress personal-data export and erasure are supported.

Encryption depends on WordPress security keys: losing or changing them can make saved inquiries unreadable. Protect your keys and database backups. A safely stored inquiry counts as received even if every external notification fails, so check the inbox and channel diagnostics. Failed notifications are not automatically retried.

The plugin loads no remote fonts or frontend scripts and collects no developer telemetry. Enabled messaging services receive the information needed to deliver notifications. See [readme.txt](readme.txt) for service endpoints, transmitted data, provider terms, retention details and uninstall behavior.

## Support and license

- Website and contact: [Tsambasis & Tsambasis](https://tsambasis.net/).
- License: [GPLv2 or later](LICENSE).

ContactBridge is independent software and is not affiliated with Telegram, WhatsApp or Meta. This repository does not imply approval or listing in the WordPress.org plugin directory.
