<?php

namespace Wikimedia\Purtle\Tests;

use Wikimedia\Purtle\BNodeLabeler;

/**
 * @covers Wikimedia\Purtle\BNodeLabeler
 *
 * @group Purtle
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class BNodeLabelerTest extends \PHPUnit_Framework_TestCase {

	public function testGetLabel() {
		$labeler = new BNodeLabeler( 'test', 2 );

		$this->assertEquals( 'test2', $labeler->getLabel() );
		$this->assertEquals( 'test3', $labeler->getLabel() );
		$this->assertEquals( 'foo', $labeler->getLabel( 'foo' ) );
		$this->assertEquals( 'test4', $labeler->getLabel() );
	}

}
