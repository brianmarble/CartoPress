<?php

/**
 * Main class, is created on every request and handles input and output
 * processing.
 * 
 * GET  /                 retrieve information about this instance of cartopress
 * GET  /formats          list of formats
 * POST /pdfs/{id}        create a pdf with the given id
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
		} else if ($request[0] == 'pdfs' && count($request) == 2){
			$this->handlePdf($request);
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
	
	private function handlePdf($request){
		$method = $_SERVER['REQUEST_METHOD'];
		$filename = Config::getInstance()->pdfDir."/".$request[1];
		if($method == 'GET'){
			if(file_exists($filename)){
				header("Content-type: application/pdf");
				header("Content-disposition:attachment; filename=".basename($filename).".pdf");
				echo file_get_contents($filename);
			} else {
				http_response_code(404);
			}
		} else if ($method == 'POST'){
			if($_SERVER['CONTENT_TYPE'] == 'application/json'){
				$data = json_decode(file_get_contents('php://input'));
				
				$pdf = new PDFBuilder($data);
				if($pdf && $pdf->saveTo($filename)){
					http_response_code(201);
					header("Content-type: application/json");
					echo json_encode(array("url"=>$request[1]));
				} else {
					header("Content-type: application/json");
					echo json_encode(array("success"=>false));
				}
			} else {
				http_response_code(415);
			}
		} else {
			http_response_code(405);
			header("Allow: GET POST");
		}
	}
	
	private function getRestRequest(){
		$relavantURI = $_SERVER['PATH_INFO'];//str_replace($_SERVER['SCRIPT_NAME'],'',$_SERVER['REQUEST_URI']);
		$relavantURI = trim($relavantURI,' /');
		$requestData = explode('/',$relavantURI);
		return $requestData;
	}

}

?>
