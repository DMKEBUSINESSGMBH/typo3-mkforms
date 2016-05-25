Widgets
=======

Allgemein
---------

Neben den bekannten Attributen, die ein Widget haben kann (siehe formidableDocLinks), kann auch das placeholder Attribut gesetzt werden. Innerhalb des Attributes kann sowohl normaler Text, ein Runnable oder auch ein LL Label (LLL:EXT:myext/locallang.xml:label\_path) angegeben werden.

Box
---

Damit Fehlermeldungen in einer Box nicht ausgegeben werden, muss "rendererrors" auf false gesetzt werden. Voreingestellt werden Errors in einer Box ausgegeben.

Lister
------

Die Werte für Elemente eines Listers mit activelistable werden nun auch aus den Parametern geholt. Wenn dies nicht gewünscht ist, kann im XML des Listers useGP auf false gesetzt werden.

**in Widget auf aktuelle Zeile zugreifen**

Wenn man z.B. einen Löschen-Button in einem Lister hat, dann benötigt dieser die Zeile in welcher er sich befindet. Das lässt sich folgendermaßen übergeben:

Wenn es eine column "uid" gibt, dann muss dem onclick Event (oder was sonst verwendet wird) folgender param gesetzt werden:

~~~~ {.sourceCode .xml}
<param get="rowData::uid" />
~~~~

autcomplete
-----------

Wenn ein autocomplete childs hat und defaultLL definiert ist, dann müssen diese childs ein label gesetzt bekommen. Oder defaultLL wird entfernt.

fluidviewhelper
---------------

Damit ist es möglich, einen ViewHelper für das Rendern zu erzeugen. Im Node params können alle Paremeter für den Helper angegebenw erden. Eine Besonderheit ist aktuell noch der Wert rdt:value, welcher durch den Wert des Renderlets ersetzt wird.

Beispiel im ein Bild anhand einer FAL-Referenz zu rendern:

~~~~ {.sourceCode .xml}
<renderlet:FLUIDVIEWHELPER name="companies-logos_uid" viewhelper="image">
   <params src="rdt:value">
      <param name="treatIdAsReference" value="true" />
      <param name="maxWidth" value="220" />
      <param name="maxHeight" value="145" />
   </params>
</renderlet:FLUIDVIEWHELPER>
~~~~
