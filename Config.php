<?php


/**
 * Parses the CartoPress configuration file and is used as a container 
 * for the configuration data.
 */
class Config {

	const COMMENT_REGEX = '~#[\\s\\S]*$~';
	const SECTION_REGEX = '~[[]([a-zA-Z0-9_]+)[]]~';
	const KEY_VALUE_REGEX = '~^([^=]+)=([^=]*)$~';
	const LAYOUT_REGEX = '~([^\\t]*)(?:\\t)\s*([^\s]+)~';
	const CONFIG_FILENAME = "Config.cfg";
	private static $instance;

	//public static function getInstance(){
	//	if(!isset(Config::$instance)){
	//		Config::$instance = new Config();
	//	}
	//	return Config::$instance;
	//}
	
	public function __construct(){
		//$configFileLines = explode("\n",file_get_contents(self::CONFIG_FILENAME));
		//$currentSection = 'initial';
		//foreach($configFileLines as $line){
		//	$strippedLine = trim(preg_replace(self::COMMENT_REGEX,'',$line));
		//	if(!empty($strippedLine)){
		//		if(preg_match(self::SECTION_REGEX,$strippedLine,$matches)){
		//			$this->extractSection($currentSection,$lines);
		//			$currentSection = $matches[1];
		//			$lines = array();
		//		} else {
		//			$lines[] = $strippedLine;
		//		}
		//	}
		//	
		//}
		//$this->extractSection($currentSection,$lines);
	}
	
	public function getFormats(){
	
	}
	
	/**
	 * Directs the parsing of a specific configuration section to a 
	 * method by the name extract_[section]. Each section in the 
	 * configuration file should have an extract function.
	 */ 
	private function extractSection($section,$lines){
		$sectionMethod = 'extract_'.$section;
		$this->$sectionMethod($lines);
	}
	
	private function extract_initial($lines){
		foreach($lines as $line){
			preg_match(self::KEY_VALUE_REGEX,$line,$matches);
			$key = trim($matches[1]);
			$value = is_numeric($matches[2]) ? $matches[2] + 0 : trim($matches[2]);
			$this->$key = $value;
		}
	}
	
	private function extract_hosts($lines){
		$this->hosts = $lines;
	}
	
	private function extract_pageSizes($lines){
		$this->pageLayouts = array();
		foreach($lines as $line){
			preg_match(self::LAYOUT_REGEX,$line,$matches);
			$this->pageLayouts[trim($matches[1])] = trim($matches[2]);
		}
	}
}

?>
