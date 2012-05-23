<?php

namespace Wikibase\Test;
use Wikibase\MapDiff as MapDiff;
use Wikibase\DiffOpRemove as DiffOpRemove;
use Wikibase\DiffOpAdd as DiffOpAdd;
use Wikibase\DiffOpChange as DiffOpChange;

/**
 * Tests for the MapDiff class.
 *
 * @file
 * @since 0.1
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseDiff
 * @group WikibaseMapDiff
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class MapDiffTest extends \MediaWikiTestCase {

	public function recursionProvider() {//$this->newFromArraysProvider()
		return array_merge( array(), array(
//			array(
//				array(
//					'en' => array( 1, 2, 3 ),
//				),
//				array(
//					'en' => array( 4, 2 ),
//				),
//				array(
//					'en' => 'list',
//				),
//			),
			array(
				array(
					'en' => array( 1, 2, 3 ),
					'nl' => array( 'hax' ),
					'foo' => 'bar',
				),
				array(
					'en' => array( 4, 2 ),
					'de' => array( 'hax' ),
				),
				array(
					'en' => 'list',
					'de' => 'list',
					'nl' => 'list',
					'foo' => new DiffOpRemove( 'bar' ),
				),
			),
		) );
	}

	/**
	 * @dataProvider recursionProvider
	 */
	public function testRecursion( array $from, array $to, $expected ) {
		$diff = MapDiff::newFromArrays( $from, $to, true );

		foreach ( $expected as $key => &$value ) {
			if ( $value === 'list' ) {
				$value = \Wikibase\ListDiff::newFromArrays(
					array_key_exists( $key, $from ) ? $from[$key] : array(),
					array_key_exists( $key, $to ) ? $to[$key] : array()
				);
			}
		}

		asort( $expected );
		$diff->asort();
		$actual = $diff->getArrayCopy();

		$this->assertEquals( $expected, $actual );
	}

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

		$this->assertInstanceOf( '\Wikibase\MapDiff', $diff );
		$this->assertInstanceOf( '\Wikibase\IDiffOp', $diff );
		$this->assertInstanceOf( '\Wikibase\IDiff', $diff );
		$this->assertInstanceOf( '\ArrayIterator', $diff );

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
	
