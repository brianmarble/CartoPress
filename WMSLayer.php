<?php

class WMSLayer extends Layer{
	
	public function __construct($layer,$pageLayout,$bounds){
		parent::__construct($layer);
		$imageDealer = new ImageDealer($layer->url,$layer->params);
		$imageDealer->addImage(array(
			'WIDTH' => $pageLayout->getMapWidth('pixel'),
			'HEIGHT' => $pageLayout->getMapWidth('pixel'),
			'BBOX' => "$bounds->left,$bounds->bottom,$bounds->right,$bounds->top"
		));
		var_dump($bounds,array(
			'WIDTH' => $pageLayout->getMapWidth('pixel'),
			'HEIGHT' => $pageLayout->getMapWidth('pixel'),
			'BBOX' => "$bounds->left,$bounds->bottom,$bounds->right,$bounds->top"
		));
		die();
	}

	protected function drawLayer($pdf){
		
	}
}

?>
