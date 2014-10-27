<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\ItemId;
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
		$this->languageCode = 'de';

		// Remove handlers for the "OutputPageParserOutput" hook
		$this->mergeMwGlobalArrayValue( 'wgHooks', array( 'OutputPageParserOutput' => array() ) );

		parent::setUp();
	}

	public function testActionForPage() {
		$page = $this->getTestItemPage( "Berlin" );

		$action = $this->createAction( "view", $page );
		$this->assertInstanceOf( 'Wikibase\ViewEntityAction', $action );
	}

	public static function provideShow() {
		$cases = array();

		$cases[] = array(
			'Berlin',
			'/Berlin/'
		);

		if ( self::shouldTestRedirects() ) {
			$cases[] = array(
				'Berlin2',
				'/redirectMsg/'
			);
		}

		return $cases;
	}

	/**
	 * @dataProvider provideShow
	 */
	public function testShow( $handle, $regex ) {
		$page = $this->getTestItemPage( $handle );
		$html = $this->executeViewAction( $page, array() );

		$this->assertRegExp( $regex, $html );
	}

	public function testShowDiff() {
		$page = $this->getTestItemPage( 'Berlin' );

		$latest = $page->getRevision();
		$previous = $latest->getPrevious();

		$params = array(
			'diff' => $latest->getId(),
			'oldid' => $previous->getId()
		);

		$html = $this->executeViewAction( $page, $params );

		$this->assertRegExp( '/diff-currentversion-title/', $html, 'is diff view' );
		$this->assertNotRegExp( '/wikibase-edittoolbar-container/', $html, 'no edit toolbar' );
	}

	public function testShowOldRevision_hasNoEditLinks() {
		$page = $this->getTestItemPage( 'Berlin' );

		$latest = $page->getRevision();
		$previous = $latest->getPrevious();

		$params = array(
			'oldid' => $previous->getId()
		);

		$html = $this->executeViewAction( $page, $params );

		$this->assertNotRegExp( '/wikibase-edittoolbar-container/', $html, 'no edit toolbar' );
	}

	/**
	 * @param WikiPage $page
	 * @param string[] $params
	 *
	 * @return string
	 */
	private function executeViewAction( WikiPage $page, array $params ) {
		$action = $this->createAction( 'view', $page, $params );
		$action->show();

		return $action->getOutput()->getHTML();
	}

	public function testShow404() {
		$id = new ItemId( 'q1122334455' );
		$title = WikibaseRepo::getDefaultInstance()->getEntityContentFactory()->getTitleForId( $id );
		$page = new WikiPage( $title );
		$action = $this->createAction( 'view', $page );

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
