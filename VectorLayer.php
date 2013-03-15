<?php

class VectorLayer extends Layer{
	
	private $currentStyle;
	
	public function __construct($layer,$pageLayout,$bounds){
		parent::__construct($layer);
		$this->layer = $layer;
		$this->pageLayout = $pageLayout;
		$this->bounds = $bounds;
		Coord::setPageInfo($pageLayout,$bounds);
		$this->features = array();
		foreach(json_decode($layer->features)->features as $featureData){
			$this->features[] = Feature::getFeature($featureData);
		}
	}

	protected function drawLayer($pdf){
		foreach($this->features as $feature){
			$feature->draw($pdf);
		}
	}
}

abstract class Feature {
	
	public $style;
	
	public static function getFeature($featureData){
		$type = $featureData->geometry->type;
		if($type == "Polygon"){
			return new Polygon($featureData);
		} else if ($type == "Point"){
			return new Point($featureData);
		} else if ($type == "LineString"){
			return new Line($featureData);
		} else {
			throw new CartoPressException("Unsuported Geometry Type: $type");
		}
	}

	public function __construct($featureData){
		$this->style = $featureData->properties->style;
	}

	abstract public function getCenter();

	abstract public function drawGeometry($pdf);

	public function draw($pdf){
		$this->setStyle($pdf);
		$this->drawGeometry($pdf);
		$this->drawLabel($pdf);
	}

	public function setStyle($pdf){
		//var_dump($this->style);
		$s = $this->style;
		$cfg = Config::getInstance();
		
		$lineStyle = array();
		if(isset($s->strokeWidth)){
			$lineStyle['width'] = $s->strokeWidth / $cfg->ppi;
		}
		if(isset($s->strokeColor)){
			$lineStyle['color'] = self::getRGBfromHex($s->strokeColor);
		}
		$pdf->SetLineStyle($lineStyle);
	}

	public function drawLabel($pdf){
		$s = $this->style;
		$cfg = Config::getInstance();
		if(!empty($s->label)){
			
			$p = $this->getCenter();
			if(isset($s->labelXOffset)){
				$p->x += $s->labelXOffset / $cfg->ppi;
			}
			if(isset($s->labelYOffset)){
				$p->y += $s->labelYOffset / $cfg->ppi;
			}
			if(isset($s->labelAlign)){
				$a = $s->labelAlign;
			} else {
				$a = 'cm';
			}
			
			$width = $pdf->GetStringWidth($s->label);
			$height = $pdf->getFontSize();//$pdf->getStringHeight(0,$s->label);
			if($a[0] == 'l'){
				// nothing to do
			} else if ($a[0] == 'c'){
				$p->x  -= $width/2;
			} else if ($a[0] == 'r'){
				$p->x  -= $width;
			}
			if ($a[1] == 't'){
				//nothing to do
			} else if ($a[1] == 'm'){
				$p->y -= $height/2;
			} else if ($a[1] == 'b'){
				$p->y -= $height;
			}
			
			$pdf->Text($p->x,$p->y,$s->label);
		}
		
	}
	
	public static function getRGBfromHex($hex){
		$red = substr($hex,1,2);
		$green = substr($hex,3,2);
		$blue = substr($hex,5,2);
		$red = intval($red,16);
		$green = intval($green,16);
		$blue = intval($blue,16);
		return array($red,$green,$blue);
	}
}

class Coord {

	private static $widthRatio;
	
	private static $heightRatio;
	
	private static $bounds;
	
	private static $pageLayout;
	
	public $x;
	
	public $y;
	
	public function __construct($xy,$transform=false){
		$this->x = $xy[0];
		$this->y = $xy[1];
		if($transform){
			$this->transformToPageCoord();
		}
	}
	
	public static function setPageInfo($pageLayout,$bounds){
		$mWidth = $pageLayout->getMapWidth();
		$mHeight = $pageLayout->getMapHeight();
		$bWidth = $bounds->right - $bounds->left;
		$bHeight = $bounds->top - $bounds->bottom;
		self::$widthRatio = $mWidth / $bWidth;
		self::$heightRatio = $mHeight / $bHeight;
		self::$bounds = $bounds;
		self::$pageLayout =  $pageLayout;
	}
	
	private function transformToPageCoord(){
		
		$cfg = Config::getInstance();
		
		// coordinats relative to bounds (origin is lower left)
		$this->x -= self::$bounds->left;
		$this->y -= self::$bounds->bottom;
		
		// coordinates in inches (relative to bounds) (origin is lower left)
		$this->x *= self::$widthRatio;
		$this->y *= self::$heightRatio;
		
		// cordinates with top left origin (relative to bounds) (in inches)
		$this->y = self::$pageLayout->getMapHeight() - $this->y;
		
		// coordinates relative to page (in inches) (origin is top left)
		$this->x += $cfg->margin + $cfg->headerSize;
		$this->y += $cfg->margin;
		
	}
}

class Point extends Feature{

	public $coord;

	public function __construct($featureData){
		parent::__construct($featureData);
		$this->coord = new Coord($featureData->geometry->coordinates,true);
	}
	
	public function getCenter(){
		return $this->coord;
	}
	
	public function drawGeometry($pdf){
		$radius = $this->style->pointRadius / 72;
		if($radius > 0){
			$center = $this->getCenter();
			$pdf->Circle($center->x,$center->y,$radius);
		}
	}
}

class Line extends Feature{
	
	private $coords;
	
	public function __construct($featureData){
		parent::__construct($featureData);
		$this->coords = array();
		foreach($featureData->geometry->coordinates as $coord){
			$this->coords[] = new Coord($coord,true);
		}
	}
	
	public function getCenter(){
		return new Coord(array(0,0));
	}
	
	public function drawGeometry($pdf){
		$lineIndex = 0;
		$coordCount = count($this->coords);
		$pdf->setAlpha($this->style->strokeOpacity);
		for($lineIndex = 0; $lineIndex < $coordCount -1; $lineIndex++){
			$start = $this->coords[$lineIndex];
			$end = $this->coords[$lineIndex+1];
			$pdf->Line($start->x,$start->y,$end->x,$end->y);
		}
		$pdf->setAlpha(1);
	}
	
}

class Polygon extends Feature{

	public function __construct($featureData){
		parent::__construct($featureData);
		$this->coords = array();
		foreach($featureData->geometry->coordinates[0] as $coord){
			$this->coords[] = new Coord($coord,true);
		}
	}

	public function getCenter(){
		return new Coord(array(0,0));
	/*
	private function getPolygonCentroid($coords){
		$x = 0;
		$y = 1;
		$sumX = 0.0;
		$sumY = 0.0;
		$coordCount = count($coords);
		for($i = 0; $i < $coordCount; $i++){
			$b = $coords[$i];
			$c = $coords[$i+1];
			$sumX += ($b[$x] + $c[$x]) * ($b[$x] * $c[$y] - $c[$x] * $b[$y]);
			$sumY += ($b[$y] + $c[$y]) * ($b[$x] * $c[$y] - $c[$x] * $b[$y]);
		}
		$area = $this->getArea($coords);
		
		for($coords as $coord){
			
		}
        if (this.components && (this.components.length > 2)) {
            var sumX = 0.0;
            var sumY = 0.0;
            for (var i = 0; i < this.components.length - 1; i++) {
                var b = this.components[i];
                var c = this.components[i+1];
                sumX += (b.x + c.x) * (b.x * c.y - c.x * b.y);
                sumY += (b.y + c.y) * (b.x * c.y - c.x * b.y);
            }
            var area = -1 * this.getArea();
            var x = sumX / (6 * area);
            var y = sumY / (6 * area);
            return new OpenLayers.Geometry.Point(x, y);
        } else {
            return null;
        }
	}*/	
	}
	
	public function drawGeometry($pdf){
		$p = array();
		foreach($this->coords as $coord){
			$p[] = $coord->x;
			$p[] = $coord->y; 
		}
		$s = $this->style;
		if($s->fill && $s->fillOpacity > 0){
			$color = self::getRGBfromHex($this->style->fillColor);
			//$pdf->Polygon($p,'f','',$color);
			//var_dump($color);
			$pdf->setAlpha($s->fillOpacity);
			$pdf->Polygon($p,'F',null,$color);
			$pdf->setAlpha(1);
		}
		
		$pdf->setAlpha($s->strokeOpacity);
		$pdf->Polygon($p);
		$pdf->setAlpha(1);
	}
	
}
?>
