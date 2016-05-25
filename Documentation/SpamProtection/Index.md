Spam Schutz
===========

Neben dem Captcha Widget gibt es noch weitere, nicht so invasive Möglichkeiten. Bei diesen müssen die Benutzer eines Formulars gar nicht aktiv werden oder etwas eingeben.

Honeypot
--------

Bots füllen für gewöhnlich alle Felder aus. Menschen sehen das Feld aber gar nicht, werden es also leer lassen. Dazu muss einfach ein Widget folgendermaßen eingefügt werden:

~~~~ {.sourceCode .xml}
<renderlet:TEXT name="website" custom=" style='display:none;'">
   <validators>
      <validator:STANDARD>
         <size message="LLL:msg_honeypot_error" value="0"/>
      </validator:STANDARD>
   </validators>
</renderlet:TEXT>
~~~~

Dieses Widget einfach in das gewünschte Formular einfügen und die Fehlermeldungen bereitstellen.

Timetracking
------------

Es kann auch geprüft werden ob ein Formular zu schnell oder zu langsam abgeschickt wurde. Dabei wird vom Erstellungszeitpunkt des Formulars also der Renderingzeit ausgegangen.

Zu schnell sind z.B. manchmal Bots.

~~~~ {.sourceCode .xml}
<renderlet:HIDDEN name="created">
   <validators>
      <validator:TIMETRACKING>
         <!-- alles was schneller als nach 10 Sekunden abgeschickt wurde, ist invalide -->
         <tooFast message="LLL:msg_timetracking_tooFast" threshold="10"/>
         <!-- alles was langsamer als nach 300 Sekunden (5 min) abgeschickt wurde, ist invalide -->
         <tooSlow message="LLL:msg_timetracking_tooSlow" threshold="300"/>
      </validator:TIMETRACKING>
   </validators>
</renderlet:HIDDEN>
~~~~

Dieses Widget einfach in das gewünschte Formular einfügen und die Fehlermeldungen bereitstellen.
