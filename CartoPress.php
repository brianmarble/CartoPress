<?php

ini_set("html_errors",false);
/**
 * Main class, is created on every request and handles input and output
 * processing.
 * 
 * GET  http:// ... /                 retrieve information about this instance of cartopress
 * GET  http:// ... /formats          list of formats
 * POST http:// ... /pdfs/{id}        create a pdf with the given id
 * GET  http:// ... /pdfs/{id}        retrieve the pdf with the given id
 */
class CartoPress {
	
	public static $config;
	
	public function __construct(){

		self::$config = new Config();

		$request = $this->getRestRequest();

		if(count($request) == 1 && empty($request[0])){
			$this->outputInfo();
		} else if ($request[0] == 'formats' && count($request) == 1 ){
			$this->outputFormatList();
		} else if ($request[0] == 'pdfs' && count($request) == 2){
			$this->handlePdf($request);
		} else {
			if(false)header("HTTP/1.0 404 Not Found");
			else var_dump($_SERVER,$request);
		}
		die();
		
	}
	
	private function outputFormatList(){
		$accept = $_SERVER['HTTP_ACCEPT'];
		if(strstr($accept,'application/json') != -1 || strstr($accept,'application/*') != -1 || strstr($accept,'*/*') != -1){
			$margins = self::$config->margin * 2;
			
			$data = array();
			foreach(self::$config->pageSizes as $layout){
				$size = MapPDF::getPageSizeFromFormat($layout->name);
				var_dump($size[0]/72,$size[1]/72);
				die();
				$data[] = array(
					'name' => $layout->name,
					'ratio' => ($layout->width - $margins) / ($layout->height - $margins)
				);
			}
			
			header("Content-type: application/json");
			echo json_encode($data);		
		} else {
			header("HTTP/1.0 406 Not Acceptable");
		}
		
	}
	
	private function handlePdf($request){
		$method = $_SERVER['REQUEST_METHOD'];
		$filename = self::$config->pdfDir."/".$request[1];
		if($method == 'GET'){
			if(file_exists($filename)){
				header("Content-type: application/x-pdf");
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
					echo json_encode(array("id"=>$request[1]));
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
