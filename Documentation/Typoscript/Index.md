# Typoscript Konfiguration

Das Typoscript für ein Formular wird über einen mehrstufigen Prozess eingebunden. Die 
Grundkonfiguration liegt unter `config.tx_mkforms.`. Hier können global Einstellungen für verschiedene
Bereiche des Formulars gesetzt werden. Hervorzuheben ist besonders die Javascript-Konfiguration. Diese wird man 
in den meisten Fällen global anpassen wollen.

Für die Plugins wird unter `lib.mkforms.formbase` eine Basiskonfiguration bereitgestellt. Diese beinhaltet
die Daten aus `config.tx_mkforms.` in `lib.mkforms.formbase.formconfig.`.

Da die Konfiguration in mkforms immer direkt aus dem Kontext des Plugins gelesen wird, musst diese gesamte
Config auch noch im Typoscript des Plugins referenziert werden:
```
plugin.tx_myplugin {
	tickerform =< lib.mkforms.formbase
	tickerform {
    xml = EXT:myext/Resources/Private/Form/myform.xml
```

## Javascript
Die Extension liefert eine Version von jQuery mit. Auf den meisten Seiten ist jQuery aber schon global und vermutlich in
einer anderen Version vorhanden. Also sollte man mkforms global verbieten diese Bibliothek zu laden.

```
config.tx_mkforms.jsframework.jscore.tx_mkforms_jsbase >
```

## Referenz
### config.tx_mkforms.
* `absRefPrefix = /` # Serverprefix für die von mkforms geladenen JS-Dateien. Wenn die Angabe fehlt, wird der Hostname `TYPO3_SITE_URL` verwendet.
* `csrfProtection = 1` # Aktivierung CSRF Schutz
* `jsframework.jscore = jquery # Konfiguration der verwendeten JS-Bibliothek.
* `loadJsFramework = 1` # Aktivierung der Javascript-Features
* `minify.enabled = 1` # Minimierte Versionen von JS-Dateien laden
* `minify.gzip = 1` # JS-Dateien per gzip kompimieren
