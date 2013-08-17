<?php
class MapPDF extends CustomTCPDF {
	public function __construct($orientation, $unit, $layout) {
		parent::__construct ( $orientation, $unit, $layout );
	}
	public function Header() {
	}
	public function Footer() {
	}
	public function drawMapLayer(Layer $layer) {
		$layer->draw ( $this );
	}
	public static function getPageSize($format) {
		return @parent::getPageSizeFromFormat ( $format );
	}
}

?>
