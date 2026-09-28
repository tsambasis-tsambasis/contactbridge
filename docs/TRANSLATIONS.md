# Kontelio translations

Kontelio's source strings and fallback language are English. New settings follow the WordPress language. Administrators may choose an explicit supported plugin language; previously saved language choices and custom text are retained.

The installable plugin ZIP contains no PO, MO or compiled PHP translation catalogs. Translation sources and prepared language files are kept separately in the repository's `translations/` directory and distributed as a separate language-pack ZIP. A complete German translation is available there, but this does not mean it has been accepted or published by WordPress.org.

## Install German manually before the official pack is available

1. Obtain the matching German pack from the Kontelio delivery or repository `translations/` directory. This is a language pack, not an installable plugin.
2. Extract it locally. Copy `kontelio-de_DE.mo` into the site's language directory, normally:

   ```text
   wp-content/languages/plugins/kontelio-de_DE.mo
   ```

3. If the pack also provides `kontelio-de_DE.l10n.php`, copy its matching version to the same directory. Do not mix an older compiled PHP catalog with a newer MO file. The PO/POT files are editable translation sources; copying only a PO/POT file does not activate a translation.
4. Choose German in Kontelio, or use its WordPress-language setting with a German WordPress locale. Save, then reload the settings and public form.
5. Verify your own labels, consent text and messages. Those are site content and are not replaced merely because a translation was installed.

If `WP_LANG_DIR` was customized, use its `plugins/` subdirectory instead. Without an applicable installed catalog, untranslated strings remain English. Files named for the earlier `contactbridge` text domain do not supply translations for `kontelio`.

## Publish through WordPress.org later

First obtain confirmation of the requested `kontelio` slug in the existing plugin-review email thread. Once the approved plugin has a translation project, confirm that the text domain and project slug match, then import or submit the German PO strings to the relevant German project. Have a fluent German speaker review the wording and follow the locale team's glossary/style guide; do not present generated translations as already approved.

Request the relevant translation editors' review or appropriate PTE access. Imported suggestions are not an official language pack until approved and the platform's pack-generation requirements are met. The Polyglots guide currently describes a 90% approved-code-string threshold for an initial plugin pack. After publication, check that the pack exists, then use WordPress's translation updates. Readme translation is a separate project. See the official [Plugin/Theme Authors Guide](https://make.wordpress.org/polyglots/handbook/plugin-theme-authors-guide/) and [language-pack workflow](https://make.wordpress.org/meta/handbook/tutorials-guides/translations/).

Do not create a second plugin submission to obtain a translation project, and do not claim that choosing a repository name assigns the WordPress.org slug. The English fallback remains usable while review and translation approval are pending.

## Maintain translation sources

Keep `kontelio` as the single text domain and preserve placeholders such as `%s`, `%d`, `{max}` and `{privacy_link}`. Update the PO from the current POT, compile the MO and keep any compiled PHP catalog from the same revision. See [WordPress internationalization guidance](https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/).
