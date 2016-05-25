Caching
=======

Für Ajax calls wird kann das Typoscript gecached werden. Um dies zu Aktivieren muss config.tx\_mkforms.cache.tsPaths auf 1 gesetzt werden.

Es können nun die TypoScript-Pfade konfiguriert werden, welche im Cache landen sollen. Beispiel: (config.tx\_mkforms.)

~~~~ {.sourceCode .ts}
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
~~~~
