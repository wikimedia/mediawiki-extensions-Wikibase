<?php

/**
 * Tests for the WikibaseSitelinkChange class.
 *
 * @file
 * @since 0.1
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * The database group has as a side effect that temporal database tables are created. This makes
 * it possible to test without poisoning a production database.
 * @group Database
 *
 * Some of the tests takes more time, and needs therefor longer time before they can be aborted
 * as non-functional. The reason why tests are aborted is assumed to be set up of temporal databases
 * that hold the first tests in a pending state awaiting access to the database.
 * @group medium
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class WikibaseSitelinkChangeTest extends MediaWikiTestCase {

	public function diffProvider() {
		return array(
			array( WikibaseMapDiff::newEmpty() ),
			array( WikibaseMapDiff::newFromArrays( array(), array( 'foo' ) ) ),
			array( WikibaseMapDiff::newFromArrays( array( 'bar' ), array( 'foo' ) ) ),
			array( WikibaseMapDiff::newFromArrays( array( 'bar' ), array() ) ),
		);
	}

	/**
	 * @param WikibaseMapDiff $diff
	 * @dataProvider diffProvider
	 */
	public function testNewFromDiff( WikibaseMapDiff $diff ) {
		$change =  WikibaseSitelinkChange::newFromDiff( $diff );

		$this->assertEquals( $diff->isEmpty(), $change->isEmpty() );

		$change->setDiff( WikibaseMapDiff::newEmpty() );

		$this->assertTrue( $change->isEmpty() );

		$diff = WikibaseMapDiff::newFromArrays( array(), array( 'foo' ) );

		$change->setDiff( $diff );

		$this->assertFalse( $change->isEmpty() );

		$this->assertEquals( $diff, $change->getDiff() );
	}

}
	
