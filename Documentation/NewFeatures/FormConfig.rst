.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _formConfig:

Form XML: form config
=====================

Im XML sind nun unter anderem Folgende Konfigurationen für /meta/form/ möglich:


wrap
----

Wrapt das enthaltene und durch eine Pipe (|) getrennte HTML um das Formular herum.


method
------

Die Methode, wie der Browser das Formular absendet, kann hier entweder auf GET oder POST gesetzt werden Der Default-Wert für method ist POST.

Achtung: Wenn method auf GET gesetzt wird und useGP nicht definiert ist, wird useGP automatisch auf true gesetzt!

useGP
-----

Diese Einstellung definiert, ob das Formular die Daten aus dem POST (wenn usegp=false oder nicht gesetzt) oder auch aus den GET Parametern bezieht. Der Default-Wert für useGP ist false.

In Verbindung damit ist es möglich, mit der Einstellung useGPWithUrlDecode zu definieren, ob die GET Parameter mit einem urldecode umgewandelt werden. Der Default-Wert für useGPWithUrlDecode ist true.