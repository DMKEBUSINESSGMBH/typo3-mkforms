Form Base
=========

Plugin
------

Die Action FormBase tx\_mkforms\_action\_FormBase beinhaltet Basisfunktionen für die Darstellung und Verarbeitung komplexer Formulare. Für die einfache Ausgabe eines XML Formulars im FE kann diese als Plugin auf einer Seite integriert werden.

Hooks
-----

**action\_formbase\_before\_processdata** Wird ausgeführt, bevor die procressData Methoden aufgerufen werden, um das Daten-Array zu modifizieren. Paramater: data =\> Daten-Array in Tabellenstruktur.

**action\_formbase\_after\_processdata** Wird ausgeführt, nachdem die procressData Methoden aufgerufen wurden, um das Daten-Array zu modifizieren. Paramater: data =\> Daten-Array in Tabellenstruktur.

**action\_formbase\_before\_filldata** Wird ausgeführt, bevor die fillData Methode aufgerufen wird, um das Daten-Array zu modifizieren. Paramater: data =\> Daten-Array in Tabellenstruktur.

**action\_formbase\_after\_filldata** Wird ausgeführt, nachdem die fillData Methode aufgerufen wurde, um das Daten-Array zu modifizieren. Paramater: data =\> Daten-Array in Tabellenstruktur.

Eigene Action für die Verarbeitung von Daten aus mehreren Tabellen
------------------------------------------------------------------

**TS Konfiguration**

im TypoScript lib.mkforms.formbase liegt die Basiskonfiguration, welche für die Action benötigt wird. Die Konfiguration könnte wie folgt aussehen

~~~~ {.sourceCode .ts}
plugin.tx_mkforms {
   ### Allgemeine Formulare ###
   extendedTemplate = EXT:mkforms/templates/formonly.html
   extended =< lib.mkforms.formbase
   extended {
      ### wird nur für Tests genutzt. Verhindert beispielsweise das Cachen des Formulars.
      testmode = 0

      ### XML form - set per action
      xml = EXT:mkforms/forms/xml/feuser.html


      addfields {
         ### setzt den Wert title, wenn er noch nicht existiert
         fe_users-name = Neuer Nutzer
         ### setzt den Wert oder überschreibt ihn.
         fe_users-module_sys_dmail_html = 1
         fe_users-module_sys_dmail_html.override = 1
         ### entfernt den Wert. 'unset' führt unset() aus, 'null' setzt den Wert auf null
         fe_users-uid = unset
      }

      ### Table <-> field separator in xml file
      fieldSeparator = -

      ### Fügt die PostVars zu dem Daten Array hinzu.
      addPostVars = 0

      ### Redirect options on successfully filled form. If not set, no redirect takes place!
      redirect {
         ### Page ID - set this to the target page id
         pid = 0
         ### To be defined on necessity
         parameters {
         }
      }
   }
}
~~~~

**XML**

Im Datahandler des XML's müssen die Methoden für das Füllen und Bearbeiten wie im Beispiel angegeben werden. Diese rufen automatisch die proceddData und fillData Methoden mit den aufbereiteten Daten auf.

Die Felder für die einzelnen Spalten der Tabellen müssen mit einem Trennzeichen, der im TS (fieldSeparator) wurde, als Name des Renderlets angegeben werden. Dabei wäre es möglich, ein Renderlet für mehrere Tabellenspalten zu nutzen. Die Tabellennamen müssen dann mit dem Trennzeichen getrennt vor dem Spaltennamen stehen (fe\_users-fe\_groups-pid).

~~~~ {.sourceCode .xml}
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<formidable>
   <control>
      <datahandler:RAW>
         <record><userobj extension="this" method="fillForm" /></record>
         <callback><userobj extension="this" method="processForm" /></callback>
      </datahandler:RAW>
   </control>
   <elements>
      <renderlet:HIDDEN name="fe_users-uid" />
      <renderlet:TEXT name="fe_users-name" />
      <renderlet:TEXT name="fe_users-email" />
      <renderlet:CHECKSINGLE name="fe_users-disable" />
      <renderlet:TEXT name="fe_groups-title" />
      <renderlet:TEXT name="fe_groups-description" />
      <renderlet:SUBMIT name="submit" />
   </elements>
</formidable>
~~~~

**Kindklasse**

Die eigene Action leitet von der Basis Action ab und benötigt im Grunde nur zwei Methoden. Eine für das vor-füllen des Formulars und eine für die Verarbeitung der Daten nach dem Absenden.:

~~~~ {.sourceCode .php}
class tx_mkforms_action_Extended extends tx_mkforms_action_FormBase {
   /**
    * @param   array    &$data
    * @return  array
    */
   protected function fillData(array $aParams) {
      $aData = array();

      $aData['fe_users']['username'] = 'testnutzer';
      $aData['fe_users']['email'] = 'testnutzer@das-medienkombinat.de';
      $aData['fe_users']['disable'] = 0;

      $aData['fe_groups']['title'] = 'Nutzergruppe';
      $aData['fe_groups']['description'] = 'Beschreibung';

      return $aData;
   }
   /**
    * @param   array    &$data Form data splitted by tables
    * @return  array
    */
   protected function processData(array $aData) {
      // $aData enthält die abgesendeten Daten.
      // Der Inhalt ist identisch dem aus fillData, wenn die Felder im XML entsprechend existieren.
      return $aData;
   }

   /**
    * Gibt den Name des zugehörigen Templates zurück und wird u.u. auch als ConfId genutzt.
    * Default wäre generic.
    *
    * @return  string
    */
   protected function getTemplateName() {
      return 'extended';
   }
}
~~~~

Es ist auch möglich, für jede Tabelle eine eigene processData Methode anzulegen. Dabei werden die Tabellennamen camel case umgewandelt und die Unterstriche entfernt:

~~~~ {.sourceCode .php}
class tx_mkforms_action_Extended extends tx_mkforms_action_FormBase {
   /**
    * @param   array    &$data
    * @return  array
    */
   private function processFeUsersData(array $aData) {
      // $aData enthält die abgesendeten Daten für den fe_user.
      // Der Inhalt ist identisch dem aus fillData, wenn die Felder im XML entsprechend existieren.
      return $aData;
   }
   /**
    * @param   array    &$data
    * @return  array
    */
   private function processFeGroupsData(array $aData) {
      // $aData enthält die abgesendeten Daten für den fe_user.
      // Der Inhalt ist identisch dem aus fillData, wenn die Felder im XML entsprechend existieren.
      return $aData;
   }
}
~~~~

Die Daten, welche die processDate Methoden zurückliefern, werden später im View als Marker bereitgestellt.

Zusätzliche Funktionen und Datenaufbereitung in Ajax-Calls und UserObj-Methoden
-------------------------------------------------------------------------------

Um die erweiterten Funktionalitäten im XML zu nutzen, muss ein codeBehind auf die Util-Classe tx\_mkforms\_util\_FormBaseAjax oder einer Kind-Klasse definiert werden.

~~~~ {.sourceCode .xml}
<codeBehind type="php" name="cbphp"
   path="EXT:mkforms/util/class.tx_mkforms_util_FormBaseAjax.php:tx_mkforms_util_FormBaseAjax"
/>
~~~~

Das Event an sich sieht beispielsweise wie folgt aus:

~~~~ {.sourceCode .xml}
<renderlet:BUTTON name="btnSave" label="Save and close">
   <onclick
      runat="ajax"
      cache="false"
      exec="cbphp.renderMajixOperations()"
   >
      <params>
         <param get="majixActionsAfterFinish::
                  fe_users-name|setValue|Neuer Nutzer|rdt:fe_users-uid == val:0,
                  fe_users-email|refresh,
               " />
      </params>
   </onclick>
</renderlet:BUTTON>
~~~~

**majixActionsAfterFinish**

Über den Parameter majixActionsAfterFinish können Komma getrennt Aktionen definiert werden, welche auf das entsprechende Renderlet ausgeführt werden. Für eine Aktion können 4 Angaben gemacht werden:

1.  [pflicht] Name des Renderlets (idealerweise qualifiziert).
2.  [pflicht] Name majix Methode (die verfügbaren majixMethoden sind im Renderlet zu finden).
3.  [optional] ein Parameter welcher der Methode übergeben wird.
4.  [optional] Bedingungen, welche zutreffen müssen, um diese Aktion auszuführen. (@see phpdoc tx\_mkforms\_util\_FormBaseAjax::evalSecureExpression)

Anstatt des Renderlets kann auch \_\_form\_\_ als erster Wert angegeben werden, um eine majix Methode des Formulars aufzurufen.

zusätzliche majix Methoden, welche nicht im Renderlet definiert sind:

-   refresh: führt einen majixRepaint Befehl aus, aktualisiert vorher die Werte des/der Renderlet/s

Um diese Funktion bei eigenen Methoden zu nutzen, muss die Funktion das Ergebnis der Methode buildAjaxReturn zurückgeben.

~~~~ {.sourceCode .php}
return self::buildAjaxReturn(
   $params,    //Parameter weiterleiten.
   $form,      //Formular weiterleiten.
   $data       //Daten für das füllen von Renderlets bei der Aktion refresh.
         // Diese Daten müssen in ihrer Quellform übergeben werden (siehe fillData Methode der FormBase Action)
);
~~~~

**Datenaufbereitung**

In der Action werden die Daten beim Füllen und Verarbeiten bereits aufbereiteten übergeben. Damit die Daten in den AjaxCalls genauso verarbeitet werden, stehen in der tx\_mkforms\_util\_FormBase folgende Methoden zur verfügung. (Die Klasse tx\_mkforms\_util\_FormBaseAjax leitet bereits von dieser ab.)

-   self::flatArray2MultipleTableStructure(\$params, \$form);  
    Wandelt die Werte der Renderlets in den Parametern in ihre Tabellenstruktur und liefert ein Array, wie es der processData Methode der FormBase Action übergeben wird.

-   self::multipleTableStructure2FlatArray(\$data, \$form);  
    Wandelt das Daten-Array mit der Tabellenstruktur, wie es in der fillData Methode der FormBase Action erzeugt wird, in ein flaches Daten-Array für einen Datahandler record.

-   self::multipleTableStructure2DeepArray(\$data, \$form);  
    Wandelt das Daten-Array mit der Tabellenstruktur, wie es in der fillData Methode der FormBase Action erzeugt wird, in ein Array, welches die Renderlet Struktur im XML repräsentiert. Das wird benötigt, um ein setValue auf ein Widget durchzuführen, welches Child-Elemente enthält. In self::buildAjaxReturn(); wird dies durchgeführt, die methode benötigt also die Quelldaten mit der Tabellenstruktur.

**TypoScript Konfiguration abfragen**

Es kann z.B. nützlich sein ein Widget abhängig von einer TypoScript Konfiguration zu verarbeiten. Dafür gibt es eine Methode, die in jedem Userobjekt verwendet werden kann. In diesem Fall muss noch *castToBoolean* gesetzt sein, damit der Wert auch korrekt verarbeitet wird. Ansonsten kann der Wert auch einfach verwendet werden wie er im TypoScript steht.

~~~~ {.sourceCode .xml}
<renderlet:BOX name="myRenderlet">
      <process>
         <userobj>
            <extension>tx_mkforms_util_FormBase</extension>
            <method>getConfigurationValue</method>
            <params>
               <param name="configurationId" value="shouldBeProcessed" />
               <param name="castToBoolean" value="1" />
            </params>
         </userobj>
      </process>
      ...
~~~~

Daten für static countries in einem Formular verwenden
------------------------------------------------------

Es gibt eine Methode, mit welcher Datensätze aus der static\_countries Tabelle abgefragt werden kann. Das kann wie folgt geschehen und z.B. in einem Select-Feld verwendet werden.

~~~~ {.sourceCode .xml}
<userobj extension="tx_mkforms_util_FormFill" method="getCountries">
   <params>
      <!-- use the german country column for the captions  -->
      <param name="caption_field" value="cn_short_de" />
      <!--
         these countries should ordered at the top of the list:
         54:Deutschland, 13:Österreich, 41:Schweiz, 104:Italien, 74:Großbritannien, 122:Liechtenstein
      --->
      <param name="add_top_countries" value="54,13,41,104,74,122" />
      <!-- seperate the top countries with a blank option -->
      <param name="add_top_country_delimiter" value="------------------------" />
   </params>
 </userobj>
~~~~
