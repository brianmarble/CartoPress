<?php


class CustomTCPDF extends TCPDF {

	/**
	 * @override
	 */
	protected function _putcatalog() {
	
		/**************************************************
		 * Same code as tcpdf. Edits marked in the middle *
		 **************************************************/
	
		// put XMP
		$xmpobj = $this->_putXMP();
		// if required, add standard sRGB_IEC61966-2.1 blackscaled ICC colour profile
		if ($this->pdfa_mode OR $this->force_srgb) {
			$iccobj = $this->_newobj();
			$icc = file_get_contents(dirname(__FILE__).'/sRGB.icc');
			$filter = '';
			if ($this->compress) {
				$filter = ' /Filter /FlateDecode';
				$icc = gzcompress($icc);
			}
			$icc = $this->_getrawstream($icc);
			$this->_out('<</N 3 '.$filter.'/Length '.strlen($icc).'>> stream'."\n".$icc."\n".'endstream'."\n".'endobj');
		}
		// start catalog
		$oid = $this->_newobj();
		$out = '<< /Type /Catalog';
		$out .= ' /Version /'.$this->PDFVersion;
		//$out .= ' /Extensions <<>>';
		$out .= ' /Pages 1 0 R';
		//$out .= ' /PageLabels ' //...;
		$out .= ' /Names <<';
		if ((!$this->pdfa_mode) AND !empty($this->n_js)) {
			$out .= ' /JavaScript '.$this->n_js;
		}
		if (!empty($this->efnames)) {
			$out .= ' /EmbeddedFiles <</Names [';
			foreach ($this->efnames AS $fn => $fref) {
				$out .= ' '.$this->_datastring($fn).' '.$fref;
			}
			$out .= ' ]>>';
		}
		$out .= ' >>';
		if (!empty($this->dests)) {
			$out .= ' /Dests '.($this->n_dests).' 0 R';
		}
		$out .= $this->_putviewerpreferences();
		if (isset($this->LayoutMode) AND (!$this->empty_string($this->LayoutMode))) {
			$out .= ' /PageLayout /'.$this->LayoutMode;
		}
		if (isset($this->PageMode) AND (!$this->empty_string($this->PageMode))) {
			$out .= ' /PageMode /'.$this->PageMode;
		}
		if (count($this->outlines) > 0) {
			$out .= ' /Outlines '.$this->OutlineRoot.' 0 R';
			$out .= ' /PageMode /UseOutlines';
		}
		//$out .= ' /Threads []';
		if ($this->ZoomMode == 'fullpage') {
			$out .= ' /OpenAction ['.$this->page_obj_id[1].' 0 R /Fit]';
		} elseif ($this->ZoomMode == 'fullwidth') {
			$out .= ' /OpenAction ['.$this->page_obj_id[1].' 0 R /FitH null]';
		} elseif ($this->ZoomMode == 'real') {
			$out .= ' /OpenAction ['.$this->page_obj_id[1].' 0 R /XYZ null null 1]';
		} elseif (!is_string($this->ZoomMode)) {
			$out .= sprintf(' /OpenAction ['.$this->page_obj_id[1].' 0 R /XYZ null null %F]', ($this->ZoomMode / 100));
		}
		//$out .= ' /AA <<>>';
		//$out .= ' /URI <<>>';
		$out .= ' /Metadata '.$xmpobj.' 0 R';
		//$out .= ' /StructTreeRoot <<>>';
		//$out .= ' /MarkInfo <<>>';
		if (isset($this->l['a_meta_language'])) {
			$out .= ' /Lang '.$this->_textstring($this->l['a_meta_language'], $oid);
		}
		//$out .= ' /SpiderInfo <<>>';
		// set OutputIntent to sRGB IEC61966-2.1 if required
		if ($this->pdfa_mode OR $this->force_srgb) {
			$out .= ' /OutputIntents [<<';
			$out .= ' /Type /OutputIntent';
			$out .= ' /S /GTS_PDFA1';
			$out .= ' /OutputCondition '.$this->_textstring('sRGB IEC61966-2.1', $oid);
			$out .= ' /OutputConditionIdentifier '.$this->_textstring('sRGB IEC61966-2.1', $oid);
			$out .= ' /RegistryName '.$this->_textstring('http://www.color.org', $oid);
			$out .= ' /Info '.$this->_textstring('sRGB IEC61966-2.1', $oid);
			$out .= ' /DestOutputProfile '.$iccobj.' 0 R';
			$out .= ' >>]';
		}
		//$out .= ' /PieceInfo <<>>';
		
		/*************************
		 * Start of custom edits *
		 *************************/
		
		if (!empty($this->pdflayers)) {
			$lyrobjs = '';
			foreach ($this->pdflayers as $layer) {
				$lyrobjs .= ' '.$layer['objid'].' 0 R';
			}
			$out .= ' /OCProperties << /OCGs ['.$lyrobjs.']';
			$out .= ' /D <<';
			$out .= ' /Order ['.$lyrobjs.']';
			$out .= ' /ListMode /AllPages';
			$out .= ' >>';
			$out .= ' >>';
		}
		
		/*************************
		 * End of custom edits *
		 *************************/
		
		// AcroForm
		if (!empty($this->form_obj_id) OR ($this->sign AND isset($this->signature_data['cert_type']))) {
			$out .= ' /AcroForm <<';
			$objrefs = '';
			if ($this->sign AND isset($this->signature_data['cert_type'])) {
				// set reference for signature object
				$objrefs .= $this->sig_obj_id.' 0 R';
			}
			if (!empty($this->empty_signature_appearance)) {
				foreach ($this->empty_signature_appearance as $esa) {
					// set reference for empty signature objects
					$objrefs .= ' '.$esa['objid'].' 0 R';
				}
			}
			if (!empty($this->form_obj_id)) {
				foreach($this->form_obj_id as $objid) {
					$objrefs .= ' '.$objid.' 0 R';
				}
			}
			$out .= ' /Fields ['.$objrefs.']';
			// It's better to turn off this value and set the appearance stream for each annotation (/AP) to avoid conflicts with signature fields.
			$out .= ' /NeedAppearances false';
			if ($this->sign AND isset($this->signature_data['cert_type'])) {
				if ($this->signature_data['cert_type'] > 0) {
					$out .= ' /SigFlags 3';
				} else {
					$out .= ' /SigFlags 1';
				}
			}
			//$out .= ' /CO ';
			if (isset($this->annotation_fonts) AND !empty($this->annotation_fonts)) {
				$out .= ' /DR <<';
				$out .= ' /Font <<';
				foreach ($this->annotation_fonts as $fontkey => $fontid) {
					$out .= ' /F'.$fontid.' '.$this->font_obj_ids[$fontkey].' 0 R';
				}
				$out .= ' >> >>';
			}
			$font = $this->getFontBuffer('helvetica');
			$out .= ' /DA (/F'.$font['i'].' 0 Tf 0 g)';
			$out .= ' /Q '.(($this->rtl)?'2':'0');
			//$out .= ' /XFA ';
			$out .= ' >>';
			// signatures
			if ($this->sign AND isset($this->signature_data['cert_type'])) {
				if ($this->signature_data['cert_type'] > 0) {
					$out .= ' /Perms << /DocMDP '.($this->sig_obj_id + 1).' 0 R >>';
				} else {
					$out .= ' /Perms << /UR3 '.($this->sig_obj_id + 1).' 0 R >>';
				}
			}
		}
		//$out .= ' /Legal <<>>';
		//$out .= ' /Requirements []';
		//$out .= ' /Collection <<>>';
		//$out .= ' /NeedsRendering true';
		$out .= ' >>';
		$out .= "\n".'endobj';
		$this->_out($out);
		return $oid;
	}	

	public function objclone($object) {
		/**
		 * The clone keyword this throws a fatal error
		 * on our system when trying to clone an Imagick object. I don't know why. 
		 * Using the depricated clone method of the Imagick class solves the problem. I don't like
		 * it because it is deprecated, but it works.
		 */
		if($object instanceof Imagick){
			return $object->clone();
		} else {
			return @clone($object);
		}
	}
}

?>
