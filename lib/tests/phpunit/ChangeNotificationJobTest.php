<?php

namespace Wikibase\Test;

use Wikibase\Change;
use Wikibase\ChangeNotificationJob;

/**
 * @covers Wikibase\ChangeNotificationJob
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseChange
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class ChangeNotificationJobTest extends \MediaWikiTestCase {

	// TODO: testNewFromChanges
	// TODO: testGetChanges
	// TODO: testRun

	public function provideToString() {
		return array(
			array( // #0: empty
				[],
				'/^ChangeNotification.*/'
			),
			array( // #1: some changes
				array(
					$this->getMock( Change::class ),
					$this->getMock( Change::class ),
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
