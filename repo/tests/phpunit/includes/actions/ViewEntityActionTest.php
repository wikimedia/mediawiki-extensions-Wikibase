<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Item;
use Wikibase\ItemContent;
use Wikibase\Repo\WikibaseRepo;
use WikiPage;

/**
 * @covers Wikibase\ViewEntityAction
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 *
 * @group Action
 * @group Wikibase
 * @group WikibaseAction
 * @group WikibaseRepo
 *
 * @group Database
 * @group medium
 */
class ViewEntityActionTest extends ActionTestCase {

	public function setUp() {
		// NOTE: use a language here for which we actually have labels etc
		$this->language = 'de';
		parent::setUp();
	}

	public function testActionForPage() {
		$page = $this->getTestItemPage( "Berlin" );

		$action = $this->createAction( "view", $page );
		$this->assertInstanceOf( 'Wikibase\ViewEntityAction', $action );
	}

	public static function provideShow() {
		return array(
			array(
				'Berlin',
				'/Berlin/'
			)
		);
	}

	/**
	 * @dataProvider provideShow
	 */
	public function testShow( $handle, $regex ) {
		$page = $this->getTestItemPage( $handle );
		$action = $this->createAction( "view", $page );

		$action->show();
		$html = $action->getOutput()->getHTML();

		$this->assertRegExp( $regex, $html );
	}

	public function testShow404() {
		$id = new ItemId( 'q1122334455' );
		$page = WikibaseRepo::getDefaultInstance()->getEntityContentFactory()->getWikiPageForId( $id );
		$action = $this->createAction( "view", $page );

		/* @var \FauxResponse $response */
		$response = $action->getRequest()->response();
		$response->header( "HTTP/1.1 200 OK" ); // reset

		// $wgSend404Code disabled -----
		$this->setMwGlobals( 'wgSend404Code', false );

		$action->show();
		$this->assertEquals( 200, $response->getStatusCode(), "response code" );

		// $wgSend404Code enabled -----
		$this->setMwGlobals( 'wgSend404Code', true );

		$action->show();
		$this->assertEquals( 404, $response->getStatusCode(), "response code" );
	}

	public function testGetDiffRevision() {
		$testCases = $this->doDiffRevisionEdits();

		foreach( $testCases as $case ) {
			list( $expected, $oldId, $diffValue, $title ) = $case;
			$page = new WikiPage( $title );
			$viewItemAction = $this->createAction( 'view', $page );

			$revision = $viewItemAction->getDiffRevision( $oldId, $diffValue, $title );
			$this->assertInstanceOf( 'Revision', $revision );

			$id = $revision->getId();
			$this->assertEquals( $expected, $id, 'Retrieved correct diff revision' );
		}
	}

	public function doDiffRevisionEdits() {
		$item = Item::newEmpty();
		$item->setDescription( 'en', 'Largest city in Germany' );

		$content = new ItemContent( $item );
		$status = $content->save( 'create', null, EDIT_NEW );
		assert( $status->isOK() );
		$revId1 = $content->getWikiPage()->getRevision()->getId();

		$content->getEntity()->setDescription( 'en', 'Capital of Germany' );
		$status = $content->save( 'update', null, EDIT_UPDATE );
		assert( $status->isOK() );
		$revId2 = $content->getWikiPage()->getRevision()->getId();

		$content->getEntity()->setDescription( 'en', 'City in Germany' );
		$status = $content->save( 'update', null, EDIT_UPDATE );
		assert( $status->isOK() );
		$revId3 = $content->getWikiPage()->getRevision()->getId();

		$title = $content->getTitle();

		return array(
			array( $revId2, $revId1, $revId2, $title ),
			array( $revId3, $revId2, 'next', $title ),
			array( $revId2, $revId2, 'prev', $title ),
			array( $revId3, $revId1, 'cur', $title )
		);
	}

}
