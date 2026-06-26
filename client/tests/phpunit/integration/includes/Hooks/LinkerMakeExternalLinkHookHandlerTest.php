<?php declare( strict_types=1 );

namespace Wikibase\Client\Tests\Integration\Hooks;

use MediaWiki\Context\RequestContext;
use MediaWiki\Language\Language;
use MediaWiki\Language\LanguageFactory;
use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
use Throwable;
use Wikibase\Client\Hooks\Formatter\ClientEntityLinkFormatter;
use Wikibase\Client\Hooks\LinkerMakeExternalLinkHookHandler;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookupException;
use Wikibase\DataModel\Term\TermFallback;
use Wikibase\Lib\FederatedProperties\FederatedPropertyId;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\FallbackLabelDescriptionLookup;

/**
 * @covers \Wikibase\Client\Hooks\LinkerMakeExternalLinkHookHandler
 *
 * @group WikibaseClient
 * @group WikibaseHooks
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class LinkerMakeExternalLinkHookHandlerTest extends TestCase {
	private FallbackLabelDescriptionLookup $mockLookup;
	private EntityIdParser $mockParser;
	private RequestContext $context;
	private SettingsArray $settings;
	private bool $isRepoEntityNamespaceMain;

	public function setUp(): void {
		parent::setUp();
		$this->settings = WikibaseClient::getSettings();
		$this->settings->setSetting( 'repoUrl', "https://www.wikidata.org" );

		$this->mockParser = $this->createStub( EntityIdParser::class );
		$this->mockLookup = $this->createStub( FallbackLabelDescriptionLookup::class );

		$this->context = RequestContext::getMain();
		$title = $this->createMock( Title::class );
		$title->method( 'isSpecialPage' )->willReturn( true );
		$title->method( 'isSpecial' )->willReturnMap( [
			[ 'Watchlist', true ],
			[ 'Recentchanges', true ],
		] );
		$this->context->setTitle( $title );
		$this->isRepoEntityNamespaceMain = false;
	}

	private function getHookHandler(): LinkerMakeExternalLinkHookHandler {
		$mockEnLanguage = $this->createMock( Language::class );
		$mockEnLanguage->method( 'getCode' )->willReturn( 'en' );
		$mockEnLanguage->method( 'getDirMark' )->willReturn( '' );
		$mockEnLanguage->method( 'getDir' )->willReturn( 'ltr' );
		$mockEnLanguage->method( 'getHtmlCode' )->willReturn( 'en' );

		$languageFactory = $this->createMock( LanguageFactory::class );
		$languageFactory->method( 'getLanguage' )
			->with( 'en' )
			->willReturn( $mockEnLanguage );

		return new LinkerMakeExternalLinkHookHandler(
			$mockEnLanguage,
			new ClientEntityLinkFormatter( $languageFactory ),
			$this->mockParser,
			$this->isRepoEntityNamespaceMain,
			$this->mockLookup,
			parse_url( $this->settings->getSetting( 'repoUrl' ), PHP_URL_HOST ),
			$this->context->getTitle(),
		);
	}

	public static function onLinkerMakeExternalLinkFailuresProvider(): \Generator {
		yield "Non-Wikibase Url" => [
			"url" => "https://en.wikipedia.org/wiki/Berlin",
			"originalText" => "Berlin",
			"parseInput" => "Berlin",
			"parseOutput" => new EntityIdParsingException(),
		];
		yield "Non-entity namespace" => [
			"url" => "https://www.wikidata.org/wiki/Talk:Q64",
			"originalText" => "Talk:Q64",
			"parseInput" => "Q64",
			"parseOutput" => new ItemId( "Q64" ),
		];
		yield "Invalid QID - Q0" => [
			"url" => "https://www.wikidata.org/wiki/Q0",
			"originalText" => "Q0",
			"parseInput" => "Q0",
			"parseOutput" => new EntityIdParsingException(),
		];
		yield "Invalid QID - Not an item id but a federated property id" => [
			"url" => "https://www.wikidata.org/wiki/P23",
			"originalText" => "P23",
			"parseInput" => "P23",
			"parseOutput" => new FederatedPropertyId( "https://www.wikidata.org/wiki/P23", "P23" ),
		];
		yield "Invalid QID - Not an item id but a different page title" => [
			"url" => "https://www.wikidata.org/wiki/Main_page",
			"originalText" => "Main_page",
			"parseInput" => "Main_page",
			"parseOutput" => new EntityIdParsingException(),
		];
		yield "Item not found - Q123456789" => [
			"url" => "https://www.wikidata.org/wiki/Q123456789",
			"originalText" => "Q123456789",
			"parseInput" => "Q123456789",
			"parseOutput" => new ItemId( "Q123456789" ),
		];
	}

	/**
	 * @dataProvider onLinkerMakeExternalLinkFailuresProvider
	 */
	public function testOnLinkerMakeExternalLink_failureCases(
		string $url,
		string $originalText,
		string $parseInput,
		EntityId|EntityIdParsingException $parseOutput
	) {
		$this->mockParser = $this->createMock( EntityIdParser::class );
		$this->mockParser->method( 'parse' )
			->with( $parseInput )
			->willReturnCallback( static function () use ( $parseOutput ) {
				if ( $parseOutput instanceof Throwable ) {
					throw $parseOutput;
				} else {
					return $parseOutput;
				}
			} );

		$this->mockLookup = $this->createStub( FallbackLabelDescriptionLookup::class );

		$text = $originalText;
		$attribs = [];
		$link = "link";
		$myHookHandler = $this->getHookHandler();
		$myHookHandler->onLinkerMakeExternalLink( $url, $text, $link, $attribs, "linkType" );
		$this->assertEquals( $originalText, $text );
	}

	public function testOnLinkerMakeExternalLink_notSpecialPage() {
		$title = $this->createMock( Title::class );
		$title->method( 'isSpecialPage' )->willReturn( false );
		$this->context->setTitle( $title );

		$originalText = "Q64";
		$url = "https://www.wikidata.org/wiki/Q64";
		$text = $originalText;
		$attribs = [];
		$link = "link";
		$myHookHandler = $this->getHookHandler();
		$myHookHandler->onLinkerMakeExternalLink( $url, $text, $link, $attribs, "linkType" );
		$this->assertEquals( $originalText, $text );
	}

	public function testOnLinkerMakeExternalLink_notWatchlistOrRecentchanges() {
		$title = $this->createMock( Title::class );
		$title->method( 'isSpecialPage' )->willReturn( true );
		$title->method( 'isSpecial' )->willReturnMap( [
			[ 'Watchlist', false ],
			[ 'Recentchanges', false ],
		] );
		$this->context->setTitle( $title );

		$originalText = "Q64";
		$url = "https://www.wikidata.org/wiki/Q64";
		$text = $originalText;
		$attribs = [];
		$link = "link";
		$myHookHandler = $this->getHookHandler();
		$myHookHandler->onLinkerMakeExternalLink( $url, $text, $link, $attribs, "linkType" );
		$this->assertEquals( $originalText, $text );
	}

	public function testOnLinkerMakeExternalLink_lookupException() {
		$this->mockParser = $this->createMock( EntityIdParser::class );
		$this->mockParser->method( 'parse' )
			->with( 'Q123456789' )
			->willReturn( new ItemId( 'Q123456789' ) );
		$this->mockLookup = $this->createStub( FallbackLabelDescriptionLookup::class );
		$this->mockLookup->method( 'getLabel' )
			->willThrowException( new LabelDescriptionLookupException( new ItemId( 'Q123456789' ) ) );

		$originalText = 'Q123456789';
		$text = $originalText;
		$url = 'https://www.wikidata.org/wiki/Q123456789';
		$attribs = [];
		$link = "link";
		$myHookHandler = $this->getHookHandler();
		$myHookHandler->onLinkerMakeExternalLink( $url, $text, $link, $attribs, "linkType" );
		$this->assertEquals( $originalText, $text );
	}

	public function testOnLinkerMakeExternalLink_repoEntityNamespaceIsNotMain() {
		// There is no explicit namespace (e.g. Item:Q64)
		$originalText = "Q64";
		$url = "https://www.wikidata.org/wiki/Q64";
		$text = $originalText;
		$attribs = [];
		$link = "link";
		$myHookHandler = $this->getHookHandler();
		$myHookHandler->onLinkerMakeExternalLink( $url, $text, $link, $attribs, "linkType" );
		$this->assertEquals( $originalText, $text );
	}

	public function testOnLinkerMakeExternalLink_repoEntityNamespaceIsMain() {
		$this->isRepoEntityNamespaceMain = true;
		// There is no explicit namespace (e.g. Item:Q64)
		$originalText = "Q64";
		$url = "https://www.wikidata.org/wiki/Q64";
		$attribs = [];
		$expected = '<span class="wb-itemlink"><span class="wb-itemlink-label" lang="en" dir="ltr">'
			. 'Berlin'
			. '</span> <span class="wb-itemlink-id">(Q64)</span></span>';
		$expectedTitleAttribs = 'Berlin | Capital of Germany';

		$this->testOnLinkerMakeExternalLink_success( $url, $originalText, $expected, $attribs, $expectedTitleAttribs );
	}

	public static function onLinkerMakeExternalLinkSuccessProvider(): \Generator {
		yield "Item: Label and description both exist" => [
			"https://www.wikidata.org/wiki/Item:Q64",
			'Item:Q64',
			'<span class="wb-itemlink"><span class="wb-itemlink-label" lang="en" dir="ltr">'
			. 'Berlin'
			. '</span> <span class="wb-itemlink-id">(Q64)</span></span>',
			[],
			'Berlin | Capital of Germany',
		];
		yield "Property: Label and description both exist" => [
			"https://www.wikidata.org/wiki/Property:P31",
			"Property:P31",
			'<span class="wb-itemlink"><span class="wb-itemlink-label" lang="en" dir="ltr">'
			. 'instance of'
			. '</span> <span class="wb-itemlink-id">(P31)</span></span>',
			[],
			'instance of | type to which this subject corresponds/belongs.',
		];
	}

	/**
	 * @dataProvider onLinkerMakeExternalLinkSuccessProvider
	 */
	public function testOnLinkerMakeExternalLink_success(
		string $url, string $text, string $expectedText, array $attribs, string $expectedTitleAttribs
	): void {
		$itemId = new ItemId( 'Q64' );
		$propertyId = new NumericPropertyId( 'P31' );

		$this->mockParser = $this->createMock( EntityIdParser::class );
		$this->mockParser->method( 'parse' )
			->willReturnMap( [
				[ 'Q64', $itemId ],
				[ 'P31', $propertyId ],
			] );

		$this->mockLookup = $this->createMock( FallbackLabelDescriptionLookup::class );
		$this->mockLookup->method( 'getLabel' )
			->willReturnMap( [
				[ $itemId, new TermFallback( 'en', 'Berlin', 'en', null ) ],
				[ $propertyId, new TermFallback( 'en', 'instance of', 'en', null ) ],
			] );

		$this->mockLookup->method( 'getDescription' )
			->willReturnMap( [
				[ $itemId, new TermFallback( 'en', 'Capital of Germany', 'en', null ) ],
				[ $propertyId, new TermFallback( 'en', 'type to which this subject corresponds/belongs.', 'en', null ) ],
			] );

		$link = "link";
		$linkType = "linkType";
		$myHookHandler = $this->getHookHandler();
		$myHookHandler->onLinkerMakeExternalLink( $url, $text, $link, $attribs, $linkType );
		$this->assertEquals( $expectedText, $text );
		$this->assertArrayHasKey( 'title', $attribs );
		$this->assertEquals( $expectedTitleAttribs, $attribs['title'] );
	}

	public function testIsRepoUrl_success(): void {
		$myHookHandler = $this->getHookHandler();
		$this->assertTrue( $myHookHandler->isRepoUrl( "https://www.wikidata.org/wiki/Q2" ) );
		$this->assertTrue( $myHookHandler->isRepoUrl( "https://www.wikidata.org/wiki/Special:BrokenRedirects" ) );
	}

	public function testIsRepoUrl_failure(): void {
		$myHookHandler = $this->getHookHandler();
		$this->assertFalse( $myHookHandler->isRepoUrl( "http://default.mediawiki.mwdd.localhost:8080/wiki/Item:Q1" ) );
		$this->assertFalse( $myHookHandler->isRepoUrl( "https://en.wikipedia.org/wiki/Main_Page" ) );
		$this->assertFalse( $myHookHandler->isRepoUrl( "wikidata.org" ) );
	}

}
