<?php
/**
 * Allows automatic class loading by including {classname}.php
 * when a new class is encountered
 */
function __autoload($class){
	$filenames = array(
		dirname(__FILE__) ."/php/$class.php",
		dirname(__FILE__) ."/tcpdf/".strtolower($class).".php"
	);
	foreach($filenames as $filename){
		if(file_exists($filename)){
			include_once $filename;
			return;
		}
	}
	$tried = implode(" -- ",$filenames);
	throw new CartoPressException("Unable to load class: $class <br>\n tried: $tried");
}

/**
 * Creates a CartoPress instance if this file was called directly
 */
if (__FILE__ == $_SERVER['DOCUMENT_ROOT'].$_SERVER['SCRIPT_NAME']){
	$cfg = Config::getInstance();
	if(!isset($cfg->blockDirect) || $cfg->blockDirect == 'false'){
		new CartoPress();
	}
}
?>