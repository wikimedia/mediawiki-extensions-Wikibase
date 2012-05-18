<?php

namespace Wikibase\Test;
use Wikibase\MapDiff as MapDiff;

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
				array( 'en' => array( 'old' => 'en', 'new' => null ) ),
			),
			array(
				array(),
				array( 'en' => 'en' ),
				array( 'en' => array( 'old' => null, 'new' => 'en' ) ),
			),
			array(
				array( 'en' => 'foo' ),
				array( 'en' => 'en' ),
				array( 'en' => array( 'old' => 'foo', 'new' => 'en' ) ),
			),
			array(
				array( 'en' => 'foo' ),
				array( 'en' => 'foo', 'de' => 'bar' ),
				array( 'de' => array( 'old' => null, 'new' => 'bar' ) ),
			),
			array(
				array( 'en' => 'foo' ),
				array( 'en' => 'baz', 'de' => 'bar' ),
				array(
					'de' => array( 'old' => null, 'new' => 'bar' ),
					'en' => array( 'old' => 'foo', 'new' => 'baz' ),
				),
			),
		);
	}

	/**
	 * @dataProvider newFromArraysProvider
	 */
	public function testNewFromArrays( array $from, array $to, array $expected, $emptyValue = null, $recursive = false ) {
		$diff = MapDiff::newFromArrays( $from, $to, $emptyValue, $recursive );
		$actual = iterator_to_array( $diff );

		// Sort to get rid of differences in order, since no promises about order are made.
		asort( $expected );
		asort( $actual );

		$this->assertEquals( $expected, $actual );

		$this->assertEquals(
			$actual === array(),
			$diff->isEmpty()
		);
	}

}
	
