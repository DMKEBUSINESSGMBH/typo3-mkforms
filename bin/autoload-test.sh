#!/usr/bin/env php
<?php

require_once __DIR__ . '/../.Build/vendor/autoload.php';

$classes = [
#  'class.mainobject.php' => 'formidable_mainobject',
#  'class.mainscriptingmethods.php' => 'formidable_mainscriptingmethods',
#  'class.inlineconfmethods.php' => 'formidable_inlineconfmethods',
#  'class.maindataset.php' => 'formidable_maindataset',
#  'class.maindatasource.php' => 'formidable_maindatasource',
#  'class.mainvalidator.php' => 'formidable_mainvalidator',
#  'class.maindatahandler.php' => 'formidable_maindatahandler',
#  'class.mainrenderer.php' => 'formidable_mainrenderer',
#  'class.mainrenderlet.php' => 'formidable_mainrenderlet',
#  'class.mainactionlet.php' => 'formidable_mainactionlet',
#  'class.tx_ameosformidable.php' => 'tx_ameosformidable',
#  'class.csv.php' => 'CSV',
# 'jsmin.php' => 'JSMin',
 '' => '',
];

foreach ($classes as $filename => $class) {
    echo ($class);
    if (class_exists($class)) {
        echo ': is autoloadable: ' . $filename;
    } else {
        echo ': is not autoloadable.';
    }
    echo "\n";
}
