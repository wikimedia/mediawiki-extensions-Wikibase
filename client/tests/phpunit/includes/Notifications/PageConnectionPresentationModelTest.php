<?php

namespace Wikibase\Client\Tests\Notifications;

use EchoEvent;
use EchoEventPresentationModel;
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

	public function testPresentationModel() {
		$methods = get_class_methods( 'EchoEvent' );
		$methods = array_diff( $methods, array( 'userCan', 'getLinkMessage', 'getLinkDestination' ) );

		$agent = User::newFromText( 'Agent' );
		$title = Title::newFromText( 'Connected' );
		$entityId = new ItemId( 'Q1' );
		$extra = [ 'entityId' => $entityId, 'url' => 'URL' ];

		$event = $this->getMockBuilder( 'EchoEvent' )
			->disableOriginalConstructor()
			->setMethods( $methods )
			->getMock();
		$event->expects( $this->any() )
			->method( 'getType' )
			->will( $this->returnValue( 'page-connected' ) );
		$event->expects( $this->any() )
			->method( 'getExtra' )
			->will( $this->returnValue( $extra ) );
		$event->expects( $this->any() )
			->method( 'getAgent' )
			->will( $this->returnValue( $agent ) );
		$event->expects( $this->any() )
			->method( 'getRevision' )
			->will( $this->returnValue( 1 ) );
		$event->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		$user = User::newFromName( 'User' );

		$model = EchoEventPresentationModel::factory( $event, 'en', $user );

		$this->assertInstanceOf( PageConnectionPresentationModel::class, $model );
		$this->assertInstanceOf( 'Message', $model->getHeaderMessage() );
		$this->assertContains( 'page-connection', $model->getHeaderMessageKey() );
		$this->assertFalse(
			$model->canRender(),
			"Failed asserting that the notification cannot be rendered"
		);
		$this->insertPage( 'Connected' );
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