<?php
class OsmGridInfo {
	public $tileSize;
	public $firstColNumber;
	public $lastColNumber;
	public $firstRowNumber;
	public $lastRowNumber;
	public $colCount;
	public $rowCount;
	public $topOffset;
	public $bottomOffset;
	public $leftOffset;
	public $rightOffset;
	public $width;
	public $height;
	public function __construct($layer, $zoom) {
		$llb = $layer->lonLatBounds;
		
		$topLeft = OsmTile::createFromLonLatZoom ( $llb->left, $llb->top, $zoom );
		$bottomRight = OsmTile::createFromLonLatZoom ( $llb->right, $llb->bottom, $zoom );
		
		$this->tileSize = 256;
		$this->firstColNumber = $topLeft->x;
		$this->lastColNumber = $bottomRight->x;
		$this->firstRowNumber = $topLeft->y;
		$this->lastRowNumber = $bottomRight->y;
		$this->colCount = $this->lastColNumber - $this->firstColNumber + 1;
		$this->rowCount = $this->lastRowNumber - $this->firstRowNumber + 1;
		$this->topOffset = $topLeft->yOffset;
		$this->bottomOffset = $this->tileSize - $bottomRight->yOffset;
		$this->leftOffset = $topLeft->xOffset;
		$this->rightOffset = $this->tileSize - $bottomRight->xOffset;
		$this->width = $this->tileSize * $this->colCount - $this->leftOffset - $this->rightOffset;
		$this->height = $this->tileSize * $this->rowCount - $this->topOffset - $this->bottomOffset;
	}
}

?>