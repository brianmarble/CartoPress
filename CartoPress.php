<?php





/**
 * Main class, is created on every request and handles input and output
 * processing.
 * 
 * 
 * Action paramater
 * The url must contain the paramater 'a' with one of the following values
 *   - 'info' Request Capabilities specification json data
 *   - 'print' Request The creation and delivery of a pdf map
 *   - 'create' Request The creation of a pdf map and a link to access it
 */
class CartoPress {
	
	const ACTION_INFO = 'info';
	const ACTION_PRINT = 'print';
	const ACTION_CREATE = 'create';
	
	public static $config;
	
	public function __construct(){
		try{
			
			$config = new Config();
			
			if(!array_key_exists('a',$_GET))throw new CartoPressException("Action Paramater 'a' was not set in url.");
			$action = $_GET['a'];
			
			if($action == self::ACTION_INFO){
				$this->outputInfoJson($config);
			}
			
			if($action == self::ACTION_PRINT || $action == self::ACTION_CREATE){
				$spec = json_decode(file_get_contents("php://input"));
				if(empty($spec))throw new CartoPressException("No print specification data sent!");
				$pdf = new MapPdf($config,$spec);
			}
			
			if($action == self::ACTION_PRINT){
				$pdf->output();
			}
			
			if($action == self::ACTION_CREATE){
				$filename = $pdf->saveTmp();
				echo json_encode(array("url"=>$filename));
			}

		} catch (CartoPressException $e){
			$this->outputError($e);
		}
	}
	
	private function outputInfoJson(){
		header("Content-type: application/json");
		echo json_encode(self::config);
	}
	
	private function outputError($e){
		if(ini_get("display_errors")){
			if(!headers_sent()){
				header("Content-type: text/plain");
			}
			echo "CartoPress Error: ";
			echo $e->getMessage();
			echo "\n";
			echo $e->getTraceAsString();
		}
	}

}


/**
 * Allows automatic class loading by including {classname}.php
 * when a new class is encountered
 */
function __autoload($class){
	$filename = "$class.php";
	if(file_exists($filename)){
		include_once $filename;
	} else {
		throw new CartoPressException("Unable to load class: $class");
	}
	
}
class CartoPressException extends Exception {}


new CartoPress();
?>
