<?php

class GoogleLayer extends Layer {

	private $filename;
	private $pageLayout;

	public function __construct($layer,$pageLayout,$bounds){
		parent::__construct($layer);
		$this->pageLayout = $pageLayout;


		$tileSize = 256;

		$mapPixelWidth = $this->pageLayout->getMapWidth('pixel');

		$targetMapTileWidth = floor($mapPixelWidth / $tileSize / 2) + 1;


		$zoom = -1;
		do {
			$zoom++;
			$zoomTileWidth = $this->getMapTileWidth($layer->lonLatBounds,$zoom);
		}while($zoomTileWidth < $targetMapTileWidth);


		// get the tile number of the top left
		$topLeftTileNumbers = $this->getTileNumbersFromLonLat(
			$layer->lonLatBounds->left,
			$layer->lonLatBounds->top,
			$zoom
		);
		$topLeftTileX = floor($topLeftTileNumbers['x']);
		$topLeftTileY = floor($topLeftTileNumbers['y']);

		$leftOffset = round(($topLeftTileNumbers['x'] - floor($topLeftTileNumbers['x'])) * $tileSize);
		$topOffset = round(($topLeftTileNumbers['y'] - floor($topLeftTileNumbers['y'])) * $tileSize);


		// get the tile number of the bottom right
		$bottomRightTileNumbers = $this->getTileNumbersFromLonLat(
			$layer->lonLatBounds->right,
			$layer->lonLatBounds->bottom,
			$zoom
		);
		$bottomRightTileX = floor($bottomRightTileNumbers['x']);
		$bottomRightTileY = floor($bottomRightTileNumbers['y']);

		$rightOffset = $tileSize - round(($bottomRightTileNumbers['x'] - floor($bottomRightTileNumbers['x'])) * $tileSize);
		$bottomOffset = $tileSize - round(($bottomRightTileNumbers['y'] - floor($bottomRightTileNumbers['y'])) * $tileSize);

		$imageDealer = new ImageDealer("http://maps.googleapis.com/maps/api/staticmap",(object)array(
			'sensor' => 'false',
			'size' => $tileSize.'x'.$tileSize,
			'maptype' => 'roadmap',
			'format' => 'roadmap',
			'zoom' => $zoom
		));

		$images = array();

		// for each tile

		for($y = $topLeftTileY; $y <= $bottomRightTileY; $y++){
			for($x = $topLeftTileX; $x <= $bottomRightTileX; $x++){
				$center = $this->getLonLatFromTileNumbers($x + .5, $y + .5, $zoom);
				$images[] = $imageDealer->addImage(array(
					'center' => $center['lat'].','.$center['lon']
				));
			}
		}

		$this->images = $images;
		$imageDealer->getImages();
		$this->grid = (object)array(
			"colCount" => $bottomRightTileX - $topLeftTileX + 1,
			"rowCount" => $bottomRightTileY - $topLeftTileY + 1,
			"width" => ($bottomRightTileX - $topLeftTileX + 1) * $tileSize - $leftOffset - $rightOffset,
			"height" => ($bottomRightTileY - $topLeftTileY + 1) * $tileSize - $topOffset - $bottomOffset,
			"leftOffset" => $leftOffset,
			"topOffset" => $topOffset,
		);
	}

	function drawLayer($pdf){

		$filename = $this->combineTiles();

		$cfg = Config::getInstance();
		$pageLayout = $this->pageLayout;
		$pdf->Image($filename,$cfg->margin,$cfg->margin+$cfg->headerSize,$pageLayout->getMapWidth('in'),$pageLayout->getMapHeight('in'));

	}

	function combineTiles(){
		$grid = $this->grid;

		$im = new Imagick();
		$nextImage = 0;

		for($row = 0; $row < $grid->rowCount; $row++){
			$imRow = new Imagick();
			for($col = 0; $col < $grid->colCount; $col++)
			{
				$imRow->readimage($this->images[$nextImage++]);
			}
			$imRow->resetiterator();
			$imRow = $imRow->appendImages(false);
			$im->addimage($imRow);
		}
		$im->resetiterator();

		$im2 = $im->appendimages(true);

		$im2->cropimage($grid->width, $grid->height, $grid->leftOffset, $grid->topOffset);

		//$filename = "/tmp/tmp.png"; //ImageDealer::getFilename();
		$filename = ImageDealer::getFilename();

		$im2->writeimage($filename);
		return $filename;
	}

	function getTileNumbersFromLonLat($lon, $lat, $zoom){
		// based on http://wiki.openstreetmap.org/wiki/Slippy_map_tilenames
		$n = pow(2,$zoom);
		$latRad = deg2rad($lat);
		return array(
			'x' => $n * (($lon + 180) / 360),
			'y' => $n * (1 - (log(tan($latRad) + (1 / cos($latRad))) / pi())) / 2
		);
	}

	function getLonLatFromTileNumbers($x,$y,$zoom){
		// based on http://wiki.openstreetmap.org/wiki/Slippy_map_tilenames
		$n = pow(2,$zoom);
		$lonDeg = $x / $n *  360.0 - 180.0;
		$latRad = atan(sinh( pi() * (1 - 2 * $y / $n)));
		$latDeg = $latRad * 180.0 / pi();
		return array(
			'lon' => $lonDeg,
			'lat' => $latDeg
		);
	}

	function getMapTileWidth($bounds,$zoom){
		$left = floor($this->getTileNumbersFromLonLat($bounds->left,$bounds->top,$zoom)['x']);
		$right = floor($this->getTileNumbersFromLonLat($bounds->right,$bounds->top,$zoom)['x']);
		return $right - $left + 1;
	}
}


?>
