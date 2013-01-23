<?php
namespace Wikibase\Test;
use Wikibase\Change;
use Wikibase\ChangeNotificationJob;

/**
 * Tests for the Wikibase\ChangeNotificationJob class.
 *
 * @file
 * @since 0.4
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseChange
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class ChangeNotificationJobTest extends \MediaWikiTestCase {

	public function testNewFromChanges() {
		$this->markTestIncomplete( "not yet implemented" );
	}

	public function testGetChanges() {
		$this->markTestIncomplete( "not yet implemented" );
	}

	public function testRun() {
		$this->markTestIncomplete( "not yet implemented" );
	}

	public static function provideToString() {
		$change1 = \Wikibase\ChangesTable::singleton()->newRow( array(
			'id' => 1,
			'type' => 'add~item',
		) );

		$change2 = \Wikibase\ChangesTable::singleton()->newRow( array(
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
