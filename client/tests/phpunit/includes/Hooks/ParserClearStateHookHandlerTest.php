<?php

namespace Wikibase\Client\Tests\Hooks;

use Language;
use MediaWikiTestCase;
use Parser;
use ParserOptions;
use Title;
use User;
use Wikibase\Client\WikibaseClient;
use Wikibase\Test\MockClientStore;

/**
 * @covers Wikibase\Client\Hooks\ParserClearStateHookHandler
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseIntegration
 * @group Database
 *
 * @license GPL-2.0+
 * @author Marius Hoch
 */
class ParserClearStateHookHandlerTest extends MediaWikiTestCase {

	protected function setUp() {
		parent::setUp();

		$wikibaseClient = WikibaseClient::getDefaultInstance( 'reset' );
		$store = $wikibaseClient->getStore();

		if ( !( $store instanceof MockClientStore ) ) {
			$store = new MockClientStore( 'de' );
			$wikibaseClient->overrideStore( $store );
		}

		$this->assertInstanceOf(
			MockClientStore::class,
			$wikibaseClient->getStore(),
			'Mocking the default ClientStore failed'
		);
	}

	protected function tearDown() {
		parent::tearDown();

		WikibaseClient::getDefaultInstance( 'reset' );
	}

	public function testStateCleared() {
		$title = Title::newMainPage();
		$restrictedEntityLookup = WikibaseClient::getDefaultInstance()->getRestrictedEntityLookup();

		$popt = new ParserOptions( User::newFromId( 0 ), Language::factory( 'en' ) );

		$parser = new Parser( [ 'class' => 'Parser' ] );
		$parser->parse( '{{#property:P1234|from=Q1}}', $title, $popt, Parser::OT_HTML );

		$this->assertSame( 1, $restrictedEntityLookup->getEntityAccessCount() );

		$parser->parse( '{{#property:P1234|from=Q2}}', $title, $popt, Parser::OT_HTML );
		// Count got reset between parser runs
		$this->assertSame( 1, $restrictedEntityLookup->getEntityAccessCount() );

		$parser->parse(
			'{{#property:P1234|from=Q1}}{{#property:P1234|from=Q3}}',
			$title,
			$popt,
			Parser::OT_HTML
		);

		// Count got reset between parser runs and Q1 is counted again, although it has been accessed before
		$this->assertSame( 2, $restrictedEntityLookup->getEntityAccessCount() );
	}

}
