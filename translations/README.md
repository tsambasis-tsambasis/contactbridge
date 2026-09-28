# Kontelio German translation

English is Kontelio's source and fallback language. All extracted English messages have a complete German translation in the accompanying PO and MO files. The installable plugin ZIP contains no translation catalogs.

## Before WordPress.org approval

The translations have not yet been published or approved at translate.wordpress.org. To use German in a test installation now:

1. Copy `kontelio-de_DE.mo` to `wp-content/languages/plugins/kontelio-de_DE.mo` using your hosting file manager or SFTP. Create the `plugins` subdirectory if it does not exist. If your site defines a custom `WP_LANG_DIR`, use its `plugins` subdirectory instead.
2. For an existing German installation, install this language pack **before activating Kontelio** so default form labels continue to appear in German.
3. In Settings → Kontelio, use **WordPress language (default)** on a German WordPress site, or select **German** explicitly and save. Your existing saved language choice and custom form texts are retained.

This is a language pack, not a WordPress plugin: do not upload it through Plugins → Add New. The PO file is editable translation source, and the POT is the extracted translation template. Only the MO file is needed at runtime. Keep the translation pack outside the plugin folder so plugin upgrades do not remove it. If no matching regional German pack is installed, German regional locales can use this `de_DE` pack.

## After WordPress.org approval

Import `kontelio-de_DE.po` into the German translation project for the approved `kontelio` slug at translate.wordpress.org. A qualified translation editor must approve the translations before WordPress.org can distribute an official language pack. Request or verify that review; a prepared PO file does not mean translations are already publicly available. WordPress language-pack updates can then replace the manually installed catalog.

## Deutsche Kurzanleitung

Englisch ist die Ausgangs- und Rückfallsprache. Die vollständige deutsche Übersetzung wird separat bereitgestellt; das Plugin-ZIP enthält keine Übersetzungskataloge. Die Übersetzung ist noch nicht auf translate.wordpress.org freigegeben.

Kopieren Sie `kontelio-de_DE.mo` per SFTP oder Hosting-Dateimanager nach `wp-content/languages/plugins/kontelio-de_DE.mo`. Bei einem eigenen `WP_LANG_DIR` verwenden Sie dessen Unterordner `plugins`. Installieren Sie die Datei bei einer bestehenden deutschen Testinstallation **vor der Aktivierung von Kontelio**, damit deutsche Standardtexte weiter angezeigt werden. Wählen Sie unter Einstellungen → Kontelio entweder die WordPress-Sprache oder ausdrücklich Deutsch. Gespeicherte Spracheinstellungen und eigene Formulartexte werden nicht überschrieben.

Das Sprachpaket wird nicht über die Plugin-Installation hochgeladen. Die PO-Datei dient nach der Plugin-Freigabe dem Import in das offizielle deutsche Übersetzungsprojekt; dort ist zusätzlich die Freigabe durch einen zuständigen Übersetzungseditor erforderlich.
