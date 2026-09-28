# Install Kontelio

Download `kontelio-1.0.0.zip` for WordPress installation or the existing WordPress.org review. It contains only the `kontelio/` runtime folder, English readme and license.

The separate `kontelio-german-1.0.0.zip` is a language pack, not a plugin. Extract it and copy `kontelio-de_DE.mo` to `wp-content/languages/plugins/`. For existing German test installations, do this before activating Kontelio. See [translation instructions](../docs/TRANSLATIONS.md).

When replacing an earlier development folder, follow [UPGRADING.md](../docs/UPGRADING.md). Do not run the old plugin's WordPress uninstall handler; it deletes inquiries. Settings, shortcodes and encryption contexts are retained.

SHA256.txt records the two packages in this directory. WordPress.org approval, the new slug and official language-pack publication are still pending.
