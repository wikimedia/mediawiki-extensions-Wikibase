<?php

namespace Wikibase\Client\Tests\Notifications;

use EchoEvent;
use EchoNotificationController;
use EchoNotificationFormatter;
use MediaWikiTestCase;
use Title;
use User;
use Wikibase\Client\Hooks\EchoNotificationsHandlers;
use Wikibase\Client\Notifications\PageConnectionFormatter;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @covers Wikibase\Client\Notifications\PageConnectionFormatter
 *
 * @group Database
 * @group Wikibase
 * @group Wikibase Client
 *
 * @since 0.5
 */
class PageConnectionFormatterTestCase extends MediaWikiTestCase {

	protected function setUp() {
		parent::setUp();
		// if Echo is not loaded, skip this test
		if ( !class_exists( EchoNotificationFormatter::class ) ) {
			$this->markTestSkipped( "Echo not loaded" );
		}
		$user = User::newFromName( 'Notification-formatter-test' );
		if ( $user->isAnon() ) {
			$user = new User();
			$user->setName( 'Notification-formatter-test' );
			$user->addToDatabase();
			$this->setMwGlobals( 'wgUser', $user );
		}
	}

	/**
	 * @param Title $title
	 *
	 * @return EchoEvent
	 */
	private function mockEvent( Title $title ) {
		$agent = User::newFromName( 'Agent' );
		$entityId = new ItemId( 'Q1' );
		$extra = [ 'entityId' => $entityId, 'url' => 'URL' ];

		$methods = [ 'getAgent', 'getExtra', 'getTitle', 'getType', 'loadFromID' ];
		$event = $this->getMockBuilder( 'EchoEvent' )
			->disableOriginalConstructor()
			->setMethods( $methods )
			->getMock();
		$event->expects( $this->any() )
			->method( 'getAgent' )
			->will( $this->returnValue( $agent ) );
		$event->expects( $this->any() )
			->method( 'getExtra' )
			->will( $this->returnValue( $extra ) );
		$event->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );
		$event->expects( $this->any() )
			->method( 'getType' )
			->will( $this->returnValue( 'page-connection' ) );
		$event->expects( $this->any() )
			->method( 'loadFromID' )
			->will( $this->returnSelf() ); // just a dummy value

		return $event;
	}

	public function testPageConnectionFormatter() {
		$entityId = new ItemId( 'Q1' );
		$extra = [ 'entityId' => $entityId ];

		$user = User::newFromName( 'Notification-formatter-test' );
		$title = Title::newFromText( 'Testpage' );
		$this->insertPage( 'Testpage' );

		$event = $this->mockEvent( $title );

		global $wgEchoNotifications;
		$categories = [];
		$icons = [ 'placeholder' => [] ];
		EchoNotificationsHandlers::onBeforeCreateEchoEvent( $wgEchoNotifications, $categories, $icons );

		$this->assertInstanceOf(
			PageConnectionFormatter::class,
			EchoNotificationFormatter::factory( $event->getType() )
		);

		$formatter = new PageConnectionFormatter( $wgEchoNotifications[$event->getType()] );
		$formatter->setOutputFormat( 'email' );
		$formatted = $formatter->format( $event, $user, 'email' );
		$this->assertContains(
			$entityId->getSerialization(),
			$formatted['body'],
			"Failed asserting that the email body contains the item id"
		);
		$this->assertContains(
			$title->getText(),
			$formatted['body'],
			"Failed asserting that the email body contains the page title"
		);

		$formatter->setOutputFormat( 'text' );
		$formatted = $formatter->format( $event, $user, 'web' );
		$this->assertContains(
			$title->getText(),
			$formatted,
			"Failed asserting that the notification body contains the page title"
		);
	}

}
