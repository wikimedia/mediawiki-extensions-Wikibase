<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Integration\Hooks;

use MediaWiki\Extension\Notifications\Model\Event;
use MediaWikiIntegrationTestCase;
use Wikibase\Client\Hooks\EchoGetBundleRulesHandler;
use Wikibase\Client\Hooks\EchoNotificationsHandlers;

/**
 * @covers \Wikibase\Client\Hooks\EchoGetBundleRulesHandler
 *
 * @group Database
 * @group WikibaseClient
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class EchoGetBundleRulesHandlerTest extends MediaWikiIntegrationTestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->markTestSkippedIfExtensionNotLoaded( 'Echo' );
	}

	public function testOnEchoGetBundleRules() {
		$bundleKey = 'originalValue';

		$event = $this->createMock( Event::class );
		$event->expects( $this->once() )
			->method( 'getType' )
			->willReturn( EchoNotificationsHandlers::NOTIFICATION_TYPE );

		$handler = new EchoGetBundleRulesHandler();
		$handler->onEchoGetBundleRules( $event, $bundleKey );

		$this->assertSame( EchoNotificationsHandlers::NOTIFICATION_TYPE, $bundleKey );
	}

	public function testOnEchoGetBundleRules_unchanged() {
		$bundleKey = 'originalValue';

		$event = $this->createMock( Event::class );
		$event->expects( $this->once() )
			->method( 'getType' )
			->willReturn( 'blah' );

		$handler = new EchoGetBundleRulesHandler();
		$handler->onEchoGetBundleRules( $event, $bundleKey );

		$this->assertSame( 'originalValue', $bundleKey );
	}

}
