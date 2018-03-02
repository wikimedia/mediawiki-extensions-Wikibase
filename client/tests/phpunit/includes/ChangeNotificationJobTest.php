<?php

namespace Wikibase\Client\Tests;

use MediaWikiTestCase;
use Title;
use Wikibase\Client\ChangeNotificationJob;

/**
 * @covers Wikibase\Client\ChangeNotificationJob
 *
 * @group Wikibase
 * @group WikibaseChange
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Marius Hoch
 */
class ChangeNotificationJobTest extends MediaWikiTestCase {

	// TODO: testNewFromChanges
	// TODO: testGetChanges
	// TODO: testRun

	public function provideToString() {
		return [
			'empty' => [
				[],
				'/^ChangeNotification.*/'
			],
			'some changes' => [
				[ 5, 37 ],
				'/^ChangeNotification/'
			],
		];
	}

	/**
	 * @dataProvider provideToString
	 */
	public function testToString( array $changeIds, $regex ) {
		$job = new ChangeNotificationJob(
			Title::newMainPage(),
			[ 'repo' => 'repo-db', 'changeIds' => $changeIds ]
		);

		// toString used to fail on some platforms if a job contained a non-primitive parameter.
		$s = $job->toString();
		$this->assertRegExp( $regex, $s );
	}

}
