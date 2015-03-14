.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _dependsOn:

Form XML: dependsOn
===================

Will man ein Renderlet in Abhängigkeit eines anderen verstecken, kann im XML das dependsOn angegeben werden. Das Renderlet bekommt dann im FE ein style="display:none;".

zusätzliche Tags:

* hideIfDependancyIs: das Element wird versteckt, wenn das dependsOn Renderlet einen angegebenen Wert enthält. Mehrere können Komma-getrennt angegeben werden.
* hideIfDependancyIsNot: Wie hideIfDependancyIs, nur das das Renderlet nicht dargestellt wird, wenn das dependsOn Renderlet deinen der Werte nicht enthält.


dependsOn im Validator
----------------------

DependOn kann auch im Validator genutzt werden. In diesem Fall wird nur validiert, wenn der Wert des dependsOn Renderlets WAHR ist. Beispiel:

.. code-block:: xml

   <renderlet:CHECKSINGLE name="custom" />
   <renderlet:TEXT name="customtext">
      <validators>
         <validator:STANDARD>
            <required dependson="custom" message="Required!" />
         </validator:STANDARD>
      </validators>
   </renderlet:TEXT>



skipIfEmpty im Validator
------------------------

im Validator kann skipIfEmpty angegeben werden, falls nicht validiert werden soll, wenn das Feld leer ist.

.. code-block:: xml

   <renderlet:TEXT name="customtext">
      <validators>
         <validator:STANDARD>
            <required skipIfEmpty="true" message="Wrong value!" />
         </validator:STANDARD>
      </validators>
   </renderlet:TEXT>