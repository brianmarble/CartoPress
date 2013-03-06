<?php

ini_set("html_errors",false);
/**
 * Main class, is created on every request and handles input and output
 * processing.
 * 
 * GET  /                 retrieve information about this instance of cartopress
 * GET  /formats          list of formats
 * POST /pdfs/{id}        create a pdf with the given id
 * GET  /pdfs/{id}        retrieve the pdf with the given id
 */
class CartoPress {
	
	public function __construct(){

		$request = $this->getRestRequest();

		if(count($request) == 1 && empty($request[0])){
			$this->outputInfo();
		} else if ($request[0] == 'formats' && count($request) == 1 ){
			$this->outputFormatList();
		} else if ($request[0] == 'pdfs' && count($request) == 2){
			$this->handlePdf($request);
		} else {
			if(false)header("HTTP/1.0 404 Not Found");
			else {
				header("Content-type: text/plain");
				var_dump($_SERVER,$request);
			}
		}
		die();
		
	}
	
	private function outputFormatList(){
		$cfg = Config::getInstance();
		$data = array();
		foreach($cfg->pageLayouts as $displayName => $tcpdfName){
			$layout = new PageLayout($displayName);
			$data[] = array(
				'name' => $layout->getDisplayName(),
				'ratio' => $layout->getMapRatio()
			);
		}
		
		header("Content-type: application/json");
		echo json_encode($data);
		
	}
	
	private function handlePdf($request){
		$method = $_SERVER['REQUEST_METHOD'];
		$filename = Config::getInstance()->pdfDir."/".$request[1];
		if($method == 'GET'){
			if(file_exists($filename)){
				header("Content-type: application/pdf");
				echo file_get_contents($filename);
			} else {
				header("HTTP/1.0 404 Not Found");
			}
		} else if ($method == 'POST'){
			if($_SERVER['CONTENT_TYPE'] == 'application/json'){
				$data = json_decode(file_get_contents('php://input'));
				
				$pdf = new PDFBuilder($data);
				if($pdf && $pdf->saveTo($filename)){
					header("HTTP/1.0 201 Created");
					header("Content-type: application/json");
					echo json_encode(array("url"=>$request[1]));
				} else {
					header("HTTP/1.0 200 Created");
					header("Content-type: application/json");
					echo json_encode(array("success"=>false));
				}
			} else {
				header("HTTP/1.0 415 Unsupported Media Type");
			}
		} else {
			header("HTTP/1.0 405 Method Not Allowed");
			header("Allow: GET POST");
		}
	}
	
	private function getRestRequest(){
		$relavantURI = str_replace($_SERVER['SCRIPT_NAME'],'',$_SERVER['REQUEST_URI']);
		$relavantURI = trim($relavantURI,' /');
		$requestData = explode('/',$relavantURI);
		return $requestData;
	}

}

/**
 * Allows automatic class loading by including {classname}.php
 * when a new class is encountered
 */
function __autoload($class){
	$filenames = array(
		"$class.php",
		"tcpdf/".strtolower($class).".php"
		
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

new CartoPress();



?>
