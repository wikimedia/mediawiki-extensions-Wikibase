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
use Wikibase\NamespaceChecker;
use Wikibase\NoLangLinkHandler;
use Wikibase\SettingsArray;

/**
 * @covers Wikibase\Client\Hooks\InterwikiSortingHookHandlers
 *
 * @group Database
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
		$hookHandlers = InterwikiSortingHookHandlers::newFromInterwikiSortingConfig(
			$config,
			$this->getNamespaceChecker( true )
		);

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
			]
		] );

		$hookHandlers = InterwikiSortingHookHandlers::newFromWikibaseConfig(
			$settings,
			$this->getNamespaceChecker( false )
		);

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
	public function testDoContentAlterParserOutput(
		$expected,
		$parserOutput,
		$wikibaseEnabledForNamespace,
		$msg
	) {
		$config = $this->getConfig();

		$interwikiSorter = new InterwikiSorter(
			$config->get( 'InterwikiSortingSort' ),
			$config->get( 'InterwikiSortingInterwikiSortOrders' ),
			$config->get( 'InterwikiSortingSortPrepend' )
		);

		$interwikiSortingHookHandlers = new InterwikiSortingHookHandlers(
			$interwikiSorter,
			$this->getNamespaceChecker( $wikibaseEnabledForNamespace )
		);

		$title = Title::makeTitle( NS_HELP, 'InterwikiSortTestPage' );

		$interwikiSortingHookHandlers->doContentAlterParserOutput( $title, $parserOutput );
		$languageLinks = $parserOutput->getLanguageLinks();

		$this->assertSame( $expected, $languageLinks, $msg );
	}

	public function doContentAlterParserOutputProvider() {
		return [
			[
				[ 'fr:Chat', 'de:Katzen', 'en:Cat', 'es:Gato' ],
				$this->getParserOutput( [ 'es:Gato', 'en:Cat', 'fr:Chat', 'de:Katzen' ], false ),
				true,
				'external links'
			],
			[
				[ 'es:Gato', 'de:Katzen' ],
				$this->getParserOutput( [ 'es:Gato', 'de:Katzen' ], true ),
				true,
				'noexternallanglinks'
			],
			[
				[ 'es:Gato', 'en:Cat', 'fr:Chat', 'de:Katzen' ],
				$this->getParserOutput( [ 'es:Gato', 'en:Cat', 'fr:Chat', 'de:Katzen' ], false ),
				false,
				'wikibase not enabled for namespace'
			]

		];
	}

	/**
	 * @param string[] $languageLinks
	 * @param bool $noExternalLangLinks
	 *
	 * @return ParserOutput
	 */
	private function getParserOutput( array $languageLinks, $noExternalLangLinks ) {
		$parserOutput = new ParserOutput();
		$parserOutput->setLanguageLinks( $languageLinks );

		if ( $noExternalLangLinks === true ) {
			NoLangLinkHandler::setNoExternalLangLinks( $parserOutput, [ '*' ] );
		}

		return $parserOutput;
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
		];

		return new HashConfig( $settings );
	}

	/**
	 * @return NamespaceChecker
	 */
	private function getNamespaceChecker( $wikibaseEnabledForNamespace ) {
		$namespaceChecker = $this->getMockBuilder( 'Wikibase\NamespaceChecker' )
			->disableOriginalConstructor()
			->getMock();

		$namespaceChecker->expects( $this->any() )
			->method( 'isWikibaseEnabled' )
			->will( $this->returnValue( $wikibaseEnabledForNamespace ) );

		return $namespaceChecker;
	}

}
