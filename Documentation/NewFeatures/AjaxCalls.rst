.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. _ajaxCalls:

Form XML: AJAX Calls
====================

Instanzieren und Laden von Klassen
----------------------------------

Klassen und Objekte, welche für Ajax benötigt und gecached werden, sollten mit dem Loader von MKFORMS geladen werden, da diese beim restoreForm automatisch geladen werden.

.. code-block:: php

   $form->getObjectLoader()->load($sClass, $sPath = false);
   $form->getObjectLoader()->makeInstance($sClass, $sPath = false);

Als Fallback wird eine unserialize_callback_func registriert, welche versucht fehlende Klassen zu laden. Hier passiert automatisch ein Logging.
Warn, wenn erfolgreich geladen.

Fatal, wenn nicht geladen werden konnte. Hier wird das Autoloading (tx_rnbase::load) genutzt.

Alternativ kann ein Hook (autoload_unserialize_callback_func) genutzt werden, um eine eigene Fehlerbehandlung zu integrieren.

Freie Parameter
---------------

Neben den normalen Parametern zur Übergabe der aktuellen Werte von Widgets, kann man jetzt auch zusätzliche, konstante Parameter per Ajax an den Server schicken:

.. code-block:: xml

   <renderlet:BUTTON name="btn_beuser" label="Edit BE User" >
       <onclick
      runat="ajax"
      params="btnparam::editBeUser"
      cache="false"
      exec="cb1.btnUserEdit_click()"
       />
   </renderlet:BUTTON>

Der Parameter ist dann hier im Beispiel "btnparam" und der Wert "editBeUser".

Shortcut um alle Felder einer Box zu übertragen
-----------------------------------------------

Hier werden alle Felder der Box mit dem Namen beuserbox per Ajax übertragen:

.. code-block:: xml

   <renderlet:BUTTON name="btnSave" label="Save and close">
     <onclick
       runat="ajax"
       params="beuserbox__*"
       cache="false"
       exec="cb1.btnUserSave_click()"
     />
   </renderlet:BUTTON>

Automatische Feldvalidierung
----------------------------

Normalerweise wird bei Ajax-Calls keine Validierung durchgeführt. Man kann dies aber jetzt zusätzlich aktivieren. Dazu setzt mal das Attribute validate:

.. code-block:: xml

   <renderlet:BUTTON name="btnSave" label="Save and close">
     <onclick
       runat="ajax"
       params="beuserbox__*"
       validate="1"
       cache="false"
       exec="cb1.btnUserSave_click()"
     />
   </renderlet:BUTTON>

Damit werden alle Felder validiert, die bei diesem Ajax-Call übertragen werden.

TODO: Fehlermeldung zurückgeben.