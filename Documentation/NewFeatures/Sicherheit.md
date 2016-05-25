Sicherheit
==========

CSRF Schutz
-----------

In MKFORMS ist ein Cross Site Request Forgery Schutz implementiert. Jedem Formular wird ein verstecktes Feld hinzugefügt, welches einen eindeutigen Token für den Nutzer und das Formular enthält. Dieser Token wird in der Session gespeichert und bei einem Submit geprüft. Der Token selbst ist ein md5 Hash aus der Nutzer ID, der Formular ID und dem Encryption Key von TYPO3.

Senden nicht vorhandener Felder
-------------------------------

MKFORMS entfernt per default alle Felder eines Requests, die nicht durch Renderlets im XML repräsentiert werden.
