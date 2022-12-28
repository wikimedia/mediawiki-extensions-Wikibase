<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Integration\Hooks;

use MediaWiki\MediaWikiServices;
use MediaWikiIntegrationTestCase;
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

	protected function setUp(): void {
		parent::setUp();

		$store = new MockClientStore( 'de' );
		$this->setService( 'WikibaseClient.Store', $store );
	}

	public function testStateCleared() {
		$title = Title::newMainPage();
		$restrictedEntityLookup = WikibaseClient::getRestrictedEntityLookup();

		$popt = new ParserOptions( User::newFromId( 0 ), $this->getServiceContainer()->getLanguageFactory()->getLanguage( 'en' ) );

		$parser = MediaWikiServices::getInstance()->getParserFactory()->create();
		$parser->parse( '{{#property:P1234|from=Q1}}', $title, $popt );

		$this->assertSame( 1, $restrictedEntityLookup->getEntityAccessCount() );

		$parser->parse( '{{#property:P1234|from=Q2}}', $title, $popt );
		// Count got reset between parser runs
		$this->assertSame( 1, $restrictedEntityLookup->getEntityAccessCount() );

		$parser->parse(
			'{{#property:P1234|from=Q1}}{{#property:P1234|from=Q3}}',
			$title,
			$popt
		);

		// Count got reset between parser runs and Q1 is counted again, although it has been accessed before
		$this->assertSame( 2, $restrictedEntityLookup->getEntityAccessCount() );
	}

}
