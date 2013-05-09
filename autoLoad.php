<?php

//ini_set("html_errors",false);
/**
 * Allows automatic class loading by including {classname}.php
 * when a new class is encountered
 */
function __autoload($class){
	$filenames = array(
		dirname(__FILE__)."/$class.php",
		dirname(__FILE__)."/tcpdf/".strtolower($class).".php"
		
	);
	foreach($filenames as $filename){
		if(file_exists($filename)){
			include_once $filename;
			return;
		}
	}
	throw new CartoPressException("Unable to load class: $class");
}
class CartoPressException extends Exception {}

?>