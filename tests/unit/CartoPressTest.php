<?php

require_once(dirname(__FILE__) .'/../simpletest/autorun.php');
require_once(dirname(__FILE__) .'/../../CartoPress.php');

class CartoPressTest extends UnitTestCase {


    function testOSMTile() {
		$tile = new OsmTile(1,2,3);
		$this->assertNotNull($tile);
		
		
    }

}


?>