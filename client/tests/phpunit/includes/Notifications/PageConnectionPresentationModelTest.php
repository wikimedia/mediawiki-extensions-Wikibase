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
use Wikibase\DataModel\Entity\ItemId;

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

	protected function setUp() {
		parent::setUp();
		// if Echo is not loaded, skip this test
		if ( !class_exists( EchoEventPresentationModel::class ) ) {
			$this->markTestSkipped( "Echo not loaded" );
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

		$methods = [ 'getAgent', 'getExtra', 'getTitle', 'getType' ];
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

		return $event;
	}

	public function testPresentationModel() {
		$title = Title::newFromText( 'Connected_page' );
		$user = User::newFromName( 'User' );
		$event = $this->mockEvent( $title );
		$this->assertTrue(
			EchoEventPresentationModel::supportsPresentationModel( $event->getType() )
		);

		global $wgEchoNotifications;
		$categories = [];
		$icons = [ 'placeholder' => [] ];
		EchoNotificationsHandlers::onBeforeCreateEchoEvent( $wgEchoNotifications, $categories, $icons );
		$model = EchoEventPresentationModel::factory( $event, 'en', $user );

		$this->assertInstanceOf( PageConnectionPresentationModel::class, $model );
		$this->assertContains( $event->getType(), $model->getHeaderMessageKey() );
		$msg = $model->getHeaderMessage();
		$this->assertInstanceOf( Message::class, $msg );
		$this->assertContains( $title->getPrefixedText(), $msg->text() );

		$this->assertFalse(
			$model->canRender(),
			"Failed asserting that the notification cannot be rendered"
		);
		$this->insertPage( 'Connected_page' );
		$this->assertTrue(
			$model->canRender(),
			"Failed asserting that the notification can be rendered"
		);

		$this->assertEquals(
			[ 'label' => $title->getFullText(), 'url' => $title->getFullURL() ],
			$model->getPrimaryLink(),
			"Failed asserting that the primary link is same"
		);

		$this->assertInternalType( 'array', $model->getSecondaryLinks() );
	}

}
