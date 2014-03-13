<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\WikibaseRepo;

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

}
