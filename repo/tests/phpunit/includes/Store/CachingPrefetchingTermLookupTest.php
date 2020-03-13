<?php

namespace Wikibase\Repo\Tests\Store;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\TermTypes;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Lib\Store\CachingPrefetchingTermLookup;
use Wikibase\Lib\Store\RedirectResolvingLatestRevisionLookup;
use Wikibase\Lib\Store\TermCacheKeyBuilder;
use Wikibase\Lib\Store\UncachedTermsPrefetcher;
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

	const TEST_REVISION = 666;
	const TEST_LANGUAGE = 'en';

	/** @var UncachedTermsPrefetcher|MockObject */
	private $termsPrefetcher;

	/** @var CacheInterface */
	private $cache;

	/** @var RedirectResolvingLatestRevisionLookup|MockObject */
	private $redirectResolvingRevisionLookup;
	private $termLanguages;

	protected function setUp(): void {
		parent::setUp();

		$this->termsPrefetcher = $this->createMock( UncachedTermsPrefetcher::class );
		$this->cache = new FakeCache();
		$this->redirectResolvingRevisionLookup = $this->newStubRedirectResolvingRevisionLookup();
		$this->termLanguages = new StaticContentLanguages( [ 'en', 'de', 'fr' ] );
	}

	public function testPrefetchTerms() {
		$entities = [ new ItemId( 'Q123' ), new ItemId( 'Q321' ) ];
		$termTypes = [ TermTypes::TYPE_LABEL, TermTypes::TYPE_DESCRIPTION, TermTypes::TYPE_ALIAS ];
		$languages = [ 'en', 'de' ];

		$this->termsPrefetcher->expects( $this->once() )
			->method( 'prefetchUncached' )
			->with( $this->cache, $entities, $termTypes, $languages );

		$this->newPrefetchingTermLookup()
			->prefetchTerms( $entities, $termTypes, $languages );
	}

	public function testPrefetchTermsOnlyPrefetchesValidTermLanguages() {
		$entities = [ new ItemId( 'Q123' ), new ItemId( 'Q321' ) ];
		$termTypes = [ TermTypes::TYPE_LABEL, TermTypes::TYPE_DESCRIPTION, TermTypes::TYPE_ALIAS ];
		$allLanguages = [ 'en', 'de', 'catlang' ];
		$validTermLanguages = [ 'en', 'de' ];

		$this->termLanguages = new StaticContentLanguages( $validTermLanguages );

		$this->termsPrefetcher->expects( $this->once() )
			->method( 'prefetchUncached' )
			->with( $this->cache, $entities, $termTypes, $validTermLanguages );

		$this->newPrefetchingTermLookup()
			->prefetchTerms( $entities, $termTypes, $allLanguages );
	}

	public function testGetPrefetchedLabel() {
		$label = 'meow';
		$this->cache->set( $this->buildTestCacheKey( 'Q123', TermTypes::TYPE_LABEL ), $label );

		$this->assertSame(
			$label,
			$this->newPrefetchingTermLookup()
				->getPrefetchedTerm( new ItemId( 'Q123' ), TermTypes::TYPE_LABEL, 'en' )
		);
	}

	public function testGetPrefetchedDescription() {
		$description = 'some description';
		$this->cache->set( $this->buildTestCacheKey( 'Q789', TermTypes::TYPE_DESCRIPTION ), $description );

		$this->assertSame(
			$description,
			$this->newPrefetchingTermLookup()
				->getPrefetchedTerm( new ItemId( 'Q789' ), TermTypes::TYPE_DESCRIPTION, 'en' )
		);
	}

	public function testGetPrefetchedAlias() {
		$aliases = [ 'foo', 'bar', 'baz' ];
		$this->cache->set( $this->buildTestCacheKey( 'Q123', TermTypes::TYPE_ALIAS ), $aliases );

		$this->assertSame(
			$aliases[0],
			$this->newPrefetchingTermLookup()
				->getPrefetchedTerm( new ItemId( 'Q123' ), TermTypes::TYPE_ALIAS, 'en' )
		);
	}

	public function testGivenInvalidLanguageCode_getPrefetchedTermReturnsNullAndDoesNotUseCache() {
		$this->cache = $this->newNeverCalledCache();

		$this->assertNull( $this->newPrefetchingTermLookup()
				->getPrefetchedTerm( new ItemId( 'Q123' ), TermTypes::TYPE_LABEL, 'language-that-doesnt-exist' ) );
	}

	public function testGetLabel() {
		$label = 'meow';
		$this->cache->set( $this->buildTestCacheKey( 'Q123', TermTypes::TYPE_LABEL ), $label );

		$this->assertSame(
			$label,
			$this->newPrefetchingTermLookup()
				->getLabel( new ItemId( 'Q123' ), 'en' )
		);
	}

	public function testGivenInvalidLanguageCode_getLabelReturnsNullAndDoesNotUseCache() {
		$this->cache = $this->newNeverCalledCache();
		$this->termLanguages = new StaticContentLanguages( [ 'en', 'de', 'fr' ] );

		$this->assertNull( $this->newPrefetchingTermLookup()
			->getLabel( new ItemId( 'Q123' ), 'language-that-doesnt-exist' ) );
	}

	public function testGetDescription() {
		$description = 'some description';
		$this->cache->set( $this->buildTestCacheKey( 'Q123', TermTypes::TYPE_DESCRIPTION ), $description );

		$this->assertSame(
			$description,
			$this->newPrefetchingTermLookup()
				->getDescription( new ItemId( 'Q123' ), 'en' )
		);
	}

	public function testGivenInvalidLanguageCode_getDescriptionReturnsNullAndDoesNotUseCache() {
		$this->cache = $this->newNeverCalledCache();
		$this->termLanguages = new StaticContentLanguages( [ 'en', 'de', 'fr' ] );

		$this->assertNull( $this->newPrefetchingTermLookup()
			->getDescription( new ItemId( 'Q123' ), 'language-that-doesnt-exist' ) );
	}

	public function testGetPrefetchedAliases() {
		$aliases = [ 'foo', 'bar', 'baz' ];
		$this->cache->set( $this->buildTestCacheKey( 'Q123', TermTypes::TYPE_ALIAS ), $aliases );

		$this->assertSame(
			$aliases,
			$this->newPrefetchingTermLookup()
				->getPrefetchedAliases( new ItemId( 'Q123' ), 'en' )
		);
	}

	public function testGivenInvalidLanguageCode_getPrefetchedAliasesReturnsNullAndDoesNotUseCache() {
		$this->cache = $this->newNeverCalledCache();
		$this->termLanguages = new StaticContentLanguages( [ 'en', 'de', 'fr' ] );

		$this->assertNull( $this->newPrefetchingTermLookup()
			->getPrefetchedAliases( new ItemId( 'Q123' ), 'language-that-doesnt-exist' ) );
	}

	public function testGetLabels() {
		$this->cache->set( $this->buildTestCacheKey( 'Q123', TermTypes::TYPE_LABEL, 'en' ), 'meow' );
		$this->cache->set( $this->buildTestCacheKey( 'Q123', TermTypes::TYPE_LABEL, 'de' ), 'miau' );

		$this->assertEquals(
			[ 'en' => 'meow', 'de' => 'miau' ],
			$this->newPrefetchingTermLookup()->getLabels( new ItemId( 'Q123' ), [ 'de', 'en' ] )
		);
	}

	public function testGivenInvalidLanguageCode_getLabelsDoesNotCallGetMultipleForInvalidLanguage() {
		$requestedLanguages = [ 'en', 'language-that-doesnt-exist' ];

		$this->cache = $this->newMockCacheExpectingKey(
			$this->buildTestCacheKey( 'Q123', TermTypes::TYPE_LABEL, 'en' )
		);
		$this->termLanguages = new StaticContentLanguages( [ 'en', 'de', 'fr' ] );

		$this->newPrefetchingTermLookup()
			->getLabels( new ItemId( 'Q123' ), $requestedLanguages );
	}

	public function testGetDescriptions() {
		$this->cache->set(
			$this->buildTestCacheKey( 'Q123', TermTypes::TYPE_DESCRIPTION, 'en' ),
			'cat sound'
		);
		$this->cache->set(
			$this->buildTestCacheKey( 'Q123', TermTypes::TYPE_DESCRIPTION, 'de' ),
			'Katzengeräusch'
		);

		$this->assertEquals(
			[ 'en' => 'cat sound', 'de' => 'Katzengeräusch' ],
			$this->newPrefetchingTermLookup()->getDescriptions( new ItemId( 'Q123' ), [ 'de', 'en' ] )
		);
	}

	public function testGivenInvalidLanguageCode_getDescriptionsDoesNotCallGetMultipleForInvalidLanguage() {
		$requestedLanguages = [ 'en', 'language-that-doesnt-exist' ];

		$this->cache = $this->newMockCacheExpectingKey(
			$this->buildTestCacheKey( 'Q123', TermTypes::TYPE_DESCRIPTION, 'en' )
		);
		$this->termLanguages = new StaticContentLanguages( [ 'en', 'de', 'fr' ] );

		$this->newPrefetchingTermLookup()
			->getDescriptions( new ItemId( 'Q123' ), $requestedLanguages );
	}

	private function newPrefetchingTermLookup() {
		return new CachingPrefetchingTermLookup(
			$this->cache,
			$this->termsPrefetcher,
			$this->redirectResolvingRevisionLookup,
			$this->termLanguages
		);
	}

	/**
	 * @return MockObject|RedirectResolvingLatestRevisionLookup
	 */
	private function newStubRedirectResolvingRevisionLookup() {
		$revisionAndRedirectResolver = $this->createMock( RedirectResolvingLatestRevisionLookup::class );
		$revisionAndRedirectResolver->expects( $this->any() )
			->method( 'lookupLatestRevisionResolvingRedirect' )
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

	private function newMockCacheExpectingKey( string $expectedCacheKey ): CacheInterface {
		$cache = $this->createMock( CacheInterface::class );
		$cache->expects( $this->once() )
			->method( 'getMultiple' )
			->with( [ $expectedCacheKey ] )
			->willReturn( [] );

		return $cache;
	}

}
