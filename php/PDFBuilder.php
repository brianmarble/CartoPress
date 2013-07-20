<?php

class PDFBuilder {
	
	private $pdf;
	
	public function __construct($spec){
		
		$this->validatePdfSpecs($spec);
		
		$pageLayout = new PageLayout($spec->layout);
		$bounds = $spec->bounds;
		$landscape = ($bounds->top - $bounds->bottom) < ($bounds->right - $bounds->left);
		
		
		
		$pdf = new MapPDF('P','in',$pageLayout->getTcpdfName());
		$margin = $pageLayout->getMargin();
		$pdf->SetMargins($margin, $margin, $margin, true);
		$pdf->setPageOrientation('P',true,$margin);
		$pdf->AddPage();
		$pdf->SetFontSize(24);
		$pdf->Cell(0,0,$spec->title,0,1,"C", 0, '', 0, false, 'T', 'M');
		
		$layers = $spec->layers;
		foreach($layers as $layer){
			$layer = $this->getLayerInstance($layer,$pageLayout,$bounds,$spec->zoom);
			$pdf->drawMapLayer($layer);
		}
		$pdf->SetY($pageLayout->getCommentsYPosition(),true);
		$pdf->SetFontSize(12);
		$pdf->Write(0,$spec->comments);
		
		$this->pdf = $pdf;
	}
	
	public function saveTo($filename){
		$dir = dirname($filename);
		if(!is_dir($dir))mkdir($dir,0777,true);
		$this->pdf->Output($filename,'F');
		return file_exists($filename);
	}
	
	private function getLayerInstance($layer,$pageLayout,$bounds,$zoom){
		if($layer->type == 'wms'){
			return new WMSLayer($layer,$pageLayout,$bounds);
		} else if ($layer->type == 'vector'){
			return new VectorLayer($layer,$pageLayout,$bounds);
		} else if ($layer->type == 'svg'){
			return new SVGLayer($layer,$pageLayout,$bounds);
		} else if ($layer->type == 'osm'){
			return new OSMLayer($layer,$pageLayout,$bounds,$zoom);
		} else {
			throw new CartoPressException("Layer type not found: $layer->type");
		}
	}
	
	private function validatePdfSpecs($spec){
		$pageLayout = new PageLayout($spec->layout);
		$layoutRatio = $pageLayout->getMapRatio();
		$bounds = $spec->bounds;
		$specWidth = $bounds->right - $bounds->left;
		$specHeight = $bounds->top - $bounds->bottom; 
		$specRatio = $specWidth / $specHeight;
		//var_dump(abs($specRatio - $layoutRatio));
		if(abs($specRatio - $layoutRatio) > .00001){
			var_dump($bounds,$specWidth,$specHeight,$specRatio,$layoutRatio);
			throw new CartoPressException("Invalid Bounds given in spec");
		}
		
		if(!isset($spec->title)){
			$spec->title = '';			
		}
		if(!isset($spec->comments)){
			$spec->comments = '';
		}
	}
}


?>
