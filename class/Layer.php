<?php


/**
 * Base Layer Class
 * 
 * Prepares generic properties
 * Creates a pdf layer then calls drawLayer allowing subclasses to draw layer contents then closes
 * the pdf layer
 */
class Layer {
	
	private $name;
	
	public function __construct($specs){
		$this->name = $specs->name;
	}
	
	public function draw($pdf){
		$pdf->startLayer($this->name);
		$this->drawLayer($pdf);
		$pdf->endLayer();
	}
	
	protected function drawLayer($pdf){
	
	}
}

?>
