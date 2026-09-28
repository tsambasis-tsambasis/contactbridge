# Kontelio: bestehendes WordPress.org-Review aktualisieren

Version 1.0.0 ist noch nicht veröffentlicht. Der bisher zugewiesene Slug ist contactbridge; kontelio muss das Review-Team für denselben Vorgang bestätigen.

1. Das geprüfte `dist/kontelio-1.0.0.zip` in der bestehenden Einreichung über „Upload updated plugin for review“ nachreichen. Keine neue Einreichung anlegen.
2. Im bestehenden Review-Mailverlauf antworten. [REVIEW-REPLY.txt](REVIEW-REPLY.txt) enthält einen kurzen englischen Entwurf mit dem gewünschten Slug und der Herstellerzuordnung. Die Antwort wurde nicht versendet.
3. Hersteller ist Tsambasis & Tsambasis, Website https://tsambasis.net/, Kontomail kontakt@tsambasis.net und WordPress.org-Konto solutionfirst. Falls das Team weiteren Nachweis benötigt, kann auf tsambasis.net ein TXT-Eintrag am Domainstamm mit Wert `wordpressorg-solutionfirst-verification` eingerichtet werden. Es wurde kein DNS-Eintrag durch uns gesetzt oder verifiziert.
4. Nach Plugin-Freigabe die deutsche PO-Datei aus `translations/` im offiziellen Übersetzungsprojekt importieren und die Freigabe durch einen zuständigen Übersetzungseditor veranlassen. Bis das Sprachpaket veröffentlicht ist, gilt [TRANSLATIONS.md](TRANSLATIONS.md).
5. Verzeichnisgrafiken aus `.wordpress-org/` später separat in das oberste SVN-Verzeichnis `assets/` legen, nicht in den Plugin-Laufzeitordner.

Name: Kontelio - Contact Forms. Menü: Kontelio. Hersteller bleibt getrennt davon angegeben. Das Plugin-ZIP enthält keine gebündelten Übersetzungskataloge. Vor einem Upgrade einer deutschen Testinstallation das neue deutsche Sprachpaket installieren und die [Migrationsanleitung](UPGRADING.md) beachten.
