<?php

namespace Wikibase\Test;

use Wikibase\ChangeRow;

/**
 * @covers Wikibase\ChangeRow
 *
 * @since 0.2
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseChange
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 * @author Marius Hoch
 */
class ChangeRowTest extends \MediaWikiTestCase {

	public function changeProvider() {
		return array(
			array(
				new ChangeRow(
					null,
					array(
						'user_id' => 1,
						'time' => '20130101000000'
					)
				)
			)
		);
	}

	/**
	 * @dataProvider changeProvider
	 */
	public function testGetUser( ChangeRow $changeRow ) {
		$this->assertInstanceOf( '\User', $changeRow->getUser() );
	}

	/**
	 * @dataProvider changeProvider
	 */
	public function testGetAge( ChangeRow $changeRow ) {
		// Don't assert on equalness because all previous code takes time!
		$this->assertTrue(
			// the time used is one above the minimum run time (4s) for the test,
			// still the normal difference to observe would be 1s.
			abs( ( time() - (int)wfTimestamp( TS_UNIX, '20130101000000' ) ) - $changeRow->getAge() ) <= 5
		);
	}

	/**
	 * @dataProvider changeProvider
	 */
	public function testGetTime( ChangeRow $changeRow ) {
		$this->assertEquals(
			'20130101000000',
			$changeRow->getTime()
		);
	}

	public function testGetObjectId() {
		$data = array( 'object_id' => 'p100' );
		$change = new ChangeRow( null, $data );

		$this->assertEquals(
			'p100',
			$change->getObjectId()
		);
	}

}
