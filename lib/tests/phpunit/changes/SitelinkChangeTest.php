<?php

namespace Wikibase\Test;
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
		$change =  SitelinkChange::newFromDiff( $diff );

		$this->assertEquals( $diff->isEmpty(), $change->isEmpty() );

		$change->setDiff( MapDiff::newEmpty() );

		$this->assertTrue( $change->isEmpty() );

		$diff = MapDiff::newFromArrays( array(), array( 'en' => 'foo' ) );

		$change->setDiff( $diff );

		$this->assertFalse( $change->isEmpty() );

		$this->assertEquals( $diff, $change->getDiff() );
	}

}
	
