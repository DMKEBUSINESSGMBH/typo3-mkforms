MK Forms
=======

![TYPO3 compatibility](https://img.shields.io/badge/TYPO3-10.4%20%7C%2011.5-orange?maxAge=3600&style=flat-square&logo=typo3)
[![Latest Stable Version](https://img.shields.io/packagist/v/dmk/mkforms.svg?maxAge=3600&style=flat-square&logo=composer)](https://packagist.org/packages/dmk/mkforms)
[![Total Downloads](https://img.shields.io/packagist/dt/dmk/mkforms.svg?maxAge=3600&style=flat-square)](https://packagist.org/packages/dmk/mkforms)
[![Build Status](https://img.shields.io/github/workflow/status/DMKEBUSINESSGMBH/typo3-mkforms/PHP-CI.svg?maxAge=3600&style=flat-square&logo=github-actions)](https://github.com/DMKEBUSINESSGMBH/typo3-mkforms/actions?query=workflow%3APHP-CI)
[![License](https://img.shields.io/packagist/l/dmk/mkforms.svg?maxAge=3600&style=flat-square&logo=gnu)](https://packagist.org/packages/dmk/mkforms)

What does it do?
----------------

Die TYPO3-Extension **MKFORMS** ermöglicht es HTML-Formulare für das Frontend zu erstellen. Das komplette Formular wird dabei in einer XML-Datei (oder alternativ per Typoscript) beschrieben.

Fork von ameos\_formidable
--------------------------

Bei **MKFORMS** handelt es sich um einen Fork der TYPO3-Extension **ameos\_formidable**. Ein Ziel der Entwicklung ist es weitestgehend kompatibel zu dieser Extension zu bleiben. Das Format der XML-Datei wird dabei nur in dringenden Fällen geändert, nach Möglichkeit aber höchstens erweitert. Die Dokumentation und die Beispiele für ameos\_formidable sollten daher fast immer funktionieren.

-   [http://formidable.typo3.ug/](https://web.archive.org/web/20181119062444/http://formidable.typo3.ug/)
-   [http://formidable.typo3.ug/reference.html](https://web.archive.org/web/20160506002926/http://formidable.typo3.ug/reference.html)
-   [http://wiki.typo3.org/index.php/Formidable_documentation](https://web.archive.org/web/20100706081508/http://wiki.typo3.org/index.php/Formidable_documentation)

[Zur Online Dokumentation](Documentation/README.md)
-------------------------------------------------


Ziele des Forks
---------------

Obwohl **ameos\_formidable** einen extrem hohen Entwicklungsgrad hat, gibt es einige Punkte, die den Einsatz der Extension erschweren bzw. verhindern. Der Fork soll folgende Probleme beheben:

-   Einsatz verschiedener JS-Bibliotheken wie JQuery. Ameos verwendet ausschließlich Prototype.
-   Verzicht auf die PHP-Session. Damit ist der Einsatz in Cluster-Umgebungen nicht möglich.
-   Einfachere Einbindung weiterer Formular-Elemente durch Auto-Loading Mechanismen. Ameos hat bisher ein sehr starres System.
-   Vereinfachung der API und Refactoring des Codes. Die Haupt-Formularklasse hat inzwischen über 8000 Zeilen Code.
-   es wird weiter entwickelt
-   TYPO3 6.2 kompatibel
-   Migration von ameos\_formidable Formularen relativ einfach möglich
-   Erweiterung der Widgets einfach möglich
-   Caching
-   mehr Sicherheitsfeatures
-   rn\_base Plugin zur Ausgabe
-   Uploadwidget für DAM und FAL
-   Fluidviewhelper
-   generischer Datahandler
-   verbesserter userfunc Ausruf innerhalb von Formularen

Breaking Changes since 10.4
---------------------------
- template paths have changed and need to be changed in all xml files and TypoScript configuration:
  - EXT:mkforms/templates/formonly.html is now EXT:mkforms/Resources/Private/Templates/formonly.html
    - May be used in plugins. So use this SQL query to migrate old paths in plugins:
      ```sql
      UPDATE tt_content SET pi_flexform = REPLACE(pi_flexform, 'mkforms/templates/formonly.html', 'mkforms/Resources/Private/Templates/formonly.html');
      ```
  - EXT:mkforms/widgets/lister/res/html/default-template.html is now EXT:mkforms/Resources/Private/Templates/Widgets/Lister/default-template.html



