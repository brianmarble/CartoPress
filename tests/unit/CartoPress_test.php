<?php

require_once(dirname(__FILE__) .'/../simpletest/autorun.php');
require_once(dirname(__FILE__) .'/../../autoLoad.php');


Mock::generate('Config');
Mock::generate('PdfBuilder');

class CartoPressTest extends UnitTestCase {

	private $cartoPress;
	private $mockConfig;

	function setUp(){
		$this->config = new MockConfig();
		$this->pdfBuilder = new MockPdfBuilder();
		$this->cartoPress = new TestableCartoPress($this->config,$this->pdfBuilder);
	}

    function testInfoRequest() {
       $response = $this->cartoPress->getResponse(array(
			'REQUEST_METHOD' => 'GET'
       ),"");
       $this->assertTrue(is_array($response->headers));
       $this->assertTrue(is_string($response->body));
       $json = json_decode($response->body);
       $this->assertTrue($json,"Response body should be json true (should be expanded in future)");
    }

    function testFormatListRequest(){
		$formats = array('hi','bob');
		$this->config->returns('getValue',$formats,array("pageSizes"));
		$response = $this->cartoPress->getResponse(array(
			"PATH_INFO" => "/formats",
			'REQUEST_METHOD' => 'GET'
		),"");
		$this->assertTrue(in_array('Content-type: application/json',$response->headers),"Content type should be set to json");
		$json = json_decode($response->body);
		$this->assertEqual($json,$formats,"Response body should be json");

    }

    function testCreatePdfRequestSuccessful(){
		$this->pdfBuilder->returns('buildPdf',"uri for pdf");
    	$response = $this->cartoPress->getResponse(array(
			"PATH_INFO" => "/pdf/1",
			'REQUEST_METHOD' => 'POST'
		),"map config");
		$this->assertTrue(in_array('HTTP/1.0 201 Created',$response->headers),"Response code should be 201");
		$this->assertTrue(in_array('Content-type: application/json',$response->headers),"Content type should be set to json");
		$json = json_decode($response->body);
		$this->assertTrue($json->success,"Success should be true");
		$this->assertEqual($json->pdfUri,"uri for pdf");
    }

    function testCreatePdfRequestFailed(){
		$this->pdfBuilder->returns('buildPdf',false);
		$this->pdfBuilder->returns('getErrorMessage',"error message");
		$this->pdfBuilder->expectOnce("buildPdf",array("*","map config"));
    	$response = $this->cartoPress->getResponse(array(
			"PATH_INFO" => "/pdf/1",
			'REQUEST_METHOD' => 'POST'
		),"map config");
		$this->assertFalse(in_array('HTTP/1.0 201 Created',$response->headers),"Response code should not be 201");
		$this->assertTrue(in_array('Content-type: application/json',$response->headers),"Content type should be set to json");
		$json = json_decode($response->body);
		$this->assertFalse($json->success,"Success should be false");
		$this->assertEqual($json->message,"error message");
    }

    function testRetrievePdfRequestSuccessful(){
		$this->cartoPress->fileContents = "File Content";
		$this->config->returns("getValue","path/to/pdfs",array("pdfDir"));
		$response = $this->cartoPress->getResponse(array(
			"PATH_INFO" => "/pdf/1",
			'REQUEST_METHOD' => 'GET'
		),'');
		$this->assertEqual($this->cartoPress->filePath,"path/to/pdfs/1","Should use configured directory and passed pdfname");
		$this->assertTrue(in_array('Content-type: application/pdf',$response->headers),"Content type should be set to pdf");
		$this->assertEqual($response->body,"File Content");
    }

    public function testRetrievePdfRequestFailed(){
		$cartoPress = new CartoPress($this->config);
		$this->config->returns("getValue","fake",array("pdfDir"));
		$response = $cartoPress->getResponse(array(
			"PATH_INFO" => "/pdf/fake",
			'REQUEST_METHOD' => 'GET'
		),'');
		$this->assertTrue(in_array('HTTP/1.0 404 Not Found',$response->headers),"Reponse code should be 404");
    }

    function testInvalidRequest() {
       $response = $this->cartoPress->getResponse(array(
			"PATH_INFO" => "/abc/",
			'REQUEST_METHOD' => 'GET'
       ),"");
		$this->assertTrue(in_array('HTTP/1.0 404 Not Found',$response->headers),"Reponse code should be 404");
    }
}

class TestableCartoPress extends CartoPress {

	public $fileContents;
	public $filePath;

	protected function getFileContents($path){
		$this->filePath = $path;
		return $this->fileContents;
	}

}
?>