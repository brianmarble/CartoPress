<?php

class PDFBuilder {
	
	private $pdf;
	
	public function __construct($spec){
		
		$bounds = $spec->bounds;
		$landscape = ($bounds->top - $bounds->bottom) < ($bounds->right - $bounds->left);
		
		$pdf = new MapPDF($landscape ? 'L' : 'P','mm',$spec->pageSize);
		$pdf->AddPage();
		$pdf->Cell(0,0,"Hello World!",0,1,"C", 0, '', 0, false, 'T', 'M');
	}
	
	public function __saveTo($filename){
		
	}
}


?>
