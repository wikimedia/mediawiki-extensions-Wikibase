<?php

namespace Wikibase\Repo\Tests\Api;

use ApiTestCase;
use Wikibase\Lib\SettingsArray;

/**
 * @covers \Wikibase\Repo\Api\AvailableBadges
 *
 * @group API
 * @group Database
 * @group Wikibase
 * @group WikibaseAPI
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class AvailableBadgesTest extends ApiTestCase {

	private static $badgeItems = [
		'Q123' => '',
		'Q999' => '',
	];

	protected function setUp(): void {
		parent::setUp();

		// Allow some badges for testing
		$this->setService( 'WikibaseRepo.Settings', new SettingsArray( [
			'badgeItems' => self::$badgeItems,
		] ) );
	}

	public function testExecute() {
		list( $result,, ) = $this->doApiRequest( [
			'action' => 'wbavailablebadges',
		] );

		$this->assertEquals( array_keys( self::$badgeItems ), $result['badges'] );
	}

}
