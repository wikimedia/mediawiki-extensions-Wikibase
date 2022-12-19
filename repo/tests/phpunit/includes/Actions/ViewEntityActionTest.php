<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Actions;

use MediaWiki\MediaWikiServices;
use OutputPage;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\Actions\ViewEntityAction;
use Wikibase\Repo\WikibaseRepo;
use WikiPage;

/**
 * @covers \Wikibase\Repo\Actions\ViewEntityAction
 *
 * @license GPL-2.0-or-later
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

	protected function setUp(): void {
		parent::setUp();

		// NOTE: use a language here for which we actually have labels etc
		$this->setUserLang( 'de' );

		// Remove handlers for the "OutputPageParserOutput" and "DifferenceEngineViewHeader" hooks
		$this->clearHook( 'OutputPageParserOutput' );
		$this->clearHook( 'DifferenceEngineViewHeader' );
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

		$this->assertStringContainsString( $expected, $output->getHTML() );
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
			[ 'og:type', 'summary' ],
		], $metaTags );
	}

	public function testShowDiff() {
		$page = $this->getTestItemPage( 'Berlin' );

		$latest = $page->getRevisionRecord();
		$previous = MediaWikiServices::getInstance()
			->getRevisionLookup()
			->getPreviousRevision( $latest );

		$params = [
			'diff' => $latest->getId(),
			'oldid' => $previous->getId(),
		];

		$output = $this->executeViewAction( $page, $params );

		$this->assertStringContainsString( 'diff-currentversion-title', $output->getHTML(), 'is diff view' );
		$this->assertNotEditable( $output );
		$this->assertNotHasLinkAlternate( $output );
	}

	public function testShowOldRevision_hasNoEditOrAlternateLinks() {
		$page = $this->getTestItemPage( 'Berlin' );

		$latest = $page->getRevisionRecord();
		$previous = MediaWikiServices::getInstance()
			->getRevisionLookup()
			->getPreviousRevision( $latest );

		$params = [
			'oldid' => $previous->getId(),
		];

		$output = $this->executeViewAction( $page, $params );

		$this->assertNotEditable( $output );
		$this->assertNotHasLinkAlternate( $output );
	}

	public function testShowPrintableVersion_hasNoEditOrAlternateLinks() {
		$page = $this->getTestItemPage( 'Berlin' );
		$requestParams = [ 'printable' => 'yes' ];

		$output = $this->executeViewAction( $page, $requestParams );

		$this->assertNotEditable( $output );
		$this->assertNotHasLinkAlternate( $output );
	}

	public function testShowNonExistingRevision() {
		$page = $this->getTestItemPage( 'Berlin' );
		$params = [ 'oldid' => 2147483647 ];

		$output = $this->executeViewAction( $page, $params );
		$this->assertStringContainsString( 'Die Version 2147483647', $output->getHTML() );
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
	 * @covers \Wikibase\Repo\Actions\ViewEntityAction::onBeforeDisplayNoArticleText
	 */
	public function testShow404() {
		$id = new ItemId( 'q1122334455' );
		$title = WikibaseRepo::getEntityTitleLookup()->getTitleForId( $id );
		$page = $this->getServiceContainer()->getWikiPageFactory()->newFromTitle( $title );
		$action = $this->createAction( 'view', $page );

		/** @var \FauxResponse $response */
		$response = $action->getRequest()->response();
		$response->header( "HTTP/1.1 200 OK" ); // reset

		// $wgSend404Code disabled -----
		$this->setMwGlobals( 'wgSend404Code', false );

		$action->show();
		$this->assertEquals( 200, $response->getStatusCode(), "response code" );
		$this->assertStringContainsString( 'noarticletext', $action->getOutput()->getHTML(), "response HTML" );

		// $wgSend404Code enabled -----
		$this->setMwGlobals( 'wgSend404Code', true );

		$action->show();
		$this->assertEquals( 404, $response->getStatusCode(), "response code" );
	}

	public function testLinkHeaders() {
		$this->setMwGlobals( 'wgLanguageCode', 'qqx' );
		$page = $this->getTestItemPage( 'Berlin' );
		$itemId = $page->getTitle()->getText();
		WikibaseRepo::getSettings()->setSetting( 'entityDataFormats', [ 'json', 'turtle', 'html' ] );

		$output = $this->executeViewAction( $page, [] );
		$this->assertHasLinkAlternate( $output, $itemId, 'json', 'application/json' );
		$this->assertHasLinkAlternate( $output, $itemId, 'ttl', 'text/turtle' );
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
		$this->assertStringNotContainsString( 'wikibase-edittoolbar-container', $html );
		$this->assertStringNotContainsString( 'wikibase-toolbar-button-edit', $html );

		$jsConfigVars = $output->getJsConfigVars();
		$this->assertArrayHasKey( 'wbIsEditView', $jsConfigVars );
		$this->assertFalse( $jsConfigVars['wbIsEditView'], 'wbIsEditView is disabled' );
	}

	private function assertHasLinkAlternate( OutputPage $output, string $itemId, string $ext, string $mime ) {
		$this->assertStringContainsString(
			"Special:EntityData/$itemId.$ext>; rel=\"alternate\"; type=\"$mime\"",
			$output->getLinkHeader()
		);
	}

	private function assertNotHasLinkAlternate( OutputPage $output ) {
		$linkHeader = $output->getLinkHeader();
		if ( $linkHeader ) {
			$this->assertStringNotContainsString( 'rel="alternate"', $linkHeader );
		} else {
			$this->addToAssertionCount( 1 );
		}
	}

}
