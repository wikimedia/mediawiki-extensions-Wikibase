<?php

declare( strict_types=1 );
namespace Wikibase\Client\Tests\Unit\DataAccess\Scribunto;

use Language;
use MediaWiki\Languages\LanguageFactory;
use MediaWiki\MediaWikiServices;
use PHPUnit\Framework\TestCase;
use Wikibase\Client\DataAccess\Scribunto\CachingFallbackBasedTermLookup;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\TermFallback;
use Wikibase\DataModel\Term\TermTypes;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookupFactory;
use Wikibase\Lib\Store\RedirectResolvingLatestRevisionLookup;
use Wikibase\Lib\TermFallbackCache\TermFallbackCacheFacade;

/**
 * @covers \Wikibase\Client\DataAccess\Scribunto\CachingFallbackBasedTermLookup
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseScribunto
 *
 * @license GPL-2.0-or-later
 */
class CachingFallbackBasedTermLookupTest extends TestCase {

	public const ITEM_Q1_REVISION = 1;

	private $termFallbackCache;
	private $revisionLookup;
	private $lookupFactory;
	private $factoryReturnLookup;
	/**
	 * @var LanguageFactory|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $languageFactory;
	/**
	 * @var \PHPUnit\Framework\MockObject\MockObject|ContentLanguages
	 */
	private $contentLanguages;

	protected function setUp(): void {
		parent::setUp();
		$this->termFallbackCache = $this->createMock( TermFallbackCacheFacade::class );
		$this->revisionLookup = $this->createMock( RedirectResolvingLatestRevisionLookup::class );
		$this->lookupFactory = $this->createMock( LanguageFallbackLabelDescriptionLookupFactory::class );
		$this->factoryReturnLookup = $this->createMock( LanguageFallbackLabelDescriptionLookup::class );
		$this->languageFactory = $this->createMock( LanguageFactory::class );
		$this->contentLanguages = $this->createMock( ContentLanguages::class );

		$englishLanguage = MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'en' );
		$this->languageFactory->method( 'getLanguage' )
			->with( 'en' )
			->willReturn( $englishLanguage );
	}

	public function getLookup(): CachingFallbackBasedTermLookup {
		return new CachingFallbackBasedTermLookup(
			$this->termFallbackCache,
			$this->revisionLookup,
			$this->lookupFactory,
			$this->languageFactory,
			$this->contentLanguages
		);
	}

	public function testGetLabelChecksTheCacheAndUsesIfValueThere() {
		$term = 'cat';
		$itemId = new ItemId( 'Q1' );

		$this->mockHasContentLanguage( true );
		$this->mockCacheWithContent( $term, $itemId );

		$lookup = $this->getLookup();
		$this->assertEquals(
			$term,
			$lookup->getLabel( $itemId, 'en' )
		);
	}

	public function testGetDescriptionChecksTheCacheAndUsesIfValueThere() {
		$term = 'cat';
		$itemId = new ItemId( 'Q1' );

		$this->mockHasContentLanguage( true );
		$this->mockCacheWithContent( $term, $itemId );

		$lookup = $this->getLookup();
		$this->assertEquals(
			$term,
			$lookup->getDescription( $itemId, 'en' )
		);
	}

	private function getTermFallback( $term, $requestLanguageCode, $actualLanguageCode = null ): ?TermFallback {
		if ( $term === null ) {
			return null;
		}

		return new TermFallback(
			$requestLanguageCode,
			$term,
			$actualLanguageCode ? $actualLanguageCode : $requestLanguageCode,
			null
		);
	}

	public function nonCachingLookupProvider() {
		$englishLanguage = MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'en' );

		$englishCat = $this->getTermFallback( 'cat', 'en' );
		$swedishEnglishCat = $this->getTermFallback( 'katt', 'en', 'sv' );

		$dataset = [
			[ // 'finds term in requested language, caches it and returns it'
				$englishCat,
				$englishLanguage,
				'cat'
			],
			[ // 'finds null and caches it, returns null'
				null,
				$englishLanguage,
				null
			],
			[ // finds term in different language and caches it and returns null
				$swedishEnglishCat,
				$englishLanguage,
				null
			]
		];

		$datasetLabels = array_map( function( $testCase ) {
			$testCase[] = TermTypes::TYPE_LABEL;
			return $testCase;
		}, $dataset );

		$datasetDescriptions = array_map( function( $testCase ) {
			$testCase[] = TermTypes::TYPE_DESCRIPTION;
			return $testCase;
		}, $dataset );

		return array_merge( $datasetLabels, $datasetDescriptions );
	}

	/**
	 * @dataProvider nonCachingLookupProvider
	 *
	 * @param TermFallback|null $termFallback
	 * @param Language $language
	 * @param string|null $expectedTerm
	 * @param string $termType
	 */
	public function testGetTermUsesInternalLookupWithCacheMiss(
		?TermFallback $termFallback,
		Language $language,
		$expectedTerm,
		string $termType
	) {
		$itemId = new ItemId( 'Q1' );
		$methodName = $termType === TermTypes::TYPE_LABEL ? 'getLabel' : 'getDescription';

		$this->mockHasContentLanguage( true );

		// no cache hit
		$this->mockCacheEmpty( $itemId );

		// should return a fallback
		$this->mockInternalLookupWithContent( $itemId, $termFallback, $methodName );

		// should store this in the cache
		$this->mockCacheSetExpectation(
			$termFallback,
			$itemId,
			self::ITEM_Q1_REVISION,
			$language->getCode(),
			$termType
		);

		$lookup = $this->getLookup();

		$this->assertEquals(
			$expectedTerm,
			$lookup->$methodName( $itemId, $language->getCode() )
		);
	}

	public function testDoesNotPolluteCacheWithNonExistingLanguages() {
		$itemId = new ItemId( 'Q1' );

		// called with invalid language
		$this->mockHasContentLanguage( false );

		// found by revisionLookup
		$this->mockRevisionLookup( $itemId );

		// but never calls cache
		$this->termFallbackCache->expects( $this->never() )->method( 'get' );

		$lookup = $this->getLookup();
		$result = $lookup->getLabel( $itemId, 'some weird thing' );
		$this->assertNull( $result );
	}

	public function testShouldNotCreateMultipleLookupsForSameLanguage() {
		$itemOneId = new ItemId( 'Q1' );
		$itemTwoId = new ItemId( 'Q2' );

		$language = MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'en' );
		$termFallbackOne = $this->getTermFallback( 'cat', 'en' );
		$termFallbackTwo = $this->getTermFallback( 'hat', 'en' );

		$this->mockHasContentLanguage( true );

		$this->revisionLookup
			->method( 'lookupLatestRevisionResolvingRedirect' )
			->withConsecutive( [ $itemOneId ], [ $itemTwoId ] )
			->willReturnOnConsecutiveCalls(
				[ 1, $itemOneId ],
				[ 2, $itemTwoId ]
			);

		$this->termFallbackCache
			->expects( $this->atLeastOnce() )
			->method( 'get' )
			->willReturn( TermFallbackCacheFacade::NO_VALUE );

		$this->factoryReturnLookup->method( 'getLabel' )
			->withConsecutive( [ $itemOneId ], [ $itemTwoId ] )
			->willReturnOnConsecutiveCalls( $termFallbackOne, $termFallbackTwo );

		$this->lookupFactory->expects( $this->once() )
			->method( 'newLabelDescriptionLookup' )
			->willReturn( $this->factoryReturnLookup );

		$lookup = $this->getLookup();

		$this->assertEquals(
			'cat',
			$lookup->getLabel( $itemOneId, $language->getCode() )
		);

		$this->assertEquals(
			'hat',
			$lookup->getLabel( $itemTwoId, $language->getCode() )
		);
	}

	/** @dataProvider provideTermTypes */
	public function testReturnsExistingTermsForMultipleLanguageCodes( string $termType ) {
		$getOne = $termType === TermTypes::TYPE_LABEL ? 'getLabel' : 'getDescription';
		$getMultiple = $termType === TermTypes::TYPE_LABEL ? 'getLabels' : 'getDescriptions';

		$itemId = new ItemId( 'Q1' );
		$enTerm = $this->getTermFallback( 'cat', 'en' );

		$this->contentLanguages->method( 'hasLanguage' )
			->willReturnCallback( function ( $languageCode ) {
				return $languageCode === 'en';
			} );
		$this->mockHasContentLanguage( true );
		$this->mockCacheEmpty( $itemId );
		$this->mockInternalLookupWithContent( $itemId, $enTerm, $getOne );

		$lookup = $this->getLookup();

		$this->assertEquals(
			[ 'en' => 'cat' ],
			$lookup->$getMultiple( $itemId, [ 'en', 'sv' ] )
		);
	}

	public function provideTermTypes() {
		yield [ TermTypes::TYPE_LABEL ];
		yield [ TermTypes::TYPE_DESCRIPTION ];
	}

	private function mockCacheWithContent( string $term, $itemId ): void {
		$termFallback = new TermFallback( 'en', $term, 'en', 'en' );
		$this->revisionLookup->method( 'lookupLatestRevisionResolvingRedirect' )->willReturn( [ 1, $itemId ] );
		$this->termFallbackCache->expects( $this->atLeastOnce() )->method( 'get' )->willReturn( $termFallback );
	}

	private function mockHasContentLanguage( bool $return ) {
		$this->contentLanguages
			->method( 'hasLanguage' )
			->willReturn( $return );
	}

	private function mockRevisionLookup( $itemId ) {
		$this->revisionLookup
			->method( 'lookupLatestRevisionResolvingRedirect' )
			->willReturn( [ self::ITEM_Q1_REVISION, $itemId ] );
	}

	private function mockCacheEmpty( $itemId ): void {
		$this->mockRevisionLookup( $itemId );

		$this->termFallbackCache
			->expects( $this->atLeastOnce() )
			->method( 'get' )
			->willReturn( TermFallbackCacheFacade::NO_VALUE );
	}

	private function mockCacheSetExpectation(
		$termFallback,
		$targetEntityId,
		$revisionId,
		$languageCode,
		$termType = TermTypes::TYPE_LABEL
	): void {

		$this->termFallbackCache
			->expects( $this->once() )
			->method( 'set' )
			->with( $termFallback, $targetEntityId, $revisionId, $languageCode, $termType );
	}

	private function mockInternalLookupWithContent(
		$itemId,
		?TermFallback $termFallback,
		string $methodName
	): void {

		$this->factoryReturnLookup->expects( $this->once() )
			->method( $methodName )
			->with( $itemId )
			->willReturn( $termFallback );

		$this->lookupFactory->expects( $this->once() )
			->method( 'newLabelDescriptionLookup' )
			->willReturn( $this->factoryReturnLookup );
	}
}
