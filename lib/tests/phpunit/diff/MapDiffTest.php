<?php

namespace Wikibase\Test;
use Wikibase\MapDiff as MapDiff;
use Wikibase\DiffOpRemove as DiffOpRemove;
use Wikibase\DiffOpAdd as DiffOpAdd;
use Wikibase\DiffOpChange as DiffOpChange;

/**
 * Tests for the WikibaseMapDiff class.
 *
 * @file
 * @since 0.1
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 * @group Wikibase
 * @group WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class MapDiffTest extends \MediaWikiTestCase {

	public function newFromArraysProvider() {
		return array(
			array(
				array(),
				array(),
				array(),
			),
			array(
				array( 'en' => 'en' ),
				array(),
				array(
					'en' => new DiffOpRemove( 'en' )
				),
			),
			array(
				array(),
				array( 'en' => 'en' ),
				array(
					'en' => new DiffOpAdd( 'en' )
				)
			),
			array(
				array( 'en' => 'foo' ),
				array( 'en' => 'en' ),
				array(
					'en' => new DiffOpChange( 'foo', 'en' )
				),
			),
			array(
				array( 'en' => 'foo' ),
				array( 'en' => 'foo', 'de' => 'bar' ),
				array(
					'de' => new DiffOpAdd( 'bar' )
				)
			),
			array(
				array( 'en' => 'foo' ),
				array( 'en' => 'baz', 'de' => 'bar' ),
				array(
					'de' => new DiffOpAdd( 'bar' ),
					'en' => new DiffOpChange( 'foo', 'baz' )
				)
			),
		);
	}

	/**
	 * @dataProvider newFromArraysProvider
	 */
	public function testNewFromArrays( array $from, array $to, array $expected ) {
		$diff = MapDiff::newFromArrays( $from, $to );

		// Sort to get rid of differences in order, since no promises about order are made.
		asort( $expected );
		$diff->asort();
		$actual = $diff->getArrayCopy();

		$this->assertEquals( $expected, $actual );

		$this->assertEquals(
			$actual === array(),
			$diff->isEmpty()
		);
	}

}
	
