<?php

/**
 * Tests for the WikibaseMapDiff class.
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
class WikibaseMapDiffTest extends MediaWikiTestCase {

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
		$diff = WikibaseMapDiff::newFromArrays( $from, $to, $emptyValue, $recursive );
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
	
