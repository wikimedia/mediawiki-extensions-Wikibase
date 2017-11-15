<?php

namespace Wikibase\Client\Tests\Usage;

use PHPUnit_Framework_TestCase;
use Wikibase\Client\Usage\NullSubscriptionManager;

/**
 * @covers Wikibase\Client\Usage\NullSubscriptionManager
 *
 * @group WikibaseClient
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Thiemo Kreuz
 */
class NullSubscriptionManagerTest extends PHPUnit_Framework_TestCase {

	public function testSubscribe() {
		$instance = new NullSubscriptionManager();
		$this->assertNull( $instance->subscribe( '', [] ) );
	}

	public function testUnsubscribe() {
		$instance = new NullSubscriptionManager();
		$this->assertNull( $instance->unsubscribe( '', [] ) );
	}

}
