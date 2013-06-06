#!/usr/bin/php
<?

$version = date("Y.m.d.H.i.s");
chdir(dirname(__FILE__));

`rm -rf CartoPress`;
`mkdir CartoPress`;

$phpData = `cat ../class/*.php`;/**/
$phpData = str_replace("?>\n<?php","\n",$phpData);
file_put_contents("CartoPress/CartoPress.php",$phpData);

copy("../Config.cfg","CartoPress/Config.cfg");

$jsData = `cat ../js/*.js`;/**/
file_put_contents("CartoPress/CartoPress.js",$jsData);

shell_exec("tar -czf CartoPress-$version.tar.gz CartoPress");

?>