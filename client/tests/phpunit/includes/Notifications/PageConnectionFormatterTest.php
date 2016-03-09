<?php

namespace Wikibase\Client\Tests\Notifications;

use Title;
use Wikibase\Client\Notifications\PageConnectionFormatter;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @covers Wikibase\Client\Notifications\PageConnectionFormatter
 *
 * @group Database
 * @group Wikibase
 * @group Wikibase Client
 */
class PageConnectionFormatterTestCase extends \EchoNotificationFormatterTest {

	public function testPageConnectionFormatter() {
		$entityId = new ItemId( 'Q1' );
		$extra = [ 'entityId' => $entityId ];

		$title = Title::newFromText( 'Testpage' );
		$this->insertPage( 'Testpage' );

		$event = $this->mockEvent( 'page-connection', $extra );
		$event->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		$this->assertInstanceOf(
			PageConnectionFormatter::class,
			\EchoNotificationFormatter::factory( $event->getType() )
		);

		$formatted = $this->format( $event, 'email' );
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

		$formatted = $this->format( $event, 'text' );
		$this->assertContains(
			$title->getText(),
			$formatted,
			"Failed asserting that the notification body contains the page title"
		);
	}

}