# Templating
In mkforms ist es möglich für das gesamte Formular, aber auch für einzelne Widgets wie LISTER und BOX ein HTML-Template 
zu hinterlegen.

## Formular

## LISTER

```xml
<renderlet:LISTER name="mylister" uidColumn="mylister-uid" useGP="false">
		<datasource use="mylisterdata" />
		<template
			path="EXT:myext/Resources/Private/Form/Html/myform.html"
			subpart="###MYLISTER###"
			alternateRows="###ROW###"
		/>
```

```html
<!-- ###MYLISTER### begin -->
	<div class="lister">
		<label class="formidable-rdrstd-label">{LLL:label_myform_someheader}</label>
		<div class="left width-135">
		<!-- ###ROWS### begin-->
		<!-- ###ROW### begin-->
		{mylister-col1.caption} <br /><span class="f-dark">({mylister-col1.value})</span><br />
		<!-- ###ROW### end-->
		<!-- ###ROWS### end-->
		</div>
	</div>
<!-- ###MYLISTER### end -->
```

## BOX
In diesem Beispiel für eine Box werden das Template und der Subpart per Typoscript angegeben.
```xml
		<renderlet:BOX name="box_data">
			<childs>
				<template
					path="TS:templates.box.data.file"
					subpart="TS:templates.box.data.subpart"
				/>

				<renderlet:TEXT name="field1" ></renderlet:TEXT>
```
Per Typoscript müssen dann natürlich noch die entgültigen Werte angegeben werden:

```
plugin.tx_myplugin {
	tickerform =< lib.mkforms.formbase
	tickerform {
    xml = EXT:myext/Resources/Private/Form/myform.xml
    formconfig.templates.box.data.file = EXT:myext/Resources/Private/Form/Html/myform.html
    formconfig.templates.box.data.subpart = ###BOXDATA###
```
Das HTML-Template sieht dann bspw. so aus:

```html
<!-- ###BOXDATA### -->
<span>{field1}</span>
<!-- ###BOXDATA### -->
```
