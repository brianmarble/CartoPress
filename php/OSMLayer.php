<?php

class OSMLayer extends Layer {

	
	private $images;
	private $rowCount;
	private $colCount;
	/*
	 * @var OsmTile
	 */
	private $topLeft;
	/*
	 * @var OsmTile
	*/
	private $bottomRight;
	/*
	 * @var PageLayout
	*/
	private $pageLayout;
	
	/*
	 * @var OsmGridInfo
	 */
	private $grid;
	
	public function __construct($layer, PageLayout $pageLayout,$bounds,$zoom){
		
		parent::__construct($layer);
		$this->pageLayout = $pageLayout;
		$this->grid = $grid = $this->getGrid($layer,$zoom);
		

		$layer->params->FORMAT = 'image/png';
		$imageDealer = new ImageDealer($layer->url,$layer->params);
		
		$images = array();
		for($row = $grid->firstRowNumber; $row <= $grid->lastRowNumber; $row++){
			for($col = $grid->firstColNumber; $col <= $grid->lastColNumber; $col++){
				$images[] = $imageDealer->addImage(array(
					'${z}' => $zoom, 
					'${x}' => $col, 
					'${y}' => $row
				),true);
			}
		}
		$imageDealer->getImages();
		$this->images = $images;
		
	}
	
	private function getGrid($layer,&$zoom){
		$tagetPixelWidth = $this->pageLayout->getMapWidth('pixel');
		$targetTileWidth = floor(($tagetPixelWidth / 256)) + 1;
		
		$tempZoom = 0;		
		do {
			$tempZoom++;
			$grid = new OsmGridInfo($layer, $tempZoom);			
		} while ($grid->colCount < $targetTileWidth);
		$zoom = $tempZoom;
		return $grid;		
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