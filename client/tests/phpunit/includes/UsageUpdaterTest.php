<?php

namespace Wikibase\Client\Test\Store;

use Wikibase\Client\Store\UsageUpdater;

/**
 * @covers Wikibase\Client\Store\UsageUpdater
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseUsageTracking
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class UsageUpdaterTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return UsageUpdater
	 */
	private function getUsageUpdater( array &$usages, array &$subscriptions ) {
	}

	public function testUpdateUsageForPage( $newUsages, $expectedUsages, $expectedSubscriptions ) {
		$usages = array();
		$subscriptions = array();

		$updater = $this->getUsageUpdater( $usages, $subscriptions );
		$updater->updateUsageForPage( 23, $newUsages );

		$this->assertEquals( $expectedUsages, $usages, 'Usage' );
		$this->assertEquals( $expectedSubscriptions, $usages, 'Subscription' );
	}

}
