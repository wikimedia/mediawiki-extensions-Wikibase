<?php

namespace Wikibase\Client\Tests\Notifications;

use EchoNotificationController;
use EchoNotificationFormatter;
use MediaWikiTestCase;
use Title;
use User;
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

	public function testPageConnectionFormatter() {
		$entityId = new ItemId( 'Q1' );
		$extra = [ 'entityId' => $entityId ];

		$user = User::newFromName( 'Test_user' );
		$title = Title::newFromText( 'Testpage' );
		$this->insertPage( 'Testpage' );

		$event = $this->mockEvent( 'page-connection', $extra );
		$event->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		$this->assertInstanceOf(
			PageConnectionFormatter::class,
			EchoNotificationFormatter::factory( $event->getType() )
		);

		$formatted = EchoNotificationController::formatNotification( $event, 'email', $user );
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

		$formatted = EchoNotificationController::formatNotification( $event, 'text', $user );
		$this->assertContains(
			$title->getText(),
			$formatted,
			"Failed asserting that the notification body contains the page title"
		);
	}

}
