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
	
	public function __construct($layer, PageLayout $pageLayout,$bounds,$zoom){
		parent::__construct($layer);
		$llb = $layer->lonLatBounds;
		
		$topLeft = OsmTile::createFromLonLatZoom($llb->left,$llb->top,$zoom);
		$bottomRight = OsmTile::createFromLonLatZoom($llb->right,$llb->bottom,$zoom);
		
		$rowCount = $bottomRight->y - $topLeft->y + 1;
		$colCount = $bottomRight->x - $topLeft->x + 1;
		

		$this->pageLayout = $pageLayout;
		$layer->params->FORMAT = 'image/png';
		$imageDealer = new ImageDealer($layer->url,$layer->params);
		
		$images = array();
		
		for($row = $topLeft->y; $row <= $bottomRight->y; $row++){
			for($col = $topLeft->x; $col <= $bottomRight->x; $col++){
				$images[] = $imageDealer->addImage(array(
					'${z}' => $zoom, 
					'${x}' => $col, 
					'${y}' => $row
				),true);
			}
		}
		$imageDealer->getImages();
		$this->rowCount = $rowCount;
		$this->colCount = $colCount;
		$this->images = $images;
		$this->bottomRight = $bottomRight;
		$this->topLeft = $topLeft;
	}

	function drawLayer($pdf){
		
		$im = new Imagick();
		$nextImage = 0;
						
		for($row = 0; $row < $this->rowCount; $row++){
			$imRow = new Imagick(); 
			for($col = 0; $col < $this->colCount; $col++)
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
				
		$imageWidth = 256 * $this->colCount - $this->topLeft->xOffset - (256 - $this->bottomRight->xOffset);
		$imageHeight = 256 * $this->rowCount - $this->topLeft->yOffset - (256 - $this->bottomRight->yOffset);
		var_dump($imageWidth, $imageHeight, $this->topLeft->xOffset, $this->topLeft->yOffset);
		$im2->cropimage($imageWidth, $imageHeight, $this->topLeft->xOffset, $this->topLeft->yOffset);

		$filename = ImageDealer::getFilename();
		
		$im2->writeimage("/tmp/tmp.png");
		
		$cfg = Config::getInstance();
		$pageLayout = $this->pageLayout;
		$pdf->Image("/tmp/tmp.png",$cfg->margin,$cfg->margin+$cfg->headerSize,$pageLayout->getMapWidth('in'),$pageLayout->getMapHeight('in'));
		
	}
	
	function combineTiles(){
		$im = new Imagick();
		$nextImage = 0;
		
		for($row = 0; $row < $this->rowCount; $row++){
			$imRow = new Imagick();
			for($col = 0; $col < $this->colCount; $col++)
			{
			$imRow->readimage($this->images[$nextImage++]);
			}
			$imRow->resetiterator();
			$imRow = $imRow->appendImages(false);
			$im->addimage($imRow);
		}
		$im->resetiterator();
		$im = $im->appendimages(true);
		$im->writeimage("/tmp/tmp.png");
	}
}

?>