# Moving an earlier development build to Kontelio

This guide covers earlier unpublished builds named ContactBridge or using the `contactbridge/` or `tsambasis-contact-bridge/` directory. The new package uses `kontelio/`, `kontelio.php` and text domain `kontelio`; its version remains **1.0.0** because it is still under review.

The folder/main-file change is a different WordPress plugin identity. Do not expect an ordinary ZIP replacement to deactivate or safely remove an older copy.

## Preserve existing data

1. Back up the WordPress database, the current plugin files and the site's security keys/salts. Keep the backup private.
2. Deactivate the earlier build. On multisite, also check network activation and individual sites.
3. **Do not click Delete for the old plugin in WordPress.** Its uninstall handler deletes shared local inquiries and may remove saved settings. This is destructive even when you intend only to remove the old name.
4. Remove the old plugin directory using the hosting file manager, SFTP or FTP after deactivation, without running its WordPress uninstall handler. Remove only the identified old plugin directory, not the database or site keys.
5. For an existing German installation, install the separate German language pack **before activating Kontelio**; see [TRANSLATIONS.md](TRANSLATIONS.md). This preserves German factory labels as well as saved custom text.
6. Install `kontelio-1.0.0.zip` and activate only Kontelio. Do not keep two active copies.
7. Check saved recipients, field definitions, design, custom text and existing encrypted inquiries. Send a deliberate test to each configured channel and inspect the resulting diagnostics.

The new build retains the existing internal option names and encryption contexts. Changing the public name does not require you to erase or recreate settings, SMTP credentials or local inquiries. Keeping the same WordPress keys is essential: changing them may make stored SMTP passwords and inquiries unreadable. A database backup alone is not enough to preserve decryptability if those keys are lost.

## Shortcodes and languages

Use `[kontelio]` for new content. Existing `[contact_bridge]` shortcodes continue to work, including their supported appearance attributes; replacing them immediately is unnecessary.

Previously saved language choices remain in place. New settings default to the WordPress language. Custom field labels, messages and consent wording remain custom content. An available translation can translate factory text; missing catalogs fall back to the English source.

Translations now live separately from the plugin ZIP. Old `contactbridge-*.mo` files do not provide the new `kontelio` text domain; install the matching new pack. Automatic WordPress.org packs are pending directory and translation approval.

## Uninstalling Kontelio deliberately

A genuine WordPress uninstall deletes the local inquiry archive, runtime records and delivery logs. The setting for deleting plugin data controls retained settings/credentials, not whether inquiries survive uninstall. External provider messages, wp-config constants and backups require separate handling.

Deactivation alone does not erase the archive. If you only want to stop using the form temporarily, deactivate it instead of uninstalling it.
