<?php

/**
 * Tests for the WikibaseListDiff class.
 *
 * The tests are using "Database" to get its own set of temporal tables.
 * This is nice so we avoid poisoning an existing database.
 *
 * The tests are using "medium" so they are able to run alittle longer before they are killed.
 * Without this they will be killed after 1 second, but the setup of the tables takes so long
 * time that the first few tests get killed.
 *
 * The tests are doing some assumptions on the id numbers. If the database isn't empty when
 * when its filled with test items the ids will most likely get out of sync and the tests will
 * fail. It seems impossible to store the item ids back somehow and at the same time not being
 * dependant on some magically correct solution. That is we could use GetItemId but then we
 * would imply that this module in fact is correct.
 *
 * @file
 * @since 0.1
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class WikibaseListDiffTest extends MediaWikiTestCase {

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
		$diff = WikibaseListDiff::newFromArrays( $from, $to );

		// array_values because we only care about the values, not promises are made about the keys.
		$resultAdditions = array_values( $diff->getAdditions() );
		$resultRemovals = array_values( $diff->getRemovals() );

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
	
