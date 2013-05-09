<?php
require_once('simpletest/autorun.php');

class AllFileTests extends TestSuite {
    function __construct() {
        parent::__construct();
        $this->collect(dirname(__FILE__) . '/unit', new SimplePatternCollector('/_test.php/'));
    }
}
?>