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
class CartoPress {
	
	
	public function __construct($config=null,$pdfBuilder=null){
		$this->config = $config ? $config : new Config();
		$this->pdfBuilder = $pdfBuilder;
		/*
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
		*/
	}
	
	public function handleRequest(){
		$response = $this->getResponse($_SERVER,file_get_contents("php://input"));
		foreach($response->headers as $header){
			header($header);
		}
		echo $response->body;
	}
	
	public function getResponse($server, $body){
		$uri = $this->getUri($server);
		$type = $this->getRequestType($server['REQUEST_METHOD'],$uri);
		if($type == RequestType::Information){
			return $this->getInfoResponse();
		} else if ($type == RequestType::Formats){
			return $this->getFormatsListResponse();
		} else if ($type == RequestType::CreatePdf){
			return $this->getCreatePdfResponse($body);
		} else if ($type == RequestType::GetPdf){
			throw new CartoPressException("Invalid Request");
		} else {
			throw new CartoPressException("Invalid Request");
		}
	}
	
	private function getFormatsListResponse(){
		$response = new Response();
		$response->body = json_encode($this->config->getFormats());
		$response->headers[] = "Content-type: application/json";
		return $response;
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
	
	private function getUri($server){
		$path = trim($server['PATH_INFO'],' /');
		$uri = new stdclass();
		$uri->basename = basename($path);
		$uri->dirname = dirname($path);
		return $uri;
	}
	
	private function getInfoResponse(){
		$response = new Response();
		$response->body = json_encode(true);
		return $response;
	}
	
	private function getCreatePdfResponse($spec){
		if(!isset($this->pdfBuilder))$this->pdfBuilder = new PdfBuilder();
	
		$response = new Response();
		
		$pdfUri = $this->pdfBuilder->buildPdf($spec);
		
		if($pdfUri){
			$response->headers[] = "HTTP/1.0 201 Created";
			$response->body = json_encode(array("success" => true, "pdfUri" => $pdfUri));
		} else {
			$response->body = json_encode(array("success" => false));
		}
		
		$response->headers[] = "Content-type: application/json";
		
		return $response;
	}
	
	private function getRequestType($method,$uri){
		$typedefs = array(
			array('GET','','', RequestType::Information),
			array('GET','.','formats', RequestType::Formats),
			array('GET','pdf',null,RequestType::GetPdf),
			array('POST','pdf',null,RequestType::CreatePdf)
		);
		foreach($typedefs as $type){
			if(	$method === $type[0]
				&& $uri->dirname === $type[1]
				&& ($uri->basename === $type[2] || $type[2] === null)
			){
				
				return $type[3];
			}
		}
		return null;
	}
	
	private function getFileContents($path){
		return file_get_contents($path);
	}

}

class RequestType {
	const Information = 1;
	const Formats = 2;
	const CreatePdf = 3;
	const GetPdf = 4;
	private function __construct(){}
}


?>
