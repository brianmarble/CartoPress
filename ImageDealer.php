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
	
	private $counter = 1;
	private $unique;
	
	public function __construct($url,$commonParams){
		$this->url = $url;
		$this->dir = CartoPress::$config->imgDir;
		$this->requests = array();
		$this->commonParams = $commonParams;
		$this->unique = rand(0,999999);
		
		$cfg = CartoPress::$config; 
		$dir = $cfg->imgDir;
		if(!is_dir($dir))mkdir($dir,0777,true);
	}
	
	public function addImage($params){
		$url = $this->url . $this->getQueryString($params);
		$request = new Curl($url);
		$filename = $this->getFilename();
		$this->requests[$filename] = $request;
		return $filename;
	}
	
	public function getImages(){
		$masterRequest = new CurlParallel();
		foreach($this->requests as $filename => $request){
			$masterRequest->add($request);
		}
		$masterRequest->exec();
		foreach($this->requests as $filename => $request){
			$imageData = $request->fetch();
			file_put_contents($filename,$imageData);
		}
	}
	
	private function getQueryString($params){
		$flatParams = array();
		foreach($this->commonParams as $key => $value){
			$flatParams[] = "$key=$value";
		}
		foreach($params as $key => $value){
			$flatParams[] = "$key=$value";
		}
		return '?'.implode('&',$flatParams);
	}
	
	private function getFilename(){
		$cfg = CartoPress::$config; 
		return $cfg->imgDir . '/' . $this->unique . '_' . $this->counter++ . '.png';
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
