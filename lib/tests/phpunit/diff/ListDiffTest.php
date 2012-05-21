<?php

namespace Wikibase\Test;
use Wikibase\ListDiff as ListDiff;

/**
 * Tests for the WikibaseListDiff class.
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
class ListDiffTest extends \MediaWikiTestCase {

	public function newFromArraysProvider() {
		return array(
			array(
				array(),
				array(),
				array(),
				array(),
			),
			array(
				array( 'foo' ),
				array(),
				array(),
				array( 'foo' ),
			),
			array(
				array(),
				array( 'foo' ),
				array( 'foo' ),
				array(),
			),
			array(
				array( 'foo' ),
				array( 'foo' ),
				array(),
				array(),
			),
			array(
				array( 'foo', 'foo' ),
				array( 'foo' ),
				array(),
				array(),
			),
			array(
				array( 'foo' ),
				array( 'foo', 'foo' ),
				array(),
				array(),
			),
			array(
				array( 'foo', 'bar' ),
				array( 'bar', 'foo' ),
				array(),
				array(),
			),
			array(
				array( 'foo', 'bar', 42, 'baz' ),
				array( 42, 1, 2, 3 ),
				array( 1, 2, 3 ),
				array( 'foo', 'bar', 'baz' ),
			),
			array(
				array( false, null ),
				array( 0, '0' ),
				array( 0, '0' ),
				array( false, null ),
			),
			// The arrays here are getting ignored for some reason... array_diff is weird...
//			array(
//				array( 1, 2, array( 'foo', 'bar' ) ),
//				array( 1, 3, array( 'spam' ), array() ),
//				array( 3, array( 'spam' ), array() ),
//				array( 2, array( 'foo', 'bar' ) ),
//			),
		);
	}

	/**
	 * @dataProvider newFromArraysProvider
	 */
	public function testNewFromArrays( array $from, array $to, array $additions, array $removals ) {
		$diff = ListDiff::newFromArrays( $from, $to );

		$this->assertInstanceOf( '\Wikibase\ListDiff', $diff );
		$this->assertInstanceOf( '\Wikibase\IDiffOp', $diff );
		$this->assertInstanceOf( '\Wikibase\IDiff', $diff );
		$this->assertInstanceOf( '\ArrayIterator', $diff );

		// array_values because we only care about the values, not promises are made about the keys.
		$resultAdditions = array_values( $diff->getAddedValues() );
		$resultRemovals = array_values( $diff->getRemovedValues() );

		// Sort everything since no promises are made about ordering.
		asort( $resultAdditions );
		asort( $resultRemovals );
		asort( $additions );
		asort( $removals );

		$this->assertEquals( $additions, $resultAdditions, 'additions mismatch' );
		$this->assertEquals( $removals, $resultRemovals, 'removals mismatch' );

		$this->assertEquals(
			$additions === array() && $removals === array(),
			$diff->isEmpty()
		);
	}

}
	
