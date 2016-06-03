<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\ViewEntityAction;
use WikiPage;

/**
 * @covers Wikibase\ViewEntityAction
 *
 * @license GPL-2.0+
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

	protected function setUp() {
		parent::setUp();

		// NOTE: use a language here for which we actually have labels etc
		$this->setUserLang( 'de' );

		// Remove handlers for the "OutputPageParserOutput" hook
		$this->mergeMwGlobalArrayValue( 'wgHooks', array( 'OutputPageParserOutput' => array() ) );
	}

	public function testActionForPage() {
		$page = $this->getTestItemPage( "Berlin" );

		$action = $this->createAction( "view", $page );
		$this->assertInstanceOf( ViewEntityAction::class, $action );
	}

	public function provideShow() {
		return [
			[ 'Berlin', '/Hauptstadt von Deutschland/' ],
			[ 'Berlin2', '/redirectMsg/' ]
		];
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

		$this->assertContains( 'diff-currentversion-title', $html, 'is diff view' );
		$this->assertNotContains( 'wikibase-edittoolbar-container', $html, 'no edit toolbar' );
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

	public function testShowNonExistingRevision() {
		$page = $this->getTestItemPage( 'Berlin' );
		$params = array( 'oldid' => 2147483647 );

		$html = $this->executeViewAction( $page, $params );
		$this->assertContains( 'Die Version 2147483647', $html, 'non-existing revision' );
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

	/**
	 * @covers ViewEntityAction::onBeforeDisplayNoArticleText
	 */
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
		$this->assertContains( 'noarticletext', $action->getOutput()->getHTML(), "response HTML" );

		// $wgSend404Code enabled -----
		$this->setMwGlobals( 'wgSend404Code', true );

		$action->show();
		$this->assertEquals( 404, $response->getStatusCode(), "response code" );
	}

}
