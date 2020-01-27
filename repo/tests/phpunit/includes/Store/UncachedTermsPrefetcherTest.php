<?php

namespace Wikibase\Repo\Tests\Store;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\RedirectResolvingLatestRevisionLookup;
use Wikibase\Lib\Store\TermCacheKeyBuilder;
use Wikibase\Lib\Store\UncachedTermsPrefetcher;
use Wikibase\Lib\Tests\FakeCache;

/**
 * @covers \Wikibase\Lib\Store\UncachedTermsPrefetcher
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class UncachedTermsPrefetcherTest extends TestCase {

	use TermCacheKeyBuilder;

	const TEST_REVISION = 666;
	const TEST_LANGUAGE = 'en';

	/** @var PrefetchingTermLookup|MockObject */
	private $prefetchingLookup;

	/** @var RedirectResolvingLatestRevisionLookup|MockObject */
	private $redirectResolvingRevisionLookup;

	protected function setUp(): void {
		parent::setUp();

		$this->prefetchingLookup = $this->createMock( PrefetchingTermLookup::class );
		$this->redirectResolvingRevisionLookup = $this->newStubRedirectResolvingRevisionLookup();
	}

	public function testGivenAllTermsAreCached_doesNotPrefetch() {
		$cache = new FakeCache();
		$cache->set( $this->buildTestCacheKey( 'Q123', 'label' ), 'some label' );
		$this->prefetchingLookup->expects( $this->never() )->method( $this->anything() );

		$this->newTermsPrefetcher()
			->prefetchUncached( $cache, [ new ItemId( 'Q123' ) ], [ 'label' ], [ 'en' ] );
	}

	public function testGivenNothingCached_prefetchesAndCaches() {
		$cache = new FakeCache();
		$termTypes = [ 'label', 'description' ];
		$languages = [ 'en' ];
		$q123 = new ItemId( 'Q123' );
		$q123Label = 'meow';
		$q123Description = 'cat sound';
		$q321 = new ItemId( 'Q321' );
		$q321Label = 'quack';
		$q321Description = 'duck sound';

		$this->prefetchingLookup->expects( $this->once() )
			->method( 'prefetchTerms' )
			->with( [ $q123, $q321 ], $termTypes, $languages );
		$this->prefetchingLookup->expects( $this->any() )
			->method( 'getPrefetchedTerm' )
			->withConsecutive(
				[ $q123, 'label', $languages[0] ],
				[ $q123, 'description', $languages[0] ],
				[ $q321, 'label', $languages[0] ],
				[ $q321, 'description', $languages[0] ]
			)
			->willReturnOnConsecutiveCalls(
				$q123Label,
				$q123Description,
				$q321Label,
				$q321Description
			);

		$this->newTermsPrefetcher()
			->prefetchUncached( $cache, [ $q123, $q321 ], $termTypes, $languages );

		$this->assertSame(
			$q123Label,
			$cache->get( $this->buildTestCacheKey( 'Q123', 'label' ) )
		);
		$this->assertSame(
			$q123Description,
			$cache->get( $this->buildTestCacheKey( 'Q123', 'description' ) )
		);
		$this->assertSame(
			$q321Label,
			$cache->get( $this->buildTestCacheKey( 'Q321', 'label' ) )
		);
		$this->assertSame(
			$q321Description,
			$cache->get( $this->buildTestCacheKey( 'Q321', 'description' ) )
		);
	}

	public function testGivenTermsCachedForSomeEntities_looksUpOnlyUncachedOnes() {
		$cache = new FakeCache();
		$cachedItemId = new ItemId( 'Q123' );
		$uncachedItemId = new ItemId( 'Q321' );
		$languages = [ 'en' ];
		$termTypes = [ 'label' ];

		$cache->set( $this->buildTestCacheKey( 'Q123', 'label' ), 'whatever' );

		$this->prefetchingLookup->expects( $this->once() )
			->method( 'prefetchTerms' )
			->with( [ $uncachedItemId ], $termTypes, $languages );

		$this->newTermsPrefetcher()
			->prefetchUncached( $cache, [ $cachedItemId, $uncachedItemId ], $termTypes, $languages );
	}

	private function newTermsPrefetcher() {
		return new UncachedTermsPrefetcher(
			$this->prefetchingLookup,
			$this->redirectResolvingRevisionLookup
		);
	}

	/**
	 * @return MockObject|RedirectResolvingLatestRevisionLookup
	 */
	protected function newStubRedirectResolvingRevisionLookup() {
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

}
