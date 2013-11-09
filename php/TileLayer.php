<?php

class TileLayer extends Layer {

	protected $gird;
	
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