<?php

namespace Wikibase\Client\Tests\Notifications;

use EchoEvent;
use EchoEventPresentationModel;
use Message;
use Title;
use User;
use Wikibase\Client\Notifications\PageConnectionPresentationModel;
use Wikibase\Client\RepoLinker;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @covers Wikibase\Client\Notifications\PageConnectionPresentationModel
 *
 * @group Database
 * @group Wikibase
 * @group Wikibase Client
 */
class PageConnectionPresentationModelTest extends \MediaWikiTestCase {

	private function mockEvent( Title $title ) {
		$agent = User::newFromText( 'Agent' );
		$entityId = new ItemId( 'Q1' );
		$extra = [ 'entityId' => $entityId, 'url' => 'URL' ];

		$methods = get_class_methods( 'EchoEvent' );
		$methods = array_diff( $methods, array( 'userCan', 'getLinkMessage', 'getLinkDestination' ) );

		$event = $this->getMockBuilder( 'EchoEvent' )
			->disableOriginalConstructor()
			->setMethods( $methods )
			->getMock();
		$event->expects( $this->any() )
			->method( 'getType' )
			->will( $this->returnValue( 'page-connection' ) );
		$event->expects( $this->any() )
			->method( 'getExtra' )
			->will( $this->returnValue( $extra ) );
		$event->expects( $this->any() )
			->method( 'getAgent' )
			->will( $this->returnValue( $agent ) );
		$event->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		return $event;
	}

	public function testPresentationModel() {
		$title = Title::newFromText( 'Connected_page' );
		$event = $this->mockEvent( $title );

		$this->assertTrue(
			EchoEventPresentationModel::supportsPresentationModel( $event->getType() )
		);

		$user = User::newFromName( 'User' );
		$model = EchoEventPresentationModel::factory( $event, 'en', $user );

		$this->assertInstanceOf( PageConnectionPresentationModel::class, $model );
		$this->assertInstanceOf( Message::class, $model->getHeaderMessage() );
		$this->assertContains( $event->getType(), $model->getHeaderMessageKey() );

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
