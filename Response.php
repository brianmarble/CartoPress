<?php
class Response {
	public $headers;
	public $body;
	public function __construct(){
		$this->headers = array();
		$this->body = '';
	}
}
?>