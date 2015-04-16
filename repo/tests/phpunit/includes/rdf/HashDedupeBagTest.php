<?php

namespace Wikibase\Test\Rdf;

use Wikibase\Rdf\HashDedupeBag;

/**
 * @covers Wikibase\Rdf\HashDedupeBagTest
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseRdf
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class HashDedupeBagTest extends \PHPUnit_Framework_TestCase {

	public function testAlreadySeen() {
		$bag = new HashDedupeBag( 2 );

		$this->assertFalse( $bag->alreadySeen( 'XYZ' ) );
		$this->assertTrue( $bag->alreadySeen( 'XYZ' ) );
		$this->assertFalse( $bag->alreadySeen( 'XAB' ) );
		$this->assertTrue( $bag->alreadySeen( 'XAB' ) );
	}


}
