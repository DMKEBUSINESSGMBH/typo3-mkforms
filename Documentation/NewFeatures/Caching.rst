.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _caching:

Caching
=======

Für Ajax calls wird kann das Typoscript gecached werden. Um dies zu Aktivieren muss config.tx_mkforms.cache.tsPaths auf 1 gesetzt werden.

Es können nun die TypoScript-Pfade konfiguriert werden, welche im Cache landen sollen. Beispiel: (config.tx_mkforms.)

.. code-block:: ts

   cache {
      ### Cachen des TS bei AjaxCalls aktivieren.
      tsPaths =  1
      tsPaths {
         ### Wir Cachen den kompletten Inhalt von lib.
         lib = 1
         ### wir cachen plugin. allerdings nur tx_mkforms. Alle anderen Keys werden ignoriert!
         plugin.tx_mkforms = 1
      }
   }
