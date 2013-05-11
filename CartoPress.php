<?php

/**
 * Main class, is created on every request and handles input and output
 * processing.
 *
 * GET  /                 retrieve information about this instance of cartopress
 * GET  /formats          list of formats
 * POST /pdf/{id}        create a pdf with the given id
 * GET  /pdf/{id}        retrieve the pdf with the given id
 */
class CartoPress {


	public function __construct($config=null,$pdfBuilder=null,$formatFactory=null){
		$this->config = $config ? $config : new Config();
		$this->pdfBuilder = $pdfBuilder;
		$this->formatFactory = isset($formatFactory) ? $formatFactory : new FormatFactory($this->config);
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
			return $this->getGetPdfResponse($uri);
		} else {
			return $this->get404Response();
		}
	}

	private function getFormatsListResponse(){
		$response = new Response();
		$response->body = json_encode($this->formatFactory->getFormatList());
		$response->headers[] = "Content-type: application/json";
		return $response;
	}

	private function getGetPdfResponse($uri){
		$filename = $this->config->getValue("pdfDir") . '/' . $uri->basename;
		$data = $this->getFileContents($filename);
		if($data){
			$response = new Response();
			$response->headers[] = "Content-type: application/pdf";
			$response->body = $data;
		} else {
			$response = $this->get404Response();
		}

		return $response;
	}

	private function get404Response(){
		$response = new Response();
		$response->headers[] = 'HTTP/1.0 404 Not Found';
		$response->body = "<h1>404 Page Not Found</h1><p>Sorry about that</p>";
		return $response;
	}

	private function getUri($server){
		$path_info = isset($server['PATH_INFO']) ? $server['PATH_INFO'] : '';
		$path = trim($path_info,' /');
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

		$pdfUri = $this->pdfBuilder->buildPdf($this->config,$spec);

		if($pdfUri){
			$response->headers[] = "HTTP/1.0 201 Created";
			$response->body = json_encode(array("success" => true, "pdfUri" => $pdfUri));
		} else {
			$response->body = json_encode(array(
				"success" => false,
				"message" => $this->pdfBuilder->getErrorMessage()
			));
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

	protected function getFileContents($path){
		if(file_exists($path)){
			return file_get_contents($path);
		}
		return false;
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
