<?php

/**
 * Main class, is created on every request and handles input and output
 * processing.
 * 
 * GET  /                 retrieve information about this instance of cartopress
 * GET  /formats          list of formats
 * POST /pdfs/create      create a pdf
 * GET  /pdfs/{id}        retrieve the pdf with the given id
 */
 
 include_once "Shim.php";
 
class CartoPress {
	
	public function __construct(){
		set_time_limit(60*5);
		$request = $this->getRestRequest();

		if(count($request) == 1 && empty($request[0])){
			$this->outputInfo();
		} else if ($request[0] == 'formats' && count($request) == 1 ){
			$this->outputFormatList();
		} else if ($request[0] == 'pdfs' && $request[1] == "create" && count($request) == 2){
			$this->createPdf($request);
		} else if ($request[0] == 'pdfs' && count($request) == 2){
			$this->returnPdf($request);
		} else {
			if(false)http_response_code(404);
			else {
				header("Content-type: text/plain");
				var_dump($_SERVER,$request);
			}
		}
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
	
	private function createPdf($request){
		$method = $_SERVER['REQUEST_METHOD'];
		$pdfId = uniqid('CartoPress_',true);
		$filename = Config::getInstance()->pdfDir."/".$pdfId;
		if($method != 'POST'){
			http_response_code(405);
			header("Allow: POST");
			die();
		}
		if($_SERVER['CONTENT_TYPE'] != 'application/json'){
			http_response_code(415);
			die();
		}
		
		$data = json_decode(file_get_contents('php://input'));
		$pdf = new PDFBuilder($data);
		if($pdf && $pdf->saveTo($filename)){
			http_response_code(201);
			header("Content-type: application/json");
			echo json_encode(array("url"=>$pdfId));
		} else {
			header("Content-type: application/json");
			echo json_encode(array("success"=>false));
		}
	}
	
	private function returnPdf($request){
		$method = $_SERVER['REQUEST_METHOD'];
		$filename = Config::getInstance()->pdfDir."/".$request[1];
		if($method != 'GET'){
			http_response_code(405);
			header("Allow: GET");
			die();
		}
		if(file_exists($filename)){
			header("Content-type: application/pdf");
			header("Content-disposition:attachment; filename=".basename($filename).".pdf");
			echo file_get_contents($filename);
		} else {
			http_response_code(404);
		}
	}
	
	private function getRestRequest(){
		$relavantURI = $_SERVER['PATH_INFO'];
		$relavantURI = trim($relavantURI,' /');
		$requestData = explode('/',$relavantURI);
		return $requestData;
	}

}

?>
