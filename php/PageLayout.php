<?php


class PageLayout {

	private $displayName;
	private $tcpdfName;
	private $pageWidthInch;
	private $pageHeightInch;
	private $mapWidthInch;
	private $mapHeightInch;
	private $dpi;

	public function __construct($displayName,$orientation){
		$cfg = Config::getInstance();
		$this->displayName = $displayName;
		$this->tcpdfName = $cfg->pageLayouts[$displayName];
		$pageSizeInPoints = MapPDF::getPageSize($this->tcpdfName);
		$pointsPerInch = 72;
		
		$this->pageWidthInch = $pageSizeInPoints[0] / $pointsPerInch;
		$this->pageHeightInch = $pageSizeInPoints[1] / $pointsPerInch;
		
		if($orientation == 'landscape'){
			$swap = $this->pageHeightInch;
			$this->pageHeightInch = $this->pageWidthInch;
			$this->pageWidthInch = $swap;
		} else if ($orientation != 'portait'){
			throw new CartoPressException("Invalid page orientation: $orientation");
		}
		
		$widthMargins = $cfg->margin * 2;
		$heightMargins = $widthMargins + $cfg->headerSize + $cfg->footerSize;
		$this->mapWidthInch = $this->pageWidthInch - $widthMargins;
		$this->mapHeightInch = $this->pageHeightInch - $heightMargins;
		$this->dpi = $cfg->dpi;
	}
	public function getCommentsYPosition(){
		$cfg = Config::getInstance();
		return $cfg->margin + $cfg->headerSize + $this->getMapHeight();
	}
	
	public function getMargin(){
		$cfg = Config::getInstance();
		return $cfg->margin;
	}	
	
	public function getPageWidth($unit='in'){
		if($unit == 'in'){
			return $this->pageWidthInch;
		} else {
			throw new CartoPressException("Unit $unit not supported!");
		}
	}
	
	public function getPageHeight($unit='in'){
		if($unit == 'in'){
			return $this->pageHeightInch;
		} else {
			throw new CartoPressException("Unit $unit not supported!");
		}
	}
	
	public function getMapWidth($unit='in'){
		if($unit == 'in'){
			return $this->mapWidthInch;
		} else if($unit == 'pixel'){
			return $this->mapWidthInch * $this->dpi;
		} else {
			throw new CartoPressException("Unit $unit not supported!");
		}
	}
	
	public function getMapHeight($unit='in'){
		if($unit == 'in'){
			return $this->mapHeightInch;
		} else if($unit == 'pixel'){
			return $this->mapHeightInch * $this->dpi;
		} else {
			throw new CartoPressException("Unit $unit not supported!");
		}
	}
	
	public function getDisplayName(){
		return $this->displayName;
	}
	
	public function getTcpdfName(){
		return $this->tcpdfName;
	}
	
	public function getMapRatio(){
		return $this->mapWidthInch / $this->mapHeightInch;
	}
}

?>
