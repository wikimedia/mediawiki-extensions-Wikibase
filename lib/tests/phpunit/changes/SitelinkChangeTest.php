<?php

namespace Wikibase\Test;
use Wikibase\Item as Item;
use Wikibase\MapDiff as MapDiff;
use Wikibase\SitelinkChange as SitelinkChange;

/**
 * Tests for the WikibaseSitelinkChange class.
 *
 * @file
 * @since 0.1
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SitelinkChangeTest extends \MediaWikiTestCase {

	public function diffProvider() {
		return array(
			array( MapDiff::newEmpty() ),
			array( MapDiff::newFromArrays( array(), array( 'en' => 'foo' ) ) ),
			array( MapDiff::newFromArrays( array( 'en' => 'bar' ), array( 'en' => 'foo' ) ) ),
			array( MapDiff::newFromArrays( array( 'en' => 'bar' ), array( 'de' => 'bar' ) ) ),
		);
	}

	/**
	 * @param MapDiff $diff
	 * @dataProvider diffProvider
	 */
	public function testNewFromDiff( MapDiff $diff ) {
		$change = SitelinkChange::newFromDiff( Item::newEmpty(), $diff );

		$this->assertEquals( $diff->isEmpty(), $change->isEmpty() );

		$change->setDiff( MapDiff::newEmpty() );

		$this->assertTrue( $change->isEmpty() );

		$diff = MapDiff::newFromArrays( array(), array( 'en' => 'foo' ) );

		$change->setDiff( $diff );

		$this->assertFalse( $change->isEmpty() );

		$this->assertEquals( $diff, $change->getDiff() );
	}

}
	
