<?php
class PDFBuilder {
	private $pdf;
	public function __construct($spec) {
		$this->validatePdfSpecs ( $spec );
		
		$pageLayout = new PageLayout ( $spec->layout, $spec->orientation );
		$bounds = $spec->bounds->proj;
		$orienation = $spec->orientation == 'portrait' ? 'P' : 'L';
		
		$pdf = new MapPDF ( $orienation, 'in', $pageLayout->getTcpdfName () );
		$margin = $pageLayout->getMargin ();
		$pdf->SetMargins ( $margin, $margin, $margin, true );
		$pdf->setPageOrientation ( $orienation, true, $margin );
		$pdf->AddPage ();
		$pdf->SetFontSize ( 24 );
		$pdf->Cell ( 0, 0, $spec->title, 0, 1, "C", 0, '', 0, false, 'T', 'M' );
		
		$layers = $spec->layers;
		foreach ( $layers as $layer ) {
			$layer = $this->getLayerInstance ( $layer, $pageLayout, $bounds, $spec->zoom );
			$pdf->drawMapLayer ( $layer );
		}
		$pdf->SetY ( $pageLayout->getCommentsYPosition (), true );
		$pdf->SetFontSize ( 12 );
		$pdf->Write ( 0, $spec->comments );
		
		$this->drawScale($pdf, $pageLayout, new Bounds($spec->bounds->lonlat),$spec->units);
		$this->drawcompass($pdf, $pageLayout);
		$this->drawBorder($pdf, $pageLayout);
		$this->drawLogo ( $pdf, $pageLayout );
		
		
		$this->pdf = $pdf;
	}
	public function drawLogo($pdf, $pageLayout) {
		$cfg = Config::getInstance ();
		$offsetX = $pageLayout->getPageWidth () - $cfg->margin - $cfg->logoWidth;
		$offsetY = $pageLayout->getPageHeight () - $cfg->margin - $cfg->logoHeight;
		$logoPath = realpath ( $cfg->logo );
		if (! $logoPath) {
			$logoPath = realpath ( __DIR__ . '/../' . $cfg->logo );
		}
		$pdf->Image ( $logoPath, $offsetX, $offsetY, $cfg->logoWidth, $cfg->logoHeight );
	}
	public function saveTo($filename) {
		$dir = dirname ( $filename );
		if (! is_dir ( $dir ))
			mkdir ( $dir, 0777, true );
		$this->pdf->Output ( $filename, 'F' );
		return file_exists ( $filename );
	}
	private function getLayerInstance($layer, $pageLayout, $bounds, $zoom) {
		if ($layer->type == 'wms') {
			return new WMSLayer ( $layer, $pageLayout, $bounds );
		} else if ($layer->type == 'vector') {
			return new VectorLayer ( $layer, $pageLayout, $bounds );
		} else if ($layer->type == 'svg') {
			return new SVGLayer ( $layer, $pageLayout, $bounds );
		} else if ($layer->type == 'osm') {
			return new OSMLayer ( $layer, $pageLayout, $bounds, $zoom );
		} else {
			throw new CartoPressException ( "Layer type not found: $layer->type" );
		}
	}
	private function validatePdfSpecs($spec) {
		$pageLayout = new PageLayout ( $spec->layout, $spec->orientation );
		$layoutRatio = $pageLayout->getMapRatio ();
		$bounds = $spec->bounds->proj;
		$specWidth = $bounds->right - $bounds->left;
		$specHeight = $bounds->top - $bounds->bottom;
		$specRatio = $specWidth / $specHeight;
		if (abs ( $specRatio - $layoutRatio ) > .00001) {
			throw new CartoPressException ( "Invalid Bounds given in spec" );
		}
		
		if (! isset ( $spec->title )) {
			$spec->title = '';
		}
		if (! isset ( $spec->comments )) {
			$spec->comments = '';
		}
	}

	private function drawcompass($pdf, $pageLayout){
		$cfg = Config::getInstance();
		$offsetX = $pageLayout->getPageWidth() - $cfg->margin - $cfg->compassWidth;
		$offsetY = $cfg->margin + $cfg->headerSize;
		$compassPath = realpath ( $cfg->compass );
		if (! $compassPath) {
			$compassPath = realpath ( __DIR__ . '/../' . $cfg->compass );
		}
		$pdf->Image ( $compassPath, $offsetX, $offsetY, $cfg->compassWidth, $cfg->compassHeight );	
	}

	private function drawBorder($pdf, $pageLayout){
		$cfg = Config::getInstance();
		
		$left = $cfg->margin;
		$top = $cfg->margin+$cfg->headerSize;
		$right = $left + $pageLayout->getMapWidth('in');
		$bottom = $top + $pageLayout->getMapHeight('in');

		$pdf->Line($left,$top,$right,$top);
		$pdf->Line($right,$top,$right,$bottom);
		$pdf->Line($left,$bottom,$right,$bottom);
		$pdf->Line($left,$top,$left,$bottom);
	}
	
	public static function drawScale(MapPDF $pdf, IPageLayout $pageLayout, $bounds, $units){
		$cfg = Config::getInstance();
		$isMetric = $units == "metric";

		/* @var $dim Dimensions */
		$dim = $pageLayout->mapDimensions();
				
		$scaleXOffset = .25;
		$scaleYoffset = .25;
		$textOffset = .05;
		
		$mapMiles = $bounds->getWidth();
		$mapInches = $pageLayout->getMapWidth();
		$scaleMaxInches = $cfg->scaleLength;
		$scaleMaxMiles = $scaleMaxInches * $mapMiles / $mapInches;

		$scaleDistance = $scaleMaxMiles * ($isMetric ? 1.60934 : 1);
		$scaleDistance = self::reduceToOneSignificantDigit($scaleDistance).($isMetric ? "km" : "mi");
		$scaleMiles = $isMetric ? $scaleDistance * 0.621371 : $scaleDistance;
		$scaleInches = $scaleMiles * $mapInches / $mapMiles;
				
		$lineHeight = $dim->getBottom() - $scaleYoffset;
		$lineStart = $dim->getRight() - ($scaleInches + $scaleXOffset);
		$lineEnd = $dim->getRight() - $scaleXOffset;

		$pdf->Line($lineStart,$lineHeight,$lineEnd,$lineHeight);
		self::drawTick($pdf,$lineStart,$lineHeight);		
		self::drawTick($pdf,$lineEnd,$lineHeight);		
		self::drawCenteredCell($pdf,'0',$lineStart,$lineHeight + $textOffset);
		self::drawCenteredCell($pdf,$scaleDistance,$lineEnd,$lineHeight + $textOffset);	
		
	}
	
	public static function drawCenteredCell($pdf,$text,$x,$y){
		$textWidth = $pdf->GetStringWidth($text,'');
		$textHeight = $pdf->getStringHeight($text,'');
		$pdf->setXY($x-($textWidth/2)-.05,$y);
		$pdf->Cell($textWidth,$textHeight,$text);
	}
	
	public static function drawTick($pdf,$x,$y){
		$tickSize = .04;
		$pdf->Line($x,$y-$tickSize,$x,$y+$tickSize);
	}

	public static function reduceToOneSignificantDigit($num){
		$powTen = 0;
		while($num >= 10){
			$powTen++;
			$num /= 10;
		}
		$num = floor($num)*pow(10,$powTen);
		return $num;
	}
}

?>
