.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.


.. _introduction:

Introduction
============


What does it do?
----------------

Die Extension MKFORMS ermöglicht es relativ einfach HTML-Formulare für das Frontend zu erstellen. Das komplette Formular wird dabei in einer XML-Datei beschrieben.

Fork von ameos_formidable
-------------------------

Bei **MKFORMS** handelt es sich um einen Fork der TYPO3-Extension **ameos_formidable**. Ein Ziel der Entwicklung ist es weitestgehend kompatibel zu dieser Extension zu bleiben. Das Format der XML-Datei wird dabei nur in dringenden Fällen geändert, nach Möglichkeit aber höchstens erweitert. Die Dokumentation und die Beispiele für ameos_formidable sollten daher fast immer funktionieren.

* http://formidable.typo3.ug/
* http://formidable.typo3.ug/reference.html
* http://wiki.typo3.org/index.php/Formidable_documentation


Ziele des Forks
---------------
Obwohl **ameos_formidable** einen extrem hohen Entwicklungsgrad hat, gibt es einige Punkte, die den Einsatz der Extension erschweren bzw. verhindern. Der Fork soll folgende Probleme beheben:

* Einsatz verschiedener JS-Bibliotheken wie JQuery. Ameos verwendet ausschließlich Prototype.
* Verzicht auf die PHP-Session. Damit ist der Einsatz in Cluster-Umgebungen nicht möglich.
* Einfachere Einbindung weiterer Formular-Elemente durch Auto-Loading Mechanismen. Ameos hat bisher ein sehr starres System.
* Vereinfachung der API und Refactoring des Codes. Die Haupt-Formularklasse hat inzwischen über 8000 Zeilen Code.
* es wird weiter entwickelt
* TYPO3 6.2 kompatibel
* Migration von ameos_formidable Formularen relativ einfach möglich
* Erweiterung der Widgets einfach möglich
* Caching
* mehr Sicherheitsfeatures
* rn_base Plugin zur Ausgabe
* Uploadwidget für DAM und FAL
* Fluidviewhelper
* generischer Datahandler
* verbesserter userfunc Ausruf innerhalb von Formularen