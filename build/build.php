#!/usr/bin/php
<?

chdir(dirname(__FILE__));

if(file_exists("nextversion.txt")){
	$version = trim(file_get_contents("nextversion.txt"));
	$nextVersion = explode('.',$version);
	$nextVersion[count($nextVersion)-1] += 1;
	$nextVersion = implode('.',$nextVersion);
	file_put_contents("nextversion.txt",$nextVersion);
} else {
	$version = date("Y.m.d.H.i.s");
}

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