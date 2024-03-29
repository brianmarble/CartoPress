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
		
		$cfg = Config::getInstance(); 
		
		$this->securityCheck($cfg,$url);
		
		$this->url = $url;
		$this->dir = $cfg->imgDir;
		$this->requests = array();
		$this->commonParams = $commonParams;
		$this->unique = rand(0,999999);
		
		$dir = $cfg->imgDir;
		if(!is_dir($dir))mkdir($dir,0777,true);
	}
	
	public function addImage($params,$areUrlParams=false){
		$url = is_array($this->url) ? $this->url[0] : $this->url;
		if($areUrlParams){
			$url = $this->insertParamsIntoUrl($url,$params);
		} else {
			$url .= $this->getQueryString($params);
		}
		$request = new Curl($url);
		$filename = self::getFilename();
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
	
	public static function getFilename(){
		$cfg = Config::getInstance();
		return sprintf("%s/%s.png",$cfg->imgDir,uniqid());
	}
	
	private function insertParamsIntoUrl($url,$params){
		foreach(array_keys($params) as $param){
			$url = str_replace($param, $params[$param], $url);
		}
		return $url;
	}
	
	private function securityCheck($cfg,$urls){
		if(!is_array($urls)){
			$urls = array($urls);
		}
		foreach($urls as $url){
			$urlArray = parse_url($url);
			if(!array_search($urlArray['host'],$cfg->hosts,true)){
				throw new CartoPressException("Forbidden Host");	
			}
		}
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
