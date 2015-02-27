<?php

namespace Wikibase\Client\Tests\DataAccess\PropertyParserFunction;

use MediaWikiTestCase;
use Language;
use Parser;
use ParserOptions;
use Title;
use Wikibase\Client\WikibaseClient;
use Wikibase\Test\MockClientStore;
use Wikibase\Client\Tests\DataAccess\WikibaseDataAccessTestItemSetUpHelper;

/**
 * Simple integration test for the {{#property:…}} parser function.
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseDataAccess
 * @group WikibaseIntegration
 * @group PropertyParserFunctionTest
 * @group Database
 *
 * @license GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
class PropertyParserFunctionIntegrationTest extends MediaWikiTestCase {

	protected function setUp() {
		parent::setUp();

		$wikibaseClient = WikibaseClient::getDefaultInstance( 'reset' );
		$store = $wikibaseClient->getStore();

		if ( ! $store instanceof MockClientStore ) {
			$store = new MockClientStore( 'de' );
			$wikibaseClient->overrideStore( $store );
		}

		$this->assertInstanceOf(
			'Wikibase\Test\MockRepository',
			$wikibaseClient->getStore()->getEntityLookup(),
			'Mocking the default client EntityLookup failed'
		);

		$this->setMwGlobals( 'wgContLang', Language::factory( 'de' ) );

		$setupHelper = new WikibaseDataAccessTestItemSetUpHelper( $store );
		$setupHelper->setUp();
	}

	protected function tearDown() {
		parent::tearDown();

		WikibaseClient::getDefaultInstance( 'reset' );
	}

	public function testPropertyParserFunction_byPropertyLabel() {
		$result = $this->parseWikitextToHtml( '{{#property:LuaTestStringProperty}}' );

		$this->assertSame( "<p>Lua&#160;:)\n</p>", $result );
	}

	public function testPropertyParserFunction_byPropertyId() {
		$result = $this->parseWikitextToHtml( '{{#property:P342}}' );

		$this->assertSame( "<p>Lua&#160;:)\n</p>", $result );
	}

	public function testPropertyParserFunction_byNonExistent() {
		$result = $this->parseWikitextToHtml( '{{#property:P123456789111}}' );

		$this->assertRegExp(
			'/<p.*class=".*wikibase-error.*">.*P123456789111.*<\/p>/',
			$result
		);
	}

	public function testPropertyParserFunction_pageNotConnected() {
		$result = $this->parseWikitextToHtml(
			'{{#property:P342}}',
			'A page not connected to an item'
		);

		$this->assertSame( '', $result );
	}

	/**
	 * @param string $wikiText
	 * @param string $title
	 *
	 * @return string HTML
	 */
	private function parseWikitextToHtml( $wikiText, $title = 'WikibaseClientDataAccessTest' ) {
		$parserConfig = array( 'class' => 'Parser' );
		$popt = new ParserOptions();

		$parser = new Parser( $parserConfig );
		$pout = $parser->parse( $wikiText, Title::newFromText( $title ), $popt, Parser::OT_HTML );

		return $pout->getText();
	}

}
