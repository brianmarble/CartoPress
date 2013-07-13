<?php 
class OsmTile{
	public $x;
	public $y;
	public $xOffset;
	public $yOffset;
	
	public function __construct($x,$y,$xOffset, $yOffset){
		$this->x = $x;
		$this->y = $y;
		$this->xOffset = $xOffset;
		$this->yOffset = $yOffset;
	}
	
	public static function createFromLonLatZoom($lon,$lat,$zoom){
		
		//code based on: http://wiki.openstreetmap.org/wiki/Slippy_map_tilenames
		$xpoint = (($lon + 180) / 360) * pow(2, $zoom);
		$ypoint = (1 - log(tan(deg2rad($lat)) + 1 / cos(deg2rad($lat))) / pi()) /2 * pow(2, $zoom);
		$xtile = floor($xpoint);
		$ytile = floor($ypoint);
		$xoffset = floor(($xpoint - $xtile) * 256);		
		$yoffset = floor(($ypoint - $ytile) * 256);		
		return new OsmTile($xtile,$ytile,$xoffset,$yoffset);
	}
}
?>