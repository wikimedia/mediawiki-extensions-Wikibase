<?php

namespace Wikibase\Test\Api;

use ApiTestCase;
use Wikibase\Repo\WikibaseRepo;

/**
 * Tests for the AvailableBadges class.
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @gorup WikibaseRepo
 * @group medium
 *
 * @licence GNU GPL v2+
 *
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class AvailabeBadgesTest extends ApiTestCase {

	static $badgeItems = array(
		'Q123' => '',
		'Q999' => ''
	);

	private function initConfig() {
		// Allow some badges for testing
		WikibaseRepo::getDefaultInstance()->getSettings()->setSetting( 'badgeItems', self::$badgeItems );
	}

	public function testExecute() {
		$this->initConfig();

		list( $result,, ) = $this->doApiRequest( array(
			'action' => 'wbavailablebadges'
		) );

		$this->assertEquals( array_keys( self::$badgeItems ), $result['badges'] );
	}

}