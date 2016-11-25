<?php

namespace Wikibase\Client\Tests\Hooks;

use Config;
use ContentHandler;
use HashConfig;
use MediaWikiTestCase;
use ParserOutput;
use Title;
use Wikibase\Client\Hooks\InterwikiSortingHookHandlers;
use Wikibase\InterwikiSorter;
use Wikibase\NoLangLinkHandler;
use Wikibase\Settings;
use Wikibase\SettingsArray;

/**
 * @covers Wikibase\Client\Hooks\InterwikiSortingHookHandlers
 *
 * @group WikibaseClient
 * @group Wikibase
 * @group WikibaseHooks
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class InterwikiSortingHookHandlersTest extends MediaWikiTestCase {

	public function testNewFromGlobalState() {
		$hookHandlers = InterwikiSortingHookHandlers::newFromGlobalState();

		$this->assertInstanceOf( InterwikiSortingHookHandlers::class, $hookHandlers );
	}

	public function testNewFromInterwikiSortingConfig() {
		$config = $this->getConfig();
		$hookHandlers = InterwikiSortingHookHandlers::newFromInterwikiSortingConfig( $config );

		$this->assertInstanceOf( InterwikiSortingHookHandlers::class, $hookHandlers );
	}

	public function testNewFromWikibaseConfig() {
		$settings = new SettingsArray( [
			'sort' => 'code',
			'sortPrepend' => [],
			'interwikiSortOrders' => [
				'alphabetic' => [
					'ar', 'de', 'en', 'sv', 'zh'
				]
			],
			'alwaysSort' => false,
		] );

		$hookHandlers = InterwikiSortingHookHandlers::newFromWikibaseConfig( $settings );
		$this->assertInstanceOf( InterwikiSortingHookHandlers::class, $hookHandlers );
	}

	public function testOnContentAlterParserOutput() {
		$parserOutput = new ParserOutput();
		$title = Title::makeTitle( NS_HELP, 'InterwikiSortTestPage' );
		$content = ContentHandler::makeContent( 'sorted kittens', $title );

		InterwikiSortingHookHandlers::onContentAlterParserOutput(
			$content,
			$title,
			$parserOutput
		);

		// sanity check
		$this->assertInstanceOf( 'ParserOutput', $parserOutput );
	}

	/**
	 * @dataProvider doContentAlterParserOutputProvider
	 */
	public function testDoContentAlterParserOutput( $expected, $parserOutput, $alwaysSort, $msg ) {
		$config = $this->getConfig();

		$interwikiSorter = new InterwikiSorter(
			$config->get( 'InterwikiSortingSort' ),
			$config->get( 'InterwikiSortingInterwikiSortOrders' ),
			$config->get( 'InterwikiSortingSortPrepend' )
		);

		$interwikiSortingHookHandlers = new InterwikiSortingHookHandlers(
			$interwikiSorter,
			$alwaysSort
		);

		$interwikiSortingHookHandlers->doContentAlterParserOutput( $parserOutput );
		$languageLinks = $parserOutput->getLanguageLinks();

		$this->assertSame( $expected, $languageLinks, $msg );
	}

	public function doContentAlterParserOutputProvider() {
		$parserOutput = new ParserOutput();
		$parserOutput->setLanguageLinks( [
			'es:Gato',
			'en:Cat',
			'fr:Chat',
			'de:Katzen'
		] );

		$parserOutputNoExternalLangLinks = new ParserOutput();

		$parserOutputNoExternalLangLinks->addLanguageLink( 'es:Gato' );
		$parserOutputNoExternalLangLinks->addLanguageLink( 'de:Katzen' );

		NoLangLinkHandler::setNoExternalLangLinks( $parserOutputNoExternalLangLinks, [ '*' ] );

		return [
			[
				[ 'fr:Chat', 'de:Katzen', 'en:Cat', 'es:Gato' ],
				$parserOutput,
				false,
				'external links'
			],
			[
				[ 'es:Gato', 'de:Katzen' ],
				$parserOutputNoExternalLangLinks,
				false,
				'noexternallanglinks'
			],
			[
				[ 'de:Katzen', 'es:Gato' ],
				$parserOutputNoExternalLangLinks,
				true,
				'alwaysSort'
			]
		];
	}

	/**
	 * @return Config
	 */
	private function getConfig() {
		$settings = [
			'InterwikiSortingSort' => InterwikiSorter::SORT_CODE,
			'InterwikiSortingInterwikiSortOrders' => [
				'alphabetic' => [ 'ar', 'de', 'en', 'es', 'fr' ]
			],
			'InterwikiSortingSortPrepend' => [ 'fr' ],
			'InterwikiSortingAlwaysSort' => true
		];

		return new HashConfig( $settings );
	}

}
