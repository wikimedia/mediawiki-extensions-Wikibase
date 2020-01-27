<?php

namespace Wikibase\Repo\Tests\Store;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
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

	protected function setUp(): void {
		parent::setUp();

		$this->termsPrefetcher = $this->createMock( UncachedTermsPrefetcher::class );
		$this->cache = new FakeCache();
		$this->redirectResolvingRevisionLookup = $this->newStubRedirectResolvingRevisionLookup();
	}

	public function testPrefetchTerms() {
		$entities = [ new ItemId( 'Q123' ), new ItemId( 'Q321' ) ];
		$termTypes = [ 'label', 'description', 'alias' ];
		$languages = [ 'en', 'de' ];

		$this->termsPrefetcher->expects( $this->once() )
			->method( 'prefetchUncached' )
			->with( $this->cache, $entities, $termTypes, $languages );

		$this->newPrefetchingTermLookup()
			->prefetchTerms( $entities, $termTypes, $languages );
	}

	public function testGetPrefetchedLabel() {
		$label = 'meow';
		$this->cache->set( $this->buildTestCacheKey( 'Q123', 'label' ), $label );

		$this->assertSame(
			$label,
			$this->newPrefetchingTermLookup()
				->getPrefetchedTerm( new ItemId( 'Q123' ), 'label', 'en' )
		);
	}

	public function testGetPrefetchedDescription() {
		$description = 'some description';
		$this->cache->set( $this->buildTestCacheKey( 'Q789', 'description' ), $description );

		$this->assertSame(
			$description,
			$this->newPrefetchingTermLookup()
				->getPrefetchedTerm( new ItemId( 'Q789' ), 'description', 'en' )
		);
	}

	public function testGetPrefetchedAlias() {
		$aliases = [ 'foo', 'bar', 'baz' ];
		$this->cache->set( $this->buildTestCacheKey( 'Q123', 'alias' ), $aliases );

		$this->assertSame(
			$aliases[0],
			$this->newPrefetchingTermLookup()
				->getPrefetchedTerm( new ItemId( 'Q123' ), 'alias', 'en' )
		);
	}

	public function testGetLabel() {
		$label = 'meow';
		$this->cache->set( $this->buildTestCacheKey( 'Q123', 'label' ), $label );

		$this->assertSame(
			$label,
			$this->newPrefetchingTermLookup()
				->getLabel( new ItemId( 'Q123' ), 'en' )
		);
	}

	public function testGetDescription() {
		$description = 'some description';
		$this->cache->set( $this->buildTestCacheKey( 'Q123', 'description' ), $description );

		$this->assertSame(
			$description,
			$this->newPrefetchingTermLookup()
				->getDescription( new ItemId( 'Q123' ), 'en' )
		);
	}

	public function testGetAliases() {
		$aliases = [ 'foo', 'bar', 'baz' ];
		$this->cache->set( $this->buildTestCacheKey( 'Q123', 'alias' ), $aliases );

		$this->assertSame(
			$aliases,
			$this->newPrefetchingTermLookup()
				->getPrefetchedAliases( new ItemId( 'Q123' ), 'en' )
		);
	}

	public function testGetLabels() {
		$this->cache->set( $this->buildTestCacheKey( 'Q123', 'label', 'en' ), 'meow' );
		$this->cache->set( $this->buildTestCacheKey( 'Q123', 'label', 'de' ), 'miau' );

		$this->assertEquals(
			[ 'en' => 'meow', 'de' => 'miau' ],
			$this->newPrefetchingTermLookup()->getLabels( new ItemId( 'Q123' ), [ 'de', 'en' ] )
		);
	}

	public function testGetDescriptions() {
		$this->cache->set(
			$this->buildTestCacheKey( 'Q123', 'description', 'en' ),
			'cat sound'
		);
		$this->cache->set(
			$this->buildTestCacheKey( 'Q123', 'description', 'de' ),
			'Katzengeräusch'
		);

		$this->assertEquals(
			[ 'en' => 'cat sound', 'de' => 'Katzengeräusch' ],
			$this->newPrefetchingTermLookup()->getDescriptions( new ItemId( 'Q123' ), [ 'de', 'en' ] )
		);
	}

	private function newPrefetchingTermLookup() {
		return new CachingPrefetchingTermLookup(
			$this->cache,
			$this->termsPrefetcher,
			$this->redirectResolvingRevisionLookup
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

}
