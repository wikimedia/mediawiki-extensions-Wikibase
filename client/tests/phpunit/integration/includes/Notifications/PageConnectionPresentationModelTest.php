<?php

namespace Wikibase\Client\Tests\Integration\Notifications;

use MediaWiki\Extension\Notifications\Formatters\EchoEventPresentationModel;
use MediaWiki\Extension\Notifications\Model\Event;
use MediaWiki\Message\Message;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use MediaWikiIntegrationTestCase;
use Wikibase\Client\Hooks\EchoNotificationsHandlers;
use Wikibase\Client\Hooks\EchoSetupHookHandler;
use Wikibase\Client\Notifications\PageConnectionPresentationModel;

/**
 * @covers \Wikibase\Client\Notifications\PageConnectionPresentationModel
 *
 * @group Database
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Matěj Suchánek
 */
class PageConnectionPresentationModelTest extends MediaWikiIntegrationTestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->markTestSkippedIfExtensionNotLoaded( 'Echo' );
	}

	/**
	 * @param Title $title
	 *
	 * @return Event
	 */
	private function mockEvent( Title $title ) {
		$agent = User::newFromName( 'Agent' );

		$methods = [ 'getAgent', 'getBundleHash', 'getExtraParam', 'getTitle', 'getType' ];
		$event = $this->getMockBuilder( Event::class )
			->disableOriginalConstructor()
			->onlyMethods( $methods )
			->getMock();
		$event->method( 'getAgent' )
			->willReturn( $agent );
		$event->method( 'getBundleHash' )
			->willReturn( false );
		$event->method( 'getExtraParam' )
			->willReturn( 'dummy' );
		$event->method( 'getTitle' )
			->willReturn( $title );
		$event->method( 'getType' )
			->willReturn( EchoNotificationsHandlers::NOTIFICATION_TYPE );

		return $event;
	}

	public function testPresentationModel() {
		global $wgEchoNotifications;

		$notificationCategories = $this->getConfVar( 'EchoNotificationCategories' );
		$icons = $this->getConfVar( 'EchoNotificationIcons' );

		$handlers = new EchoSetupHookHandler(
			true,
			false
		);
		$handlers->onBeforeCreateEchoEvent(
			$wgEchoNotifications, $notificationCategories, $icons
		);

		$title = Title::makeTitle( NS_MAIN, 'Connected_page' );
		$event = $this->mockEvent( $title );
		$this->assertTrue(
			EchoEventPresentationModel::supportsPresentationModel( $event->getType() ),
			"Failed asserting that Echo supports our notification events"
		);

		$user = User::newFromName( 'User' );
		$model = EchoEventPresentationModel::factory(
			$event,
			$this->getServiceContainer()->getLanguageFactory()->getLanguage( 'en' ),
			$user
		);
		$this->assertInstanceOf( PageConnectionPresentationModel::class, $model );

		$this->assertFalse(
			$model->canRender(),
			"Failed asserting that the notification cannot be rendered"
		);
		$this->insertPage( $title );
		$this->assertTrue(
			$model->canRender(),
			"Failed asserting that the notification can be rendered"
		);

		$this->assertArrayHasKey(
			$model->getIconType(),
			$icons,
			"Failed asserting that our presentation model returns an existing icon type"
		);

		$msg = $model->getHeaderMessage();
		$this->assertInstanceOf( Message::class, $msg );
		$this->assertStringContainsString( $title->getPrefixedText(), $msg->text() );

		$this->assertEquals(
			[ 'url' => $title->getFullURL(), 'label' => $title->getFullText() ],
			$model->getPrimaryLink(),
			"Failed asserting that the primary link is same"
		);

		$this->assertIsArray( $model->getSecondaryLinks() );
		$this->assertInstanceOf( Message::class, $model->getSubjectMessage() );

		unset( $wgEchoNotifications[EchoNotificationsHandlers::NOTIFICATION_TYPE] );
	}

}
