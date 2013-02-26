<?php

class PDFBuilder {
	
	private $pdf;
	
	public function __construct($spec){
		$pageLayout = new PageLayout($spec->layout);
		$bounds = $spec->bounds;
		$landscape = ($bounds->top - $bounds->bottom) < ($bounds->right - $bounds->left);
		
		
		
		$pdf = new MapPDF('P','mm',$pageLayout->getTcpdfName());
		//$pdf->AddPage();
		//$pdf->Cell(0,0,"Hello World!",0,1,"C", 0, '', 0, false, 'T', 'M');
		
		$layers = $spec->layers;
		foreach($layers as $layer){
			$layer = $this->getLayerInstance($layer,$pageLayout,$bounds);
			$pdf->drawMapLayer($layer);
		}
		
		$this->pdf = $pdf;
	}
	
	public function saveTo($filename){
		$dir = dirname($filename);
		if(!is_dir($dir))mkdir($dir,0777,true);
		$this->pdf->Output($filename,'F');
		return file_exists($filename);
	}
	
	private function getLayerInstance($layer,$pageLayout,$bounds){
		if($layer->type == 'wms'){
			return new WMSLayer($layer,$pageLayout,$bounds);
		} else if ($layer->type == 'vector'){
			return new VectorLayer($layer,$pageLayout,$bounds);
		} else {
			throw new CartoPressException("Layer type not found: $layer->type");
		}
	}
}


?>
