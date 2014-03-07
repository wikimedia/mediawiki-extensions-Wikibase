<?php

namespace Wikibase\Test;

use Wikibase\ChangeNotificationJob;
use Wikibase\ChangesTable;

/**
 * @covers Wikibase\ChangeNotificationJob
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseChange
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class ChangeNotificationJobTest extends \MediaWikiTestCase {

	// TODO: testNewFromChanges
	// TODO: testGetChanges
	// TODO: testRun

	public static function provideToString() {
		$change1 = ChangesTable::singleton()->newRow( array(
			'id' => 1,
			'type' => 'add~item',
		) );

		$change2 = ChangesTable::singleton()->newRow( array(
			'id' => 2,
			'type' => 'update~item',
		) );

		return array(
			array( // #0: empty
				array(),
				'/^ChangeNotification.*/'
			),
			array( // #1: some changes
				array(
					$change1,
					$change2,
				),
				'/^ChangeNotification/'
			),
		);
	}

	/**
	 * @dataProvider provideToString
	 */
	public function testToString( $changes, $regex ) {
		$job = ChangeNotificationJob::newFromChanges( $changes );

		// toString used to fail on some platforms if a job contained a non-primitive parameter.
		$s = $job->toString();
		$this->assertRegExp( $regex, $s );
	}

}
