<?php

namespace Wikibase\Client\Tests\Usage;

use Wikibase\Client\Usage\NullSubscriptionManager;

/**
 * @covers Wikibase\Client\Usage\NullSubscriptionManager
 *
 * @group WikibaseClient
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class NullSubscriptionManagerTest extends \PHPUnit\Framework\TestCase {

	public function testSubscribe() {
		$instance = new NullSubscriptionManager();
		$this->assertNull( $instance->subscribe( '', [] ) );
	}

	public function testUnsubscribe() {
		$instance = new NullSubscriptionManager();
		$this->assertNull( $instance->unsubscribe( '', [] ) );
	}

}
