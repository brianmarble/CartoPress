<?php

class WMSLayer extends Layer{
	
	private $filename;
	private $pageLayout;
	
	public function __construct($layer,$pageLayout,$bounds){
		parent::__construct($layer);
		$this->pageLayout = $pageLayout;
		$layer->params->FORMAT = 'image/png';
		$imageDealer = new ImageDealer($layer->url,$layer->params);
		$this->filename = $imageDealer->addImage(array(
			'WIDTH' => $pageLayout->getMapWidth('pixel'),
			'HEIGHT' => $pageLayout->getMapHeight('pixel'),
			'BBOX' => "$bounds->left,$bounds->bottom,$bounds->right,$bounds->top"
		));
		$imageDealer->getImages();
	}

	protected function drawLayer($pdf){
		$cfg = Config::getInstance();
		$pageLayout = $this->pageLayout;
		$level = error_reporting();
		error_reporting(0);
		$pdf->Image($this->filename,$cfg->margin,$cfg->margin+$cfg->headerSize,$pageLayout->getMapWidth('in'),$pageLayout->getMapHeight('in'));
		error_reporting($level);
	}
}

?>
