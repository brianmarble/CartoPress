<?php


class Bounds extends Dimensions{

	public function getWidth($units="miles"){
		$mid = ($this->top - $this->bottom)/2 + $this->bottom;
		return GeoMath::LonLatDistance($this->left, $mid, $this->right, $mid,'mile');
	}
	
}

?>
