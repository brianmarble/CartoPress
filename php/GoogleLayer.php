<?php

class GoogleLayer extends Layer {

	private $filename;
	private $pageLayout;
	
	public function __construct($layer,$pageLayout,$bounds){
		parent::__construct($layer);
		$this->pageLayout = $pageLayout;
		
		
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
				
		// get the tile number of the top left
		$topLeftTileNumbers = getTileNumbersFromLonLat(
			$layer->lonLatBounds->left,
			$layer->lonLatBounds->top, 
			$layer->zoom
		);
		$topLeftTileX = floor($topLeftTileNumbers['x']);
		$topLeftTileY = floor($topLeftTileNumbers['y']);
		
		
		// TODO need to calculate offset
		
		// get the tile number of the bottom right
		$bottomRightTileNumbers = getTileNumbersFromLonLat(
			$layer->lonLatBounds->right,
			$layer->lonLatBounds->bottom, 
			$layer->zoom
		);
		$bottomRightTileX = ceil($bottomRightTileNumbers['x']);
		$bottomRightTileY = ceil($bottomRightTileNumbers['y']);
		
		
		$imageDealer = new ImageDealer("http://maps.googleapis.com/maps/api/staticmap",(object)array(
			'sensor' => 'false',
			'size' => '256x256',
			'maptype' => 'roadmap',
			'format' => 'roadmap'
		));
		
		$images = array();
		
		// for each tile
		
		for($y = $topLeftTileY; $y <= $bottomRightTileY; $y++){
			for($x = $topLeftTileX; $x <= $bottomRightTileX; $x++){
				$center = getLonLatFromTileNumbers($x + .5, $y + .5, $layer->zoom);
				$images[] = $imageDealer->addImage(array(
					'center' => $center['lat'].','.$center['lon'],
					'zoom' => $layer->zoom
				));
				
			}
		}
		
		$this->images = $images;
		$imageDealer->getImages();
		$this->grid = (object)array(
			"colCount" => $bottomRightTileX - $topLeftTileX + 1,
			"rowCount" => $bottomRightTileY - $topLeftTileY + 1,
			"width" => ($bottomRightTileX - $topLeftTileX + 1) * 256,
			"height" => ($bottomRightTileY - $topLeftTileY + 1) * 256,
			"leftOffset" => 0,
			"topOffset" => 0,
		);
		
		return;
		die('happy');
		
			// get the center
			
			// add the tile
		
		// from http://wiki.openstreetmap.org/wiki/Mercator
		function lon2x($lon) { return deg2rad($lon) * 6378137.0; }
		function lat2y($lat) { return log(tan(M_PI_4 + deg2rad($lat) / 2.0)) * 6378137.0; }
		function x2lon($x) { return rad2deg($x / 6378137.0); }
		function y2lat($y) { return rad2deg(2.0 * atan(exp($y / 6378137.0)) - M_PI_2); }

		


		
		
		$llb = $layer->lonLatBounds;
		
		$tile = new stdClass(); // single image
		$grid = new stdClass(); // grid of tiles may be bigger than printed map
		$map = new stdClass(); // final print map
		
		$printedMapWidthInPixels = $this->pageLayout->getMapWidth('pixel');
		$printedMapWidthInLonDeg = $llb->right - $llb->left;
		$printedMapLonDegPerPx =  $printedMapWidthInPixels / $printedMapWidthInLonDeg;
		
		$googleTileWidthInPixels = 640;
		$googleTilesInRow = ceil($printedMapWidthInPixels / $googleTileWidthInPixels);
		
		$printedMapHeigthInPixels = $this->pageLayout->getMapHeight('pixel');
		$printedMapHeigthInLatDeg = $llb->top - $llb->bottom;
		$printedMapLatDegPerPx = $printedMapHeigthInPixels / $printedMapHeigthInLatDeg;
		
		$googleTileHeightInPixels = 640;
		$googleTilesInRow = ceil($printedMapHeigthInPixels / $googleTileHeightInPixels);
		
		
		
		// how many tiles wide and what size of tile
		$map->widthPx = $this->pageLayout->getMapWidth('pixel');
		$map->widthLon = $llb->right - $llb->left;
		$lonsPerPx = $map->widthLon / $map->widthPx;
		$tile->widthPx = $map->widthPx;
		$grid->cols = 1;
		while($tile->widthPx > 700){
			$grid->cols++;
			$tile->widthPx = ceil($map->widthPx / $grid->cols / 2) * 2; // needs to be a multiple of two
		}
		$tile->widthLon = $tile->widthPx * $lonsPerPx;
		
		// how many tiles high and what size of tile
		$mapPixelHeight = $this->pageLayout->getMapHeight('pixel');
		$tileHeight = $mapPixelHeight;
		$tilesInColCount = 1;
		while($tileHeight > 700){
			$tilesInColCount++;
			$tileHeight = ceil($mapPixelHeight / $tilesInColCount / 2) * 2;// needs to be a multiple of two
		}
		$lonLatMapHeight = $llb->top - $llb->bottom;
		$lonLatTileHeight = $tileHeight * $lonLatMapHeight / $mapPixelHeight;
		
		//var_dump(
		//	$mapPixelWidth,
		//	$tileWidth,
		//	$tilesInRowCount,
		//	$mapPixelHeight,
		//	$tileHeight,
		//	$tilesInColCount
		//);
		
		$currentCenterX = $firstCenterX = $llb->left + ($tile->widthLon /2);
		$currentCenterY = $firstCenterY = $llb->top - ($lonLatTileHeight /2);
		
		//var_dump(
		//$lonLatMapWidth,
		//$lonLatTileWidth,
		//$lonLatMapHeight,
		//$lonLatTileHeight,
		//$currentCenterX,
		//$currentCenterY
		//);
		$images = array();
		
		for($row =  0; $row < $tilesInColCount; $row++){
			for($col =  0; $col < $grid->cols; $col++){
			
				$images[] = $imageDealer->addImage(array(
					'size' => ($tile->widthPx/2).'x'.($tileHeight/2),
					'center' => $currentCenterY.','.$currentCenterX,
					'zoom' => $layer->zoom,
					'maptype' => $layer->maptype
				));
				$currentCenterX += $tile->widthLon;
			}
			$currentCenterX = $firstCenterX;
			$currentCenterY -= $lonLatTileHeight;
		}
		$this->images = $images;
		$imageDealer->getImages();
		$this->grid = (object)array(
			"colCount" => $grid->cols,
			"rowCount" => $tilesInColCount,
			"width" => $map->widthPx,
			"height" => $mapPixelHeight,
			"leftOffset" => 0,
			"topOffset" => 0,
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
		
		/* @var Imagick */
		$im2 = $im->appendimages(true);
				
		$im2->cropimage($grid->width, $grid->height, $grid->leftOffset, $grid->topOffset);

		$filename = "/tmp/tmp.png"; //ImageDealer::getFilename();
		
		$im2->writeimage($filename);
		return $filename;
	}
}


?>
