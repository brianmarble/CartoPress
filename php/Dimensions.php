<?php


interface IDimensions {
	public function getTop();
	public function getBottom();
	public function getLeft();
	public function getRight();
}

class Dimensions {
	
	protected $top;
	protected $bottom;
	protected $left;
	protected $right;
	
	public function __construct($cfg){
		$this->left = $cfg->left;
		$this->right = $cfg->right;
		$this->top = $cfg->top;
		$this->bottom = $cfg->bottom;
	}
	
	public function getTop(){return $this->top;}
	public function getBottom(){return $this->bottom;}
	public function getLeft(){return $this->left;}
	public function getRight(){return $this->right;}
}