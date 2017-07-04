<?php

namespace Wikibase\Repo\Tests\Api;

use ApiTestCase;
use Wikibase\Repo\WikibaseRepo;

/**
 * Tests for the AvailableBadges class.
 *
 * @group API
 * @group Database
 * @group Wikibase
 * @group WikibaseAPI
 * @group medium
 *
 * @license GPL-2.0+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class AvailableBadgesTest extends ApiTestCase {

	private static $badgeItems = [
		'Q123' => '',
		'Q999' => ''
	];

	private static $oldBadgeItems;

	protected function setUp() {
		parent::setUp();

		// Allow some badges for testing
		$settings = WikibaseRepo::getDefaultInstance()->getSettings();
		self::$oldBadgeItems = $settings->getSetting( 'badgeItems' );
		$settings->setSetting( 'badgeItems', self::$badgeItems );
	}

	protected function tearDown() {
		parent::tearDown();

		$settings = WikibaseRepo::getDefaultInstance()->getSettings();
		$settings->setSetting( 'badgeItems', self::$oldBadgeItems );
	}

	public function testExecute() {
		list( $result,, ) = $this->doApiRequest( [
			'action' => 'wbavailablebadges'
		] );

		$this->assertEquals( array_keys( self::$badgeItems ), $result['badges'] );
	}

}
