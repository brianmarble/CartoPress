<?php

class SVGLayer extends Layer{
	
	private $layer;
	private $pageLayout;
	private $bounds;
	
	public function __construct($layer,$pageLayout,$bounds){
       parent::__construct($layer);
       $this->layer = $layer;
       $this->pageLayout = $pageLayout;
       $this->bounds = $bounds;
	}
	
	protected function drawLayer($pdf){
		
		$cfg = Config::getInstance();
		$pageLayout = $this->pageLayout;
		$pdf->ImageSVG('@'.$this->layer->svg,$cfg->margin,$cfg->margin+$cfg->headerSize,$pageLayout->getMapWidth('in'),$pageLayout->getMapHeight('in'));

	}
}

?>