<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Integration\Hooks;

use Language;
use MediaWiki\MediaWikiServices;
use MediaWikiIntegrationTestCase;
use Parser;
use ParserOptions;
use Title;
use User;
use Wikibase\Client\Tests\Mocks\MockClientStore;
use Wikibase\Client\WikibaseClient;

/**
 * @covers \Wikibase\Client\Hooks\ParserHookHandler
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseIntegration
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class ParserHookHandlerTest extends MediaWikiIntegrationTestCase {

	private function resetWikibaseClient(): WikibaseClient {
		$wikibaseClient = WikibaseClient::getDefaultInstance( 'reset' );

		// clear any hook container or handler which might hold a stale
		// RestrictedEntityLookup after we reset WikibaseClient
		$this->resetServices();

		return $wikibaseClient;
	}

	protected function setUp(): void {
		parent::setUp();

		$this->resetWikibaseClient();

		$store = new MockClientStore( 'de' );
		$this->setService( 'WikibaseClient.Store', $store );
	}

	protected function tearDown(): void {
		$this->resetWikibaseClient();
		parent::tearDown();
	}

	public function testStateCleared() {
		$title = Title::newMainPage();
		$restrictedEntityLookup = WikibaseClient::getRestrictedEntityLookup();

		$popt = new ParserOptions( User::newFromId( 0 ), Language::factory( 'en' ) );

		$parser = MediaWikiServices::getInstance()->getParserFactory()->create();
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
