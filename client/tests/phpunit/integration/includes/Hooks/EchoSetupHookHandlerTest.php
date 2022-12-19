<?php

namespace Wikibase\Client\Tests\Integration\Hooks;

use MediaWikiIntegrationTestCase;
use Wikibase\Client\Hooks\EchoNotificationsHandlers;
use Wikibase\Client\Hooks\EchoSetupHookHandler;

/**
 * @covers \Wikibase\Client\Hooks\EchoSetupHookHandler
 *
 * @group Database
 * @group WikibaseClient
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class EchoSetupHookHandlerTest extends MediaWikiIntegrationTestCase {

	public function beforeCreateEchoEventProvider() {
		return [
			'no registration' => [
				'register' => false,
				'icon' => false,
				'expectedIcon' => false,
			],
			'registered with optional icon' => [
				'register' => true,
				'icon' => [ 'url' => 'some_url_here' ],
				'expectedIcon' => [ 'url' => 'some_url_here' ],
			],
			'registered with default icon' => [
				'register' => true,
				'icon' => false,
				'expectedIcon' => [ 'path' => 'Wikibase/client/resources/images/echoIcon.svg' ],
			],
		];
	}

	/**
	 * @dataProvider beforeCreateEchoEventProvider
	 */
	public function testBeforeCreateEchoEvent( $register, $icon, $expectedIcon ) {
		$this->markTestSkippedIfExtensionNotLoaded( 'Echo' );

		$notifications = [];
		$categories = [];
		$icons = [];

		$handlers = new EchoSetupHookHandler( $register, $icon );

		$handlers->onBeforeCreateEchoEvent( $notifications, $categories, $icons );

		$this->assertSame( $register, isset( $notifications[EchoNotificationsHandlers::NOTIFICATION_TYPE] ) );
		$this->assertSame( $register, isset( $categories['wikibase-action'] ) );
		$this->assertSame( $register, isset( $icons[EchoNotificationsHandlers::NOTIFICATION_TYPE] ) );

		if ( $register ) {
			if ( isset( $expectedIcon['path'] ) ) {
				$this->assertSame(
					array_keys( $expectedIcon ),
					array_keys( $icons[EchoNotificationsHandlers::NOTIFICATION_TYPE] )
				);
				$this->assertStringEndsWith(
					$expectedIcon['path'],
					$icons[EchoNotificationsHandlers::NOTIFICATION_TYPE]['path']
				);
			} else {
				$this->assertSame(
					$expectedIcon,
					$icons[EchoNotificationsHandlers::NOTIFICATION_TYPE]
				);
			}
		}
	}

}
