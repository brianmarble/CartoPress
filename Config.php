<?php


/**
 * Parses the CartoPress configuration file and is used as a container 
 * for the configuration data.
 */
class Config {

	const COMMENT_REGEX = '~#[\\s\\S]*$~';
	const SECTION_REGEX = '~[[]([a-zA-Z0-9_]+)[]]~';
	const KEY_VALUE_REGEX = '~^([^=]+)=([^=]*)$~';
	const LAYOUT_REGEX = '~(.*)(?:\\t|  )\s*(\d+)\s+(\d+)~';
	const DEFAULT_FILENAME = "Config.cfg";
	
	/**
	 * @param filename Name of the configuration file to open. If no 
	 * filename is provided the default file "Config.cfg" will be used.
	 */
	public function __construct($filename=null){
		if(!$filename)$filename = self::DEFAULT_FILENAME;
		$configFileLines = explode("\n",file_get_contents("Config.cfg"));
		$currentSection = 'initial';
		foreach($configFileLines as $line){
			$strippedLine = trim(preg_replace(self::COMMENT_REGEX,'',$line));
			if(!empty($strippedLine)){
				if(preg_match(self::SECTION_REGEX,$strippedLine,$matches)){
					$this->extractSection($currentSection,$lines);
					$currentSection = $matches[1];
					$lines = array();
				} else {
					$lines[] = $strippedLine;
				}
			}
			
		}
		$this->extractSection($currentSection,$lines);
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
			$key = $matches[1];
			$value = is_numeric($matches[2]) ? $matches[2] + 0 : $matches[2];
			$this->$key = $value;
		}
	}
	
	private function extract_hosts($lines){
		$this->hosts = $lines;
	}
	
	private function extract_layouts($lines){
		$this->layouts = array();
		foreach($lines as $line){
			preg_match(self::LAYOUT_REGEX,$line,$matches);
			$layout = new stdClass();
			$layout->name = trim($matches[1]);
			$layout->width = $matches[2]+0;
			$layout->height = $matches[3]+0;
			$this->layouts[] = $layout;
		}
	}
}

?>
