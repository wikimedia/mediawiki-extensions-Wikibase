<?php

namespace Wikibase\Client\Tests\Notifications;

use EchoEvent;
use EchoEventPresentationModel;
use MediaWikiTestCase;
use Message;
use Title;
use User;
use Wikibase\Client\Hooks\EchoNotificationsHandlers;
use Wikibase\Client\Notifications\PageConnectionPresentationModel;
use Wikibase\Client\RepoLinker;

/**
 * @covers Wikibase\Client\Notifications\PageConnectionPresentationModel
 *
 * @group Database
 * @group Wikibase
 * @group Wikibase Client
 *
 * @since 0.5
 */
class PageConnectionPresentationModelTest extends MediaWikiTestCase {

	/**
	 * @var RepoLinker
	 */
	private $repoLinker;

	protected function setUp() {
		parent::setUp();
		// if Echo is not loaded, skip this test
		if ( !class_exists( EchoEventPresentationModel::class ) ) {
			$this->markTestSkipped( "Echo not loaded" );
		}

		$this->repoLinker = $this->getMockBuilder( RepoLinker::class )
			->disableOriginalConstructor()
			->getMock();
		$this->repoLinker
			->expects( $this->any() )
			->method( 'getEntityUrl' )
			->will( $this->returnValue( 'foo' ) );
	}

	/**
	 * @param Title $title
	 *
	 * @return EchoEvent
	 */
	private function mockEvent( Title $title ) {
		$agent = User::newFromName( 'Agent' );

		$methods = [ 'getAgent', 'getBundleHash', 'getExtraParam', 'getTitle', 'getType' ];
		$event = $this->getMockBuilder( EchoEvent::class )
			->disableOriginalConstructor()
			->setMethods( $methods )
			->getMock();
		$event->expects( $this->any() )
			->method( 'getAgent' )
			->will( $this->returnValue( $agent ) );
		$event->expects( $this->any() )
			->method( 'getBundleHash' )
			->will( $this->returnValue( false ) );
		$event->expects( $this->any() )
			->method( 'getExtraParam' )
			->will( $this->returnValue( 'dummy' ) );
		$event->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );
		$event->expects( $this->any() )
			->method( 'getType' )
			->will( $this->returnValue( EchoNotificationsHandlers::NOTIFICATION_TYPE ) );

		return $event;
	}

	public function testPresentationModel() {
		global $wgEchoNotifications, $wgEchoNotificationCategories, $wgEchoNotificationIcons;
		unset( $wgEchoNotifications[$handlers::NOTIFICATION_TYPE] );
		unset( $wgEchoNotificationCategories['wikibase-action'] );
		unset( $wgEchoNotificationIcons[$handlers::NOTIFICATION_TYPE] );

		$handlers = new EchoNotificationsHandlers(
			$this->repoLinker, 'enwiki', true, false, 'repoSiteName'
		);
		$handlers->doBeforeCreateEchoEvent(
			$wgEchoNotifications, $wgEchoNotificationCategories, $wgEchoNotificationIcons
		);

		$title = Title::newFromText( 'Connected_page' );
		$event = $this->mockEvent( $title );
		$this->assertTrue(
			EchoEventPresentationModel::supportsPresentationModel( $event->getType() ),
			"Failed asserting that Echo supports our notification events"
		);

		$user = User::newFromName( 'User' );
		$model = EchoEventPresentationModel::factory( $event, 'en', $user );
		$this->assertInstanceOf( PageConnectionPresentationModel::class, $model );

		$this->assertFalse(
			$model->canRender(),
			"Failed asserting that the notification cannot be rendered"
		);
		$this->insertPage( 'Connected_page' );
		$this->assertTrue(
			$model->canRender(),
			"Failed asserting that the notification can be rendered"
		);

		$this->assertArrayHasKey(
			$model->getIconType(),
			$wgEchoNotificationIcons,
			"Failed asserting that our presentation model returns an existing icon type"
		);

		$msg = $model->getHeaderMessage();
		$this->assertInstanceOf( Message::class, $msg );
		$this->assertContains( $title->getPrefixedText(), $msg->text() );

		$this->assertEquals(
			[ 'url' => $title->getFullURL(), 'label' => $title->getFullText() ],
			$model->getPrimaryLink(),
			"Failed asserting that the primary link is same"
		);

		$this->assertInternalType( 'array', $model->getSecondaryLinks() );
		$this->assertInstanceOf( Message::class, $model->getSubjectMessage() );
	}

}
