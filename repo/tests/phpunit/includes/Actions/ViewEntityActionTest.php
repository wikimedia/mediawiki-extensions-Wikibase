<?php

namespace Wikibase\Repo\Tests\Actions;

use OutputPage;
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
		$this->mergeMwGlobalArrayValue( 'wgHooks', [ 'OutputPageParserOutput' => [] ] );
	}

	public function testActionForPage() {
		$page = $this->getTestItemPage( "Berlin" );

		$action = $this->createAction( "view", $page );
		$this->assertInstanceOf( ViewEntityAction::class, $action );
	}

	public function provideShow() {
		return [
			[ 'Berlin', 'Hauptstadt von Deutschland' ],
			[ 'Berlin2', 'redirectMsg' ],
		];
	}

	/**
	 * @dataProvider provideShow
	 */
	public function testShow( $handle, $expected ) {
		$page = $this->getTestItemPage( $handle );
		$output = $this->executeViewAction( $page );

		$this->assertContains( $expected, $output->getHTML() );
		$this->assertEditable( $output );
	}

	public function testMetaTags_withoutDescription() {
		$action = $this->createAction( 'view', $this->getTestItemPage( 'Berlin' ) );
		$output = $action->getOutput();
		$output->setProperty( 'wikibase-meta-tags', [ 'title' => 'testTitle' ] );

		$action->show();

		$metaTags = $output->getMetaTags();
		$this->assertSame( [ [ 'og:title', 'testTitle' ] ], $metaTags );
	}

	public function testMetaTags_withTitleAndDescription() {
		$action = $this->createAction( 'view', $this->getTestItemPage( 'Berlin' ) );
		$output = $action->getOutput();
		$output->setProperty( 'wikibase-meta-tags', [
			'title' => 'testTitle',
			'description' => 'testDescription',
		] );

		$action->show();

		$metaTags = $output->getMetaTags();
		$this->assertSame( [
			[ 'og:title', 'testTitle' ],
			[ 'description', 'testDescription' ],
			[ 'og:description', 'testDescription' ],
			[ 'twitter:card', 'summary' ],
		], $metaTags );
	}

	public function testShowDiff() {
		$page = $this->getTestItemPage( 'Berlin' );

		$latest = $page->getRevision();
		$previous = $latest->getPrevious();

		$params = [
			'diff' => $latest->getId(),
			'oldid' => $previous->getId()
		];

		$output = $this->executeViewAction( $page, $params );

		$this->assertContains( 'diff-currentversion-title', $output->getHTML(), 'is diff view' );
		$this->assertNotEditable( $output );
	}

	public function testShowOldRevision_hasNoEditLinks() {
		$page = $this->getTestItemPage( 'Berlin' );

		$latest = $page->getRevision();
		$previous = $latest->getPrevious();

		$params = [
			'oldid' => $previous->getId()
		];

		$output = $this->executeViewAction( $page, $params );

		$this->assertNotEditable( $output );
	}

	public function testShowPrintableVersion_hasNoEditLinks() {
		$page = $this->getTestItemPage( 'Berlin' );
		$requestParams = [ 'printable' => 'yes' ];

		$output = $this->executeViewAction( $page, $requestParams );

		$this->assertNotEditable( $output );
	}

	public function testShowNonExistingRevision() {
		$page = $this->getTestItemPage( 'Berlin' );
		$params = [ 'oldid' => 2147483647 ];

		$output = $this->executeViewAction( $page, $params );
		$this->assertContains( 'Die Version 2147483647', $output->getHTML() );
	}

	/**
	 * @param WikiPage $page
	 * @param string[] $requestParams
	 *
	 * @return OutputPage
	 */
	private function executeViewAction( WikiPage $page, array $requestParams = [] ) {
		$action = $this->createAction( 'view', $page, $requestParams );
		$action->show();

		return $action->getOutput();
	}

	/**
	 * @covers \Wikibase\ViewEntityAction::onBeforeDisplayNoArticleText
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

	private function assertEditable( OutputPage $output ) {
		$jsConfigVars = $output->getJSVars();

		// This mirrors the check the front does in isEditable() in wikibase.ui.entityViewInit.js.
		$this->assertArrayHasKey( 'wbIsEditView', $jsConfigVars );
		$this->assertArrayHasKey( 'wgRelevantPageIsProbablyEditable', $jsConfigVars );
		$this->assertTrue( $jsConfigVars['wbIsEditView'], 'wbIsEditView is enabled' );
		$this->assertTrue(
			$jsConfigVars['wgRelevantPageIsProbablyEditable'],
			'wgRelevantPageIsProbablyEditable is enabled'
		);
	}

	private function assertNotEditable( OutputPage $output ) {
		$html = $output->getHTML();
		$this->assertNotContains( 'wikibase-edittoolbar-container', $html );
		$this->assertNotContains( 'wikibase-toolbar-button-edit', $html );

		$jsConfigVars = $output->getJsConfigVars();
		$this->assertArrayHasKey( 'wbIsEditView', $jsConfigVars );
		$this->assertFalse( $jsConfigVars['wbIsEditView'], 'wbIsEditView is disabled' );
	}

}
