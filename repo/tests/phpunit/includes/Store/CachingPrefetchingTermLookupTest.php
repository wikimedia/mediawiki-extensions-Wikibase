<?php

namespace Wikibase\Repo\Tests\Store;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;
use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\TermTypes;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Lib\Store\CachingPrefetchingTermLookup;
use Wikibase\Lib\Store\RedirectResolvingLatestRevisionLookup;
use Wikibase\Lib\Store\TermCacheKeyBuilder;
use Wikibase\Lib\Tests\FakeCache;

/**
 * @covers \Wikibase\Lib\Store\CachingPrefetchingTermLookup
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class CachingPrefetchingTermLookupTest extends TestCase {

	use TermCacheKeyBuilder;

	private const TEST_REVISION = 666;
	private const TEST_LANGUAGE = 'en';

	/** @var CacheInterface */
	private $cache;

	/** @var RedirectResolvingLatestRevisionLookup|MockObject */
	private $redirectResolvingRevisionLookup;
	private $termLanguages;

	/** @var MockObject|PrefetchingTermLookup */
	private $lookup;

	protected function setUp(): void {
		parent::setUp();

		$this->lookup = $this->createMock( PrefetchingTermLookup::class );
		$this->cache = new FakeCache();
		$this->redirectResolvingRevisionLookup = $this->newStubRedirectResolvingRevisionLookup();
		$this->termLanguages = new StaticContentLanguages( [ 'en', 'de', 'fr' ] );
	}

	public function testPrefetchTermsNoCache(): void {
		$entities = [ new ItemId( 'Q123' ), new ItemId( 'Q321' ) ];
		$termTypes = [ TermTypes::TYPE_LABEL, TermTypes::TYPE_DESCRIPTION, TermTypes::TYPE_ALIAS ];
		$languages = [ 'en', 'de' ];

		$this->lookup->expects( $this->once() )
			->method( 'prefetchTerms' )
			->with( $entities, $termTypes, $languages );

		$this->cache = $this->newMockCacheExpectingKeys( $this->buildExpectedCacheKeys( [ 'Q123', 'Q321' ], $termTypes, $languages ) );

		$this->newPrefetchingTermLookup()
			->prefetchTerms( $entities, $termTypes, $languages );
	}

	public function testGetPrefetchedAliasesFromBuffer(): void {
		$entity = new ItemId( 'Q123' );
		$entities = [ $entity ];
		$termType = TermTypes::TYPE_ALIAS;
		$termTypes = [ $termType ];
		$languages = [ 'en' ];
		$expectedTerm = [ 'meow' ];

		$cache = $this->createMock( CacheInterface::class );

		// buffer gets set from cache using prefetchTerms
		$cache->expects( $this->once() )->method( 'getMultiple' )
		->willReturn( [
			$this->buildTestCacheKey( 'Q123', $termType, 'en' ) => $expectedTerm,
		] );

		// cache should not be called
		$cache->expects( $this->never() )->method( 'get' );

		$cachingLookup = new CachingPrefetchingTermLookup(
			$cache,
			$this->lookup,
			$this->redirectResolvingRevisionLookup,
			$this->termLanguages
		);

		// add to buffer
		$cachingLookup->prefetchTerms( $entities, $termTypes, $languages );

		$this->assertSame( $expectedTerm, $cachingLookup->getPrefetchedAliases( $entity, 'en' ) );
	}

	public function testGetPrefetchedAliasesFromLookup(): void {
		$entity = new ItemId( 'Q123' );
		$expectedAlias = [ 'meow' ];

		$cache = $this->createMock( CacheInterface::class );

		// cache should not be called
		$cache->expects( $this->never() )->method( 'get' );

		$this->lookup->expects( $this->once() )->method( 'getPrefetchedAliases' )
		->with( $entity, 'en' )
		->willReturn( $expectedAlias );

		$cachingLookup = new CachingPrefetchingTermLookup(
			$cache,
			$this->lookup,
			$this->redirectResolvingRevisionLookup,
			$this->termLanguages
		);

		$this->assertSame( $expectedAlias, $cachingLookup->getPrefetchedAliases( $entity, 'en' ) );
	}

	public function testGivenInvalidLanguageCode_getLabelReturnsNullAndDoesNotUseCache() {
		$this->cache = $this->newNeverCalledCache();
		$this->termLanguages = new StaticContentLanguages( [ 'en', 'de', 'fr' ] );

		$this->assertNull( $this->newPrefetchingTermLookup()
			->getLabel( new ItemId( 'Q123' ), 'language-that-doesnt-exist' ) );
	}

	public function testPrefetchTermsAllCached(): void {

		$entities = [ new ItemId( 'Q123' ), new ItemId( 'Q321' ) ];
		$termTypes = [ TermTypes::TYPE_LABEL ];
		$languages = [ 'en' ];
		$labelOne = 'label one';
		$labelTwo = 'label two';

		$cache = $this->createMock( CacheInterface::class );
		$cache->expects( $this->once() )->method( 'getMultiple' )
		->with( [
			'Q123_666_en_label',
			'Q321_666_en_label',
		] )->willReturn( [
			'Q123_666_en_label' => $labelOne,
			'Q321_666_en_label' => $labelTwo,
		] );

		// assert that the lookup is not asked to talk to the database
		$this->lookup->expects( $this->once() )->method( 'prefetchTerms' )
		->with( [], [], [] );

		// assert that nothing new is stored in the cache
		$cache->expects( $this->once() )->method( 'setMultiple' )
		->with( [] );

		$cachingLookup = new CachingPrefetchingTermLookup(
			$cache,
			$this->lookup,
			$this->redirectResolvingRevisionLookup,
			$this->termLanguages
		);

		$cachingLookup->prefetchTerms( $entities, $termTypes, $languages );

		$actualItemOne = $cachingLookup->getPrefetchedTerm( $entities[0], TermTypes::TYPE_LABEL, 'en' );
		$actualItemTwo = $cachingLookup->getPrefetchedTerm( $entities[1], TermTypes::TYPE_LABEL, 'en' );

		$this->assertSame( $labelOne, $actualItemOne, 'Term seems to not be buffered locally' );
		$this->assertSame( $labelTwo, $actualItemTwo, 'Term seems to not be buffered locally' );
	}

	private function buildExpectedCacheKeys( array $ids, array $termTypes, array $languages ): array {
		$keys = [];

		foreach ( $ids as $itemId ) {
			foreach ( $termTypes as $termType ) {
				foreach ( $languages as $language ) {
					$keys[] = $this->buildTestCacheKey( $itemId, $termType, $language );
				}
			}
		}

		return $keys;
	}

	public function testPrefetchTermsOnlyPrefetchesValidTermLanguages() {
		$entities = [ new ItemId( 'Q123' ), new ItemId( 'Q321' ) ];
		$termTypes = [ TermTypes::TYPE_LABEL, TermTypes::TYPE_DESCRIPTION, TermTypes::TYPE_ALIAS ];
		$allLanguages = [ 'en', 'de', 'catlang' ];
		$validTermLanguages = [ 'en', 'de' ];

		$this->termLanguages = new StaticContentLanguages( $validTermLanguages );

		$this->lookup->expects( $this->once() )
			->method( 'prefetchTerms' )
			->with( $entities, $termTypes, $validTermLanguages );

		$this->newPrefetchingTermLookup()
			->prefetchTerms( $entities, $termTypes, $allLanguages );
	}

	public function testPrefetchTermUnresolvedRedirect() {
		$entityIdResolved = new ItemId( 'Q1' );
		$entityIdUnresolved = new ItemId( 'Q2' );
		$termType = TermTypes::TYPE_LABEL;
		$languageCode = 'en';

		$revisionAndRedirectResolver = $this->createMock( RedirectResolvingLatestRevisionLookup::class );
		$revisionAndRedirectResolver->expects( $this->exactly( 2 ) )
			->method( 'lookupLatestRevisionResolvingRedirect' )
			->willReturn( [ self::TEST_REVISION, $entityIdResolved ], null );

		$cache = $this->createMock( CacheInterface::class );
		$cache->expects( $this->once() )->method( 'getMultiple' )->with( [
			'Q1_666_en_label',
		] )->willReturn( [ 'Q1_666_en_label' => 'whatever' ] );
		$this->lookup->expects( $this->once() )->method( 'prefetchTerms' )->with( [], [], [] );
		$lookup = new CachingPrefetchingTermLookup(
			$cache,
			$this->lookup,
			$revisionAndRedirectResolver,
			$this->termLanguages
		);

		$lookup->prefetchTerms( [ $entityIdResolved, $entityIdUnresolved ], [ $termType ], [ $languageCode ] );

		$this->assertNull(
			$lookup->getPrefetchedTerm( $entityIdUnresolved, $termType, $languageCode ),
			'If the redirect cannot be resolved, the prefetch should be ignored.'
		);
	}

	public function getTermDataProvider() {
		return [ [ TermTypes::TYPE_LABEL, 'getLabel' ], [ TermTypes::TYPE_DESCRIPTION, 'getDescription' ] ];
	}

	/**
	 * @dataProvider getTermDataProvider
	 */
	public function testGetTerm( $termType, $getTermMethodName ) {
		$label = 'meow';
		$this->cache->set( $this->buildTestCacheKey( 'Q123', $termType ), $label );

		$this->assertSame(
			$label,
			$this->newPrefetchingTermLookup()
				->$getTermMethodName( new ItemId( 'Q123' ), 'en' )
		);
	}

	/**
	 * @dataProvider getTermDataProvider
	 */
	public function testGetNonExisingTerm( $termType, $getTermMethodName ) {
		$this->cache->set( $this->buildTestCacheKey( 'Q123', $termType ), false );

		$this->assertNull(
			$this->newPrefetchingTermLookup()
				->$getTermMethodName( new ItemId( 'Q123' ), 'en' )
		);
	}

	/**
	 * @dataProvider getTermDataProvider
	 */
	public function testGetTermBuffered( $termType, $getTermMethodName ) {
		$entity = new ItemId( 'Q123' );
		$entities = [ $entity ];
		$termTypes = [ $termType ];
		$languages = [ 'en' ];
		$expectedTerm = 'meow';

		$cache = $this->createMock( CacheInterface::class );

		// buffer gets set from cache using prefetchTerms
		$cache->expects( $this->once() )->method( 'getMultiple' )
		->willReturn( [
			$this->buildTestCacheKey( 'Q123', $termType, 'en' ) => $expectedTerm,
		] );

		// cache should not be called
		$cache->expects( $this->never() )->method( 'get' );

		$cachingLookup = new CachingPrefetchingTermLookup(
			$cache,
			$this->lookup,
			$this->redirectResolvingRevisionLookup,
			$this->termLanguages
		);

		// add to buffer
		$cachingLookup->prefetchTerms( $entities, $termTypes, $languages );

		$this->assertSame( $expectedTerm, $cachingLookup->$getTermMethodName( $entity, 'en' ) );
	}

	/**
	 * @dataProvider getTermDataProvider
	 */
	public function testGetTermThroughLookup( $termType, $getTermMethodName ) {
		$entity = new ItemId( 'Q123' );
		$entities = [ $entity ];
		$termTypes = [ $termType ];

		$expectedLabel = 'meow';

		$cache = $this->createMock( CacheInterface::class );

		$this->lookup->expects( $this->once() )->method( $getTermMethodName )
		->with( $entity, 'en' )
		->willReturn( $expectedLabel );

		// cache should not be called
		$cache->expects( $this->once() )->method( 'get' )
		->with( $this->buildTestCacheKey( 'Q123', $termType, 'en' ) )
		->willReturn( null );

		// cache should be set with fresh term
		$cache->expects( $this->once() )->method( 'set' )
		->with( $this->buildTestCacheKey( 'Q123', $termType, 'en' ), $expectedLabel );

		$cachingLookup = new CachingPrefetchingTermLookup(
			$cache,
			$this->lookup,
			$this->redirectResolvingRevisionLookup,
			$this->termLanguages
		);

		$this->assertSame( $expectedLabel, $cachingLookup->$getTermMethodName( $entity, 'en' ) );

		$this->assertEquals(
			$expectedLabel,
			$cachingLookup->getPrefetchedTerm( $entity, $termType, 'en' )
		);
	}

	/**
	 * @dataProvider getTermDataProvider
	 */
	public function testGetNonExisingTermFromLookup( string $termType, string $getTermMethodName ) {
		$entity = new ItemId( 'Q123' );

		$cache = $this->createMock( CacheInterface::class );

		$this->lookup->expects( $this->once() )->method( $getTermMethodName )
			->with( $entity, 'en' )
			->willReturn( null );

		$cache->expects( $this->once() )->method( 'get' )
			->with( $this->buildTestCacheKey( 'Q123', $termType, 'en' ) )
			->willReturn( null );

		$cache->expects( $this->once() )->method( 'set' )
			->with( $this->buildTestCacheKey( 'Q123', $termType, 'en' ), false );

		$cachingLookup = new CachingPrefetchingTermLookup(
			$cache,
			$this->lookup,
			$this->redirectResolvingRevisionLookup,
			$this->termLanguages
		);

		$this->assertNull(
			$cachingLookup->$getTermMethodName( new ItemId( 'Q123' ), 'en' )
		);
	}

	public function testGetTermUnresolvedRedirect() {
		$entityIdUnresolved = new ItemId( 'Q1' );
		$languageCode = 'en';

		$revisionAndRedirectResolver = $this->createStub( RedirectResolvingLatestRevisionLookup::class );
		$revisionAndRedirectResolver->method( 'lookupLatestRevisionResolvingRedirect' )
			->willReturn( null );
		$cache = $this->createMock( CacheInterface::class );
		$cache->expects( $this->never() )->method( 'set' );

		$lookup = new CachingPrefetchingTermLookup(
			$cache,
			$this->lookup,
			$revisionAndRedirectResolver,
			$this->termLanguages
		);

		$this->assertNull( $lookup->getLabel( $entityIdUnresolved, $languageCode ) );
	}

	public function testGivenInvalidLanguageCode_getDescriptionReturnsNullAndDoesNotUseCache() {
		$this->cache = $this->newNeverCalledCache();
		$this->termLanguages = new StaticContentLanguages( [ 'en', 'de', 'fr' ] );

		$this->assertNull( $this->newPrefetchingTermLookup()
			->getDescription( new ItemId( 'Q123' ), 'language-that-doesnt-exist' ) );
	}

	/**
	 * @dataProvider getMultipleTermsByLanguageDataProvider
	 */
	public function testGetLabelsAndGetDescriptions( $termType, $getTermMethodName ) {
		$entity = new ItemId( 'Q123' );
		$this->cache->set( $this->buildTestCacheKey( 'Q123', $termType, 'en' ), 'meow' );
		$this->cache->set( $this->buildTestCacheKey( 'Q123', $termType, 'de' ), 'miau' );

		$cachingLookup = $this->newPrefetchingTermLookup();

		$this->assertEquals(
			[ 'en' => 'meow', 'de' => 'miau' ],
			$cachingLookup->$getTermMethodName( $entity, [ 'de', 'en' ] )
		);

		$this->assertEquals(
			'meow',
			$cachingLookup->getPrefetchedTerm( $entity, $termType, 'en' )
		);

		$this->assertEquals(
			'miau',
			$cachingLookup->getPrefetchedTerm( $entity, $termType, 'de' )
		);
	}

	/**
	 * @dataProvider getMultipleTermsByLanguageDataProvider
	 */
	public function testGetTermsAllBuffered( $termType, $getTermMethodName ) {
		// assert that nothing new is stored in the cache
		$cache = $this->createMock( CacheInterface::class );

		// buffer gets set from cache using prefetchTerms
		$cache->expects( $this->once() )->method( 'getMultiple' )
		->willReturn( [
			$this->buildTestCacheKey( 'Q123', $termType, 'en' ) => 'meow',
			$this->buildTestCacheKey( 'Q123', $termType, 'de' ) => 'miau',
		] );

		$cachingLookup = new CachingPrefetchingTermLookup(
			$cache,
			$this->lookup,
			$this->redirectResolvingRevisionLookup,
			$this->termLanguages
		);

		$entity = new ItemId( 'Q123' );
		$entities = [ $entity ];
		$termTypes = [ $termType ];
		$languages = [ 'en', 'de' ];

		// add to termbuffer
		$cachingLookup->prefetchTerms( $entities, $termTypes, $languages );

		$this->assertEquals(
			[ 'en' => 'meow', 'de' => 'miau' ],
			$cachingLookup->$getTermMethodName( $entity, [ 'de', 'en' ] )
		);
	}

	/**
	 * @dataProvider getMultipleTermsByLanguageDataProvider
	 */
	public function testGetTermsThroughLookup( $termType, $getTermMethodName ) {
		$entity = new ItemId( 'Q123' );

		$this->lookup->expects( $this->once() )->method( $getTermMethodName )
		->with( $entity, [ 'de', 'en' ] )
		->willReturn( [
			'en' => 'meow',
			'de' => 'miau',
		] );

		$cachingLookup = $this->newPrefetchingTermLookup();

		$this->assertEquals(
			[ 'en' => 'meow', 'de' => 'miau' ],
			$cachingLookup->$getTermMethodName( $entity,
				[ 'de', 'en' ]
			)
		);

		$this->assertEquals(
			'miau',
			$this->cache->get( $this->buildTestCacheKey( 'Q123', $termType, 'de' ) )
		);

		$this->assertEquals(
			'meow',
			$this->cache->get( $this->buildTestCacheKey( 'Q123', $termType, 'en' ) )
		);

		$this->assertEquals(
			'meow',
			$cachingLookup->getPrefetchedTerm( $entity, $termType, 'en' )
		);

		$this->assertEquals(
			'miau',
			$cachingLookup->getPrefetchedTerm( $entity, $termType, 'de' )
		);
	}

	/**
	 * @dataProvider getMultipleTermsByLanguageDataProvider
	 */
	public function testGetLabelsThroughBufferCacheAndLookup( $termType, $getTermMethodName ) {
		$entity = new ItemId( 'Q123' );
		$entities = [ $entity ];
		$termTypes = [ $termType ];

		$cacheKeyGerman = $this->buildTestCacheKey( 'Q123', $termType, 'de' );
		$cacheKeyEnglish = $this->buildTestCacheKey( 'Q123', $termType, 'en' );
		$cacheKeyFrench = $this->buildTestCacheKey( 'Q123', $termType, 'fr' );

		$this->lookup->expects( $this->once() )->method( $getTermMethodName )
		->with( $entity, [ 'fr' ] )
		->willReturn( [
			'fr' => 'miaule',
		] );

		$cache = $this->createMock( CacheInterface::class );
		$cache->expects( $this->exactly( 2 ) )->method( 'getMultiple' )
		->withConsecutive(
			[ [ $cacheKeyEnglish ] ], // first call populates buffer
			[ [ $cacheKeyGerman, $cacheKeyFrench ] ]
		)
		->willReturnOnConsecutiveCalls(
			[ $cacheKeyEnglish => 'meow' ],
			[ $cacheKeyGerman => 'miau', $cacheKeyFrench => null ]
		);

		$cachingLookup = new CachingPrefetchingTermLookup(
			$cache,
			$this->lookup,
			$this->redirectResolvingRevisionLookup,
			$this->termLanguages
		);

		// populate buffer
		$cachingLookup->prefetchTerms( $entities, $termTypes, [ 'en' ] );

		$this->assertEquals(
			[ 'en' => 'meow', 'de' => 'miau', 'fr' => 'miaule' ],
			$cachingLookup->$getTermMethodName( $entity,
				[ 'de', 'en', 'fr' ]
			)
		);
	}

	/**
	 * @dataProvider getMultipleTermsByLanguageDataProvider
	 */
	public function testGetMissingLabelsAndGetDescriptions( string $termType, string $getTermMethodName ) {
		$entity = new ItemId( 'Q123' );
		$this->cache->set( $this->buildTestCacheKey( 'Q123', $termType, 'en' ), false );
		$this->cache->set( $this->buildTestCacheKey( 'Q123', $termType, 'de' ), false );

		$cachingLookup = $this->newPrefetchingTermLookup();

		$this->assertEquals(
			[],
			$cachingLookup->$getTermMethodName( $entity, [ 'de', 'en' ] )
		);

		$this->assertFalse(
			$cachingLookup->getPrefetchedTerm( $entity, $termType, 'en' )
		);

		$this->assertFalse(
			$cachingLookup->getPrefetchedTerm( $entity, $termType, 'de' )
		);
	}

	/**
	 * @dataProvider getMultipleTermsByLanguageDataProvider
	 */
	public function testGetMissingLabelsAndGetDescriptionsFromLookup( string $termType, string $getTermMethodName ) {
		$entity = new ItemId( 'Q123' );

		$this->lookup->expects( $this->once() )->method( $getTermMethodName )
			->with( $entity, [ 'de', 'en' ] )
			->willReturn( [] );

		$cachingLookup = $this->newPrefetchingTermLookup();

		$this->assertEquals(
			[],
			$cachingLookup->$getTermMethodName( $entity,
				[ 'de', 'en' ]
			)
		);

		$this->assertFalse(
			$this->cache->get( $this->buildTestCacheKey( 'Q123', $termType, 'de' ) )
		);

		$this->assertFalse(
			$this->cache->get( $this->buildTestCacheKey( 'Q123', $termType, 'en' ) )
		);

		$this->assertFalse(
			$cachingLookup->getPrefetchedTerm( $entity, $termType, 'en' )
		);

		$this->assertFalse(
			$cachingLookup->getPrefetchedTerm( $entity, $termType, 'de' )
		);
	}

	public function getMultipleTermsByLanguageDataProvider() {
		return [ [ TermTypes::TYPE_LABEL, 'getLabels' ], [ TermTypes::TYPE_DESCRIPTION, 'getDescriptions' ] ];
	}

	/**
	 * @dataProvider getMultipleTermsByLanguageDataProvider
	 */
	public function testGivenInvalidLanguageCode_getTermDoesNotCallGetMultipleForInvalidLanguage( $termType, $getTermMethodName ) {
		$requestedLanguages = [ 'en', 'language-that-doesnt-exist' ];

		$this->cache = $this->newMockCacheExpectingKeys(
			[ $this->buildTestCacheKey( 'Q123', $termType, 'en' ) ],
			false
		);
		$this->termLanguages = new StaticContentLanguages( [ 'en', 'de', 'fr' ] );

		$this->newPrefetchingTermLookup()
			->$getTermMethodName( new ItemId( 'Q123' ), $requestedLanguages );
	}

	private function newPrefetchingTermLookup() {
		return new CachingPrefetchingTermLookup(
			$this->cache,
			$this->lookup,
			$this->redirectResolvingRevisionLookup,
			$this->termLanguages
		);
	}

	/**
	 * @return MockObject|RedirectResolvingLatestRevisionLookup
	 */
	private function newStubRedirectResolvingRevisionLookup() {
		$revisionAndRedirectResolver = $this->createMock( RedirectResolvingLatestRevisionLookup::class );
		$revisionAndRedirectResolver->method( 'lookupLatestRevisionResolvingRedirect' )
			->willReturnCallback( function ( EntityId $id ) {
				return [ self::TEST_REVISION, $id ];
			} );

		return $revisionAndRedirectResolver;
	}

	private function buildTestCacheKey(
		string $itemId,
		string $termType,
		string $language = self::TEST_LANGUAGE,
		int $revision = self::TEST_REVISION
	) {
		return $this->buildCacheKey( new ItemId( $itemId ), $revision, $language, $termType );
	}

	private function newNeverCalledCache() {
		$mockCache = $this->createMock( CacheInterface::class );
		$mockCache->expects( $this->never() )
			->method( $this->anything() );

		return $mockCache;
	}

	private function newMockCacheExpectingKeys( array $expectedCacheKeys, $fillValue = null ): CacheInterface {
		$cache = $this->createMock( CacheInterface::class );
		$cache->expects( $this->once() )
			->method( 'getMultiple' )
			->with( $expectedCacheKeys )
			->willReturn( array_fill_keys( $expectedCacheKeys, $fillValue ) );

		return $cache;
	}

}
