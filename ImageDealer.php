<?php


/**
 * Uses Curl class to access images from remote servers.
 * 
 * Stores the images in temparay files and return
 */
class ImageDealer {


	private $url;
	private $dir;
	private $requests;
	private $filenames;
	
	private static $counter = 1;
	private $unique;
	
	public function __construct($url,$dir){
		$this->url = $url;
		$this->dir = $dir;
		$this->requests = array();
		$this->unique = microtime(true) . '_'  . getmypid();
	}
	
	public function addImage($params){
		$url = $this->url . $this->getQueryString($params);
		$request = new Curl($url);
		$this->requests[] = $request;
	}
	
	public function getImages(){
		$masterRequest = new CurlParallel();
		foreach($this->requests as $request){
			$masterRequest->add($request);
		}
		$masterRequest->exec();
		foreach($this->requests as $request){
			var_dump( $this);
			var_dump($_SERVER);
			var_dump($request);
			var_dump($request->info());
			var_dump($request->fetch());
			//file_put_contents($filename,$imageData);
		}
		//return array_keys($this->requests);
	}
	
	private function getQueryString($params){
		$flatParams = array();
		foreach($params as $key => $value){
			$flatParams[] = "$key=$value";
		}
		return '?'.implode('&',$flatParams);
	}
	
	private function getFilename(){
		$time = microtime(true);
		var_dump($time);
	}
}

if(ini_get('display_errors') && $_SERVER['SCRIPT_FILENAME'] == __FILE__){
	include 'Curl.php';
	$id = new ImageDealer('http://mt.10000maps.com/dev/ms','/tmp/');
	$id->addImage(array(
		'LAYERS' => 'BaseLayers_SimpleBase2',
		'ISBASELAYER' => 'true',
		'TRANSPARENT' => 'false',
		'FORMAT' => 'image/png',
		'MAP' => 'landmaps/Base_Layers.map',
		'SERVICE' => 'WMS',
		'VERSION' => '1.1.1',
		'REQUEST' => 'GetMap',
		'STYLES' => '',
		'SRS' => 'EPSG:900913',
		'BBOX' => '-626172.1364,5009377.084,-0.0008000002708286,5635549.2196',
		'WIDTH' => '256',
		'HEIGHT' => '256'
	));
	$id->getImages();
}

?>
