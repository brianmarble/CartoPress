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

function handleError($errno, $errstr, $errfile, $errline, array $errcontext)
{
    // error was suppressed with the @-operator
    if (0 === error_reporting()) {
        return false;
    }

    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}
set_error_handler('handleError');

?>