<?php

namespace Wikibase\Test;

use Wikibase\ChangeNotificationJob;

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

	public function provideToString() {
		return array(
			array( // #0: empty
				array(),
				'/^ChangeNotification.*/'
			),
			array( // #1: some changes
				array(
					$this->getMock( 'Wikibase\Change' ),
					$this->getMock( 'Wikibase\Change' ),
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
