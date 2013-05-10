<?php

require_once(dirname(__FILE__) .'/../simpletest/autorun.php');
require_once(dirname(__FILE__) .'/../../autoLoad.php');

class ConfigTest extends UnitTestCase {
	
	
	function setUp(){
		TestableConfig::$fileContents = "
			#ignore this
			prop = value
			number = 100#ignore this

			[hosts]
			host1
			host2 #ignore this

			[pageSizes]
			d1	n1
			d2	n2
		";
		$this->config = new TestableConfig();
	
	}

	function testGetValue(){
		$this->assertEqual($this->config->getValue("prop"),"value");
		$this->assertEqual($this->config->getValue("number"),100);
	}
	
	function testHosts(){
		$hosts = $this->config->getValue("hosts");
		$this->assertEqual(count($hosts),2);
		$this->assertEqual($hosts[0],"host1");
		$this->assertEqual($hosts[1],"host2");
	}
	
	function testFormats(){
		$pageSizes = $this->config->getValue("pageSizes");
		$this->assertEqual(count($pageSizes),2);
	}
	
	function testUnset(){
		$this->assertEqual($this->config->getValue("doesnotexist"),null);
	}
}


class TestableConfig extends Config{
	
	public static $fileContents;
	public $filePath;
	
	protected function getFileContents($path){
		$this->filePath = $path;
		return self::$fileContents;
	}
}
?>