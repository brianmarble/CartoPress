<?php

class WMSLayer extends Layer{
	
	public function __construct($spec){
		parent::__construct($spec);
		$imageDealer = new ImageDealer($spec->url);
	}

	protected function drawLayer(){}
}

?>
