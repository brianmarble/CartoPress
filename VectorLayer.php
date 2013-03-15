<?php

class VectorLayer extends Layer{
	
	private $currentStyle;
	
	public function __construct($layer,$pageLayout,$bounds){
		parent::__construct($layer);
		$this->layer = $layer;
		$this->pageLayout = $pageLayout;
		$this->bounds = $bounds;
		$this->features = json_decode($layer->features);
		
		$mWidth = $pageLayout->getMapWidth();
		$mHeight = $pageLayout->getMapHeight();
		$bWidth = $bounds->right - $bounds->left;
		$bHeight = $bounds->top - $bounds->bottom;
		$this->widthRatio = $mWidth / $bWidth;
		$this->heightRatio = $mHeight / $bHeight;
		//var_dump($this->widthRatio,$this->heightRatio);
		//die();
	}

	protected function drawLayer($pdf){
		foreach($this->features->features as $feature){
			$this->prepareStyle($feature->properties->style,$pdf);
			$this->drawGeometry($feature->geometry,$pdf);
		}
	}
	
	private function prepareStyle($style,$pdf){
		$this->currentStyle = $style;
	}
	
	private function drawGeometry($geometry,$pdf){
		$type = $geometry->type;
		if($type == "Polygon"){
			$this->drawPolygon($geometry->coordinates,$pdf);
		} else if ($type == "Point"){
			$this->drawPoint($geometry->coordinates,$pdf);
		} else if ($type == "LineString"){
			$this->drawLineString($geometry->coordinates,$pdf);
		} else {
			throw new CartoPressException("Unsuported Geometry Type: $type");
		}
	}
	
	private function drawPolygon($coords,$pdf){
		$p = array();
		foreach($coords[0] as $coord){
			$c = $this->transformToPageCoord($coord);
			$p[] = $c[0];
			$p[] = $c[1]; 
		}
		$pdf->Polygon($p);
	}
	
	private function drawPoint($coords,$pdf){
		$radius = $this->currentStyle->pointRadius / 72;
		//tmp
		$style6 = array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => '10,10', 'color' => array(0, 128, 0));
		//tmp end
		if($radius > 0){
			$center = $this->transformToPageCoord($coords);
			//$pdf->Circle($center[0],$center[1],$radius,0,360,'DF',$style6);
			$pdf->Circle($center[0],$center[1],$radius);
		}
	}
	
	private function drawLineString($coords,$pdf){
		$lineIndex = 0;
		$coordCount = count($coords);
		for($lineIndex = 0; $lineIndex < $coordCount -1; $lineIndex++){
			$start = $coords[$lineIndex];
			$end = $coords[$lineIndex+1];
			$this->drawLine($start,$end,$pdf);
		}
	}
	
	private function drawLine($start,$end,$pdf){
		$start = $this->transformToPageCoord($start);
		$end = $this->transformToPageCoord($end);
		$pdf->Line($start[0],$start[1],$end[0],$end[1]);
	}

	private function transformToPageCoord($coord){
		$bounds = $this->bounds;
		// coordinats relative to bounds (origin is lower left)
		$c = array(
			$coord[0] - $bounds->left,
			$coord[1] - $bounds->bottom
		);
		
		// coordinates in inches (relative to bounds) (origin is lower left)
		$c = array(
			$c[0] * $this->widthRatio,
			$c[1] * $this->heightRatio
		);
		
		// cordinates with top left origin (relative to bounds) (in inches)
		$c = array(
			$c[0],
			$this->pageLayout->getMapHeight() - $c[1]
		);
		
		// coordinates relative to page (in inches) (origin is top left)
		return array(
			$c[0] + 1,
			$c[1] + 1
		);
	}
	
	private function transformToPageSize($size){
		return $size * $this->widthRatio;
	}
}

?>
