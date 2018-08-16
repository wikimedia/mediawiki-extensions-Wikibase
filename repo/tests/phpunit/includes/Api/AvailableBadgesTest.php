<?php

namespace Wikibase\Repo\Tests\Api;

use ApiTestCase;
use Wikibase\Repo\Tests\WikibaseRepoAccess;

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

	use WikibaseRepoAccess;

	private static $badgeItems = [
		'Q123' => '',
		'Q999' => ''
	];

	private static $oldBadgeItems;

	protected function setUp() {
		parent::setUp();

		// Allow some badges for testing
		// todo Consider moving resetting into WikibaseRepoAccess
		$settings = $this->wikibaseRepo->getSettings();
		self::$oldBadgeItems = $settings->getSetting( 'badgeItems' );
		$settings->setSetting( 'badgeItems', self::$badgeItems );
	}

	protected function tearDown() {
		parent::tearDown();

		$settings = $this->wikibaseRepo->getSettings();
		$settings->setSetting( 'badgeItems', self::$oldBadgeItems );
	}

	public function testExecute() {
		list( $result,, ) = $this->doApiRequest( [
			'action' => 'wbavailablebadges'
		] );

		$this->assertEquals( array_keys( self::$badgeItems ), $result['badges'] );
	}

}
