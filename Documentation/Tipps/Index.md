Tipps und Tricks
================

Tests
-----

**FormAction**

Bei einem FormAction Test, muss für gewöhnlich die MKFORMS TS Konfig geladen werden. Dazu folgenden Aufruf setzen bevor das eigene TS geladen wird:

~~~~ {.sourceCode .php}
tx_mklib_util_TS::loadConfig4BE(
   'mkforms','mkforms','/static/ts/setup.txt',array(),true
);
~~~~

Außerdem muss noch der Testmode gesetzt werden.

Wenn es nicht nötig ist das der ganze Rendering Prozess durchlaufen wird, dann einfach die Konfig für das XML entfernen. Dann wird eine Fehlermeldung zurückgegeben.

**processForm**

Wenn man diese Funktion testen möchte, dann muss folgendes gemacht werden bevor handleRequest aufgerufen wird. Die Formdaten müssen in \$\_POST geschrieben werden mit AMEOSFORMIDABLE\_SUBMITTED=AMEOSFORMIDABLE\_EVENT\_SUBMIT\_FULL. Ein Beispiel findet sich in tx\_mkforms\_tests\_action\_FormBase\_testcase. Evtl. sollte noch der CSRF Schutz deaktiviert werden für Tests.

**fillForm**

siehe tx\_mkforms\_tests\_action\_FormBase\_testcase

Ajax-Calls
----------

Wichtig bei Ajax-Calls ist vor allem die Parameterübergabe an den Server. Hier gibt es spezielle Parameterwerte um die richtigen Daten zu übergeben. So sieht grundsätzlich ein Button aus, der einen Ajax-Request startet:

~~~~ {.sourceCode .xml}
<renderlet:BUTTON name="btn_beuser" label="Edit BE User" >
   <onclick
      runat="ajax"
      params="rowInput::uid, btnparam::editBeUser"
      cache="false"
      exec="cb1.btnUserEdit_click()"
   />
</renderlet:BUTTON>
~~~~

Folgende Prefixe sind von Ameos vorgegeben:

-   **rowData::** - Zugriff auf einen Datensatz im Widget Lister. Hier kann also in jeder Zeile ein Button mit einem Event auf diesen Datensatz eingebaut werden
-   **rowInput::** - Zugriff auf einen Wert im aktuellen Formular. Alternativ kann man auch direkt den Namen des Widgets angeben. Siehe unten.
-   **sysEvent.** - Funktion noch unbekannt
-   **[absWidgetName]** - Wenn man nur den absoluten Namen eines Widget angibt, dann wird dessen Wert übertragen.

**Nochmal zur Klarheit**: Diese Angaben werden letztendlich auf Client-Seite per Javascript ausgewertet. Der Server stellt sie nur in einem Format bereit, damit der Client weiß, was am Ende genau übertragen werden soll. Somit wird bei Ajax-Call letztendlich der aktuelle Wert des entsprechenden Widgets übertragen.

Action URL
----------

Wenn ein Formular in Zusammenspiel mit einem Pagebrowser verwendet wird, kann es zu Problemen mit der Action URL kommen. Folgendes Szenario:

Ein Formular wird für eine Suche verwendet. Hat man nun Suchergebnisse für 3 Seiten und schickt auf der 3. Seite die Suche mit einem neuen Suchbegriff ab, der nur Treffer für 1 Seite zurück liefert, würden angeblich keine Ergebnisse gefunden, was auch logisch ist. Man ist in diesem Moment auf der 3. Ergebnisseite, hat aber nur Treffer für die 1. Ergebnisseite.

Daher gibt es die Möglichkeit in Formularen in folgenden Pfad "/meta/form/" das Attribut "action" auf "current" zu setzen. Damit wird immer die URL der aktuellen Seite ohne zusätzliche Parameter angegeben.

Mit einer aktuellen rn\_base Version ist das nicht mehr notwendig bei Pagebrowsern. Diese erkennen selbstständig ob die geforderte Seite valide ist. Wenn nicht, wird die erste angezeigt womit dieser Fehler nicht mehr auftreten kann.

includeXml
----------

Beispiel für ein XML, welches seine Elemente aus einem anderen XML zieht. Dabei werden manche Felder überschrieben.

~~~~ {.sourceCode .xml}
<mkforms version="1.0.10">
   <meta>
      <includeXml
         path="EXT:mkexample/forms/xml/gameForm.xml"
         xPath="/formidable/meta/" />
      <form formid="gameForm" disableButtonsOnSubmit="false"/>
   </meta>
   <control>
      <renderer:TEMPLATE>
         <template>
            <includeXml
               path="EXT:mkexample/forms/xml/gameForm.xml"
               xPath="/formidable/control/renderer/template/" />
            <path>EXT:mkexample/forms/html/gameFormFacebook.html</path>
         </template>
      </renderer:TEMPLATE>
      <datahandler:RAW>
         <includeXml
            path="EXT:mkexample/forms/xml/gameForm.xml"
            xPath="/formidable/control/datahandler" />
      </datahandler:RAW>
   </control>
   <elements>
      <renderlet:BOX name="gameFormData" mode="fieldset" class="" defaultWrap="false">
         <childs autowrap="false">
            <includeXml
               path="EXT:mkexample/forms/xml/gameForm.xml"
               xPath="/formidable/elements/renderlet[name=gameFormData]/childs/renderlet" />
            <renderlet:RADIOBUTTON name="choice" addNoLabelTag="true" validateForDraft="true">
               <validators>
                  <validator:STANDARD>
                     <required message="LLL:msg_form_gamechoice_required"/>
                  </validator:STANDARD>
               </validators>
            </renderlet:RADIOBUTTON>
         </childs>
      </renderlet:BOX>
      <includeXml
         path="EXT:mkexample/forms/xml/gameForm.xml"
         xPath="/formidable/elements/renderlet[name=captchaLabel]" />
   </elements>
</mkforms>
~~~~

Mehrfach abschicken eines Fromulares verhindern (Doppelklick)
-------------------------------------------------------------

-   **disableButtonsOnSubmit** - Buttons werden nach einem Submit deaktiviert (Default = true)

~~~~ {.sourceCode .xml}
<meta>
   <form formid="mkexample" class="fields1colums" action="current" disableButtonsOnSubmit="false"/>
   ...
~~~~

-   **displayLoaderOnSubmit** - Zeigt den Loader bei einem Submit (Default = false)

~~~~ {.sourceCode .xml}
<meta>
   <form formid="mkexample" class="fields1colums" action="current" displayLoaderOnSubmit="true"/>
   ...
~~~~

Subtemplates im HTML verwenden
------------------------------

Damit wiederkehrende Templateteile bequem an einer Stelle gepflegt werden können, können rn\_base Subtemplates verwendet werden. Dazu folgendes in einem HTML Template einbinden:

~~~~ {.sourceCode .html}
<!-- ### INCLUDE_TEMPLATE EXT:myext/Resources/Private/Forms/Html/Includes/mySubTemplate.html@MYSUBPART ### -->
~~~~

Das dazugehörige Subtemplate könnte so aussehen:

~~~~ {.sourceCode .html}
###MYSUBPART###
   {mySubTemplateWidget}
###MYSUBPART###
~~~~
