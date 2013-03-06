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
		$pageLayout = $this->pageLayout;
		$pdf->Image($this->filename,1,1,$pageLayout->getMapWidth('in'),$pageLayout->getMapHeight('in'));
	}
}

?>
