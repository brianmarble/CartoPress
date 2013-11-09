<?php

class GoogleLayer extends Layer {

	private $filename;
	private $pageLayout;
	
	public function __construct($layer,$pageLayout,$bounds){
		parent::__construct($layer);
		$this->pageLayout = $pageLayout;

		$imageDealer = new ImageDealer("http://maps.googleapis.com/maps/api/staticmap",(object)array(
			'sensor' => 'false'
		));
		
		$llb = $layer->lonLatBounds;
		
		$tile = new stdClass(); // single image
		$grid = new stdClass(); // grid of tiles may be bigger than printed map
		$map = new stdClass(); // final print map
		
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
