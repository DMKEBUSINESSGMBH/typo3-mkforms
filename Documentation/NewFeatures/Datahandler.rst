.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _datahandler:

Form XML: Datahandler
=====================


datahandler:RAW mit vorbelegten Werten
--------------------------------------

Der datahandler\:RAW konnte bisher nur die Daten nach dem Absenden des Formulars verarbeiten. Es war aber nicht möglich, einen initialen Datensatz bereitzustellen, der im Formular editiert werden kann. Dies war bisher nur mit dem datadandler:DB möglich. In MKFORMS kann der datahandler:RAW mit folgendem Code initialisiert werden:

.. code-block:: xml

    <datahandler:RAW>
     <record>
       <userobj>
         <php><![CDATA[
           return array('username'=> 'Heinz');
         ]]></php>
       </userobj>
     </record>
    </datahandler:RAW>

Man liefert über den XML-Wert von record also einfach ein flaches PHP-Array mit den passenden Key-Value-Paaren zurück. Die Keys sollten natürlich zu den Namen der Renderlets passen.

datahandler:MAIL als toller powermail ersatz
--------------------------------------------

Dieser Datahandler versendet anhand der Daten aus dem XML eine E-Mail. Im Moment wird nur das versenden über MK Mailer unterstützt.

Konfiguration:

.. code-block:: xml

    <datahandler:MAIL>
      <!-- Pflicht: Als Enginge für den Versand ist zur Zeit nur mkmailer möglich -->
      <engine>mkmailer</engine>
      <!-- Optional: Das Model, welches die Daten für die E-Mail bereit stellt
         Als Record werden die Daten aus dem Formular genutzt.
         Default ist tx_rnbase_model_base. -->
      <model>tx_rnbase_model_base</model>
      <!-- Pflicht: Die E-Mail-Adresse, an die versendet werden soll. -->
      <mailTo>mwagner@localhost.de</mailTo>
      <!-- Optional: Absender, wird default aus dem MKmailer-Mail-Template genommen. -->
      <mailFrom>ich@da.com</mailFrom>
      <!-- Optional: Absender, wird default aus dem MKmailer-Mail-Template genommen. -->
      <mailFromName>ich bin da</mailFromName>
      <!-- optional: Erzeugt eine Augabe der an das Model übergebenen flachen Daten. -->
      <debugData>TRUE</debugData>
      <!-- Spezielle Konfiguratione für den MK Mailer. -->
      <mkmailer>
         <!-- Pflicht: Der Key, unter dem das E-Mail-Template gefunden werden kann. -->
         <templateKey>mkexample_general_contact</templateKey>
         <!-- Optional: Die Confid, unter der das Typoscript für das rendering der Marker gefunden werden klann
            statisch wird immer plugin.tx_mkforms. voran gestellt. -->
         <markerConfId>sendmail.generalcontact.</markerConfId>
         <!-- Optional: Der Name des Datensatzes.
            Dieser wird für die erweiterung der Confid (
               markerConfId.contactsubject,
               markerConfId.contacttext,
               markerConfId.contacthtml
            ) und als Marker im template (###CONTACT_NAME###) genutzt.
            Default ist item -->
         <itemName>contact</itemName>
         <!-- Optional: Damit werden die im Template angegebenen mail from angaben
            mit denen aus dieser XML config überschrieben. -->
         <forceMailFrom>true</forceMailFrom>
      </mkmailer>
   </datahandler:MAIL>

TypoScript-Konfiguration:

.. code-block:: ts

   plugin.tx_mkforms {
      ### Die Confid ist abhängig von der Konfiguration im XML
      ### Default wird diese genutzt: generic.sendmail.contact
      ### Im Beispiel oben wurde eine definiert, womit sich folgendes ergibt: sendmail.generalcontact.contact
      ### .contact ist dabei der im XML angegebene itemName und wird duch die verschiedenen Parts ergänzt.
      sendmail {
         generalcontact {
            ### Hier kommt nun die normale Konfiguration für die Markerklasse rein
            ### Das können Links, Felder oder dcmarker sein.
            ### Als Markerklasse wird tx_rnbase_util_SimpleMarker genutzt.
            contactsubject {
               links {
               }
            }
            contacttext < .contactsubject
            contacthtml < .contactsubject
         }
      }
   }
   ### Konfiguration eines WrapperTemplates. Dieses ist via Default deaktiviert und muss gesetzt werden:
   plugin.tx_mkmailer.sendmails.email.wrapTemplate = 0


Nachbearbeitung von Initialdaten der Datahandler
------------------------------------------------

Mit dem datahandler\:DB kommt es öfters mal vor, daß man dem Formularrecord gerne noch weitere Daten hinzufügen möchte, oder vorhandene Daten vor der Verarbeitung im Formular bearbeiten will. Man denke nur an ein Datumsfeld. In der Datenbank wird der Datentyp date verwendet, das Kalender-Widget kennt aber nur den Timestamp. Diese Nachbearbeitung ist in mkforms jetzt einfach möglich:

.. code-block:: xml

   <datahandler:DB>
     <initrecord>
       <userobj>
         <php><![CDATA[
           // Jetzt noch einen Wert einfügen
           $record = $this->getParams();
           $record['date'] = tx_rnbase_util_Dates::date_mysql2tstamp($record['date']);
           return $record;
         ]]></php>
       </userobj>
     </initrecord>
   </datahandler:DB>
