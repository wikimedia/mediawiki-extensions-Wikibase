<?php

namespace Wikibase\Lib\Tests\Store;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\TermFallback;
use Wikibase\Lib\Store\CachingFallbackLabelDescriptionLookup;
use Wikibase\Lib\Store\FallbackLabelDescriptionLookup;
use Wikibase\Lib\Store\RedirectResolvingLatestRevisionLookup;
use Wikibase\Lib\TermFallbackCache\TermFallbackCacheFacade;
use Wikibase\Lib\TermLanguageFallbackChain;

/**
 * @covers \Wikibase\Lib\Store\CachingFallbackLabelDescriptionLookup
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class CachingFallbackLabelDescriptionLookupTest extends TestCase {

	private const TEST_LABEL = 'tomato';
	private const TEST_DESCRIPTION = 'The edible berry of the plant Solanum lycopersicum';
	private const TTL = 3600; // longer than it takes to run the tests

	/**
	 * @var MockObject|CacheInterface
	 */
	private $cache;

	protected function setUp(): void {
		parent::setUp();

		/** @var MockObject $cache */
		$this->cache = $this->createMock( CacheInterface::class );
		// Cache is empty by default - returning default value
		$this->cache->method( 'get' )->willReturnArgument( 1 );
		// Can save anything
		$this->cache->method( 'set' )
			->with( $this->isType( 'string' ), $this->anything(), $this->isType( 'int' ) )
			->willReturn( true );
	}

	public function testGivenNoLabelInCache_getLabelPassesRequestToInnerLookup() {
		$itemId = new ItemId( 'Q123' );

		$ldLookup = $this->createMock( FallbackLabelDescriptionLookup::class );
		$ldLookup->method( 'getLabel' )
			->willReturn( new TermFallback( 'en', self::TEST_LABEL, 'en', 'en' ) );

		$ttlInSeconds = 10;

		$lookup = new CachingFallbackLabelDescriptionLookup(
			new TermFallbackCacheFacade(
				$this->cache,
				$ttlInSeconds
			),
			$this->newRedirectResolvingLatestRevisionLookup( 1, $itemId ),
			$ldLookup,
			$this->newFallbackChain()
		);

		$this->assertSame( self::TEST_LABEL, $lookup->getLabel( $itemId )->getText() );
	}

	public function testGetLabelWritesLabelToCache() {
		$itemId = new ItemId( 'Q123' );

		$ldLookup = $this->createMock( FallbackLabelDescriptionLookup::class );
		$ldLookup->method( 'getLabel' )
			->willReturn( new TermFallback( 'en', self::TEST_LABEL, 'en', 'en' ) );

		$ttlInSeconds = 10;

		$lookup = new CachingFallbackLabelDescriptionLookup(
			new TermFallbackCacheFacade(
				$this->cache,
				$ttlInSeconds
			),
			$this->newRedirectResolvingLatestRevisionLookup( 99, $itemId ),
			$ldLookup,
			$this->newFallbackChain()
		);

		$this->cache->expects( $this->once() )->method( 'set' )->with(
			'Q123_99_en_label',
			 [
				'language' => 'en',
				'value' => self::TEST_LABEL,
				'requestLanguage' => 'en',
				'sourceLanguage' => 'en',
			],
			$ttlInSeconds
		);

		$lookup->getLabel( $itemId );
	}

	public function testGivenEntryInCacheExists_getLabelUsesCachedValue() {
		$cache = $this->createMock( CacheInterface::class );

		$cache->method( 'get' )
			->with( 'Q123_99_en_label' )->willReturn( [
				'language' => 'en',
				'value' => self::TEST_LABEL,
				'requestLanguage' => 'en',
				'sourceLanguage' => 'en',
			] );

		$itemId = new ItemId( 'Q123' );

		$ldLookup = $this->createMock( FallbackLabelDescriptionLookup::class );

		$ttlInSeconds = 10;

		$lookup = new CachingFallbackLabelDescriptionLookup(
			new TermFallbackCacheFacade(
				$cache,
				$ttlInSeconds
			),
			$this->newRedirectResolvingLatestRevisionLookup( 99, $itemId ),
			$ldLookup,
			$this->newFallbackChain()
		);

		$ldLookup->expects( $this->never() )->method( 'getLabel' );

		$this->assertSame( self::TEST_LABEL, $lookup->getLabel( $itemId )->getText() );
	}

	public function testGivenNoLabelFound_nullEntryWrittenToCache() {
		$itemId = new ItemId( 'Q123' );

		$ldLookup = $this->createMock( FallbackLabelDescriptionLookup::class );
		$ldLookup->method( 'getLabel' )->willReturn( null );

		$ttlInSeconds = 10;

		$lookup = new CachingFallbackLabelDescriptionLookup(
			new TermFallbackCacheFacade(
				$this->cache,
				$ttlInSeconds
			),
			$this->newRedirectResolvingLatestRevisionLookup( 99, $itemId ),
			$ldLookup,
			$this->newFallbackChain()
		);

		$this->cache->expects( $this->once() )->method( 'set' )
			->with( 'Q123_99_en_label', null, $ttlInSeconds );

		$lookup->getLabel( $itemId );
	}

	public function testGivenNullEntryInCache_getLabelReturnsCachedNull() {
		$ttlInSeconds = 10;
		$cache = $this->createMock( CacheInterface::class );
		$cache->method( 'get' )->with( 'Q123_99_en_label' );

		$itemId = new ItemId( 'Q123' );

		$ldLookup = $this->createMock( FallbackLabelDescriptionLookup::class );

		$lookup = new CachingFallbackLabelDescriptionLookup(
			new TermFallbackCacheFacade(
				$cache,
				$ttlInSeconds
			),
			$this->newRedirectResolvingLatestRevisionLookup( 99, $itemId ),
			$ldLookup,
			$this->newFallbackChain()
		);

		$ldLookup->expects( $this->never() )->method( 'getLabel' );

		$this->assertNull( $lookup->getLabel( $itemId ) );
	}

	public function testGivenEmptyLanguageCodes_getLabelReturnsNull() {
		$itemId = new ItemId( 'Q123' );
		$ttlInSeconds = 10;

		$fallbackChain = $this->createMock( TermLanguageFallbackChain::class );
		$fallbackChain->method( 'getFetchLanguageCodes' )->willReturn( [] );
		$revLookup = $this->createMock( RedirectResolvingLatestRevisionLookup::class );

		$lookup = new CachingFallbackLabelDescriptionLookup(
			new TermFallbackCacheFacade(
				$this->cache,
				$ttlInSeconds
			),
			$revLookup,
			$this->createMock( FallbackLabelDescriptionLookup::class ),
			$fallbackChain
		);

		$revLookup->expects( $this->never() )->method( 'lookupLatestRevisionResolvingRedirect' );

		$this->assertNull( $lookup->getLabel( $itemId ) );
	}

	public function testGivenNoDescriptionInCache_getDescriptionPassesRequestToInnerLookup() {
		$itemId = new ItemId( 'Q123' );

		$ldLookup = $this->createMock( FallbackLabelDescriptionLookup::class );
		$ldLookup->method( 'getDescription' )
			->willReturn( new TermFallback( 'en', self::TEST_DESCRIPTION, 'en', 'en' ) );

		$ttlInSeconds = 10;

		$lookup = new CachingFallbackLabelDescriptionLookup(
			new TermFallbackCacheFacade(
				$this->cache,
				$ttlInSeconds
			),
			$this->newRedirectResolvingLatestRevisionLookup( 1, $itemId ),
			$ldLookup,
			$this->newFallbackChain()
		);

		$this->assertSame( self::TEST_DESCRIPTION, $lookup->getDescription( $itemId )->getText() );
	}

	public function testGetDescriptionWritesDescriptionToCache() {
		$itemId = new ItemId( 'Q123' );

		$ldLookup = $this->createMock( FallbackLabelDescriptionLookup::class );
		$ldLookup->method( 'getDescription' )
			->with( $itemId )
			->willReturn( new TermFallback( 'en', self::TEST_DESCRIPTION, 'en', 'en' ) );

		$ttlInSeconds = 10;

		$lookup = new CachingFallbackLabelDescriptionLookup(
			new TermFallbackCacheFacade(
				$this->cache,
				$ttlInSeconds
			),
			$this->newRedirectResolvingLatestRevisionLookup( 99, $itemId ),
			$ldLookup,
			$this->newFallbackChain()
		);

		$this->cache->expects( $this->once() )->method( 'set' )
			->with(
			'Q123_99_en_description',
			[
				'language' => 'en',
				'value' => self::TEST_DESCRIPTION,
				'requestLanguage' => 'en',
				'sourceLanguage' => 'en',
			],
			$ttlInSeconds
		);

		$lookup->getDescription( $itemId );
	}

	public function testGivenEntryInCacheExists_getDescriptionUsesCachedValue() {

		$cache = $this->createMock( CacheInterface::class );

		$cache->method( 'get' )
			->with( 'Q123_99_en_description', $this->anything() )->willReturn( [
				'language' => 'en',
				'value' => self::TEST_DESCRIPTION,
				'requestLanguage' => 'en',
				'sourceLanguage' => 'en',
			] );

		$itemId = new ItemId( 'Q123' );

		$ldLookup = $this->createMock( FallbackLabelDescriptionLookup::class );

		$ttlInSeconds = 10;

		$lookup = new CachingFallbackLabelDescriptionLookup(
			new TermFallbackCacheFacade(
				$cache,
				$ttlInSeconds
			),
			$this->newRedirectResolvingLatestRevisionLookup( 99, $itemId ),
			$ldLookup,
			$this->newFallbackChain()
		);

		$ldLookup->expects( $this->never() )->method( 'getDescription' );
		$this->assertSame( self::TEST_DESCRIPTION, $lookup->getDescription( $itemId )->getText() );
	}

	public function testGivenNoDescriptionFound_nullEntryWrittenToCache() {
		$itemId = new ItemId( 'Q123' );

		$ldLookup = $this->createMock( FallbackLabelDescriptionLookup::class );
		$ldLookup->method( 'getDescription' );

		$ttl = 10;

		$lookup = new CachingFallbackLabelDescriptionLookup(
			new TermFallbackCacheFacade(
				$this->cache,
				$ttl
			),
			$this->newRedirectResolvingLatestRevisionLookup( 99, $itemId ),
			$ldLookup,
			$this->newFallbackChain()
		);

		$this->cache->expects( $this->once() )->method( 'set' )->with( 'Q123_99_en_description', null, $ttl );

		$lookup->getDescription( $itemId );
	}

	public function testGivenNullEntryInCache_getDescriptionReturnsCachedNull() {
		$ttlInSeconds = 10;

		$cache = $this->createMock( CacheInterface::class );
		$cache->method( 'get' )->with( 'Q123_99_en_description' );

		$itemId = new ItemId( 'Q123' );

		$ldLookup = $this->createMock( FallbackLabelDescriptionLookup::class );

		$lookup = new CachingFallbackLabelDescriptionLookup(
			new TermFallbackCacheFacade(
				$cache,
				$ttlInSeconds
			),
			$this->newRedirectResolvingLatestRevisionLookup( 99, $itemId ),
			$ldLookup,
			$this->newFallbackChain()
		);

		$ldLookup->expects( $this->never() )->method( 'getDescription' );
		$this->assertNull( $lookup->getDescription( $itemId ) );
	}

	public function testGivenEmptyLanguageCodes_getDescriptionReturnsNull() {
		$itemId = new ItemId( 'Q123' );
		$ttlInSeconds = 10;

		$fallbackChain = $this->createMock( TermLanguageFallbackChain::class );
		$fallbackChain->method( 'getFetchLanguageCodes' )->willReturn( [] );
		$revLookup = $this->createMock( RedirectResolvingLatestRevisionLookup::class );

		$lookup = new CachingFallbackLabelDescriptionLookup(
			new TermFallbackCacheFacade(
				$this->cache,
				$ttlInSeconds
			),
			$revLookup,
			$this->createMock( FallbackLabelDescriptionLookup::class ),
			$fallbackChain
		);

		$revLookup->expects( $this->never() )->method( 'lookupLatestRevisionResolvingRedirect' );
		$this->assertNull( $lookup->getDescription( $itemId ) );
	}

	public function testNoRevisionFoundForTheEntity_ReturnsNull() {
		$this->cache->method( 'get' )
			->with( 'Q123_99_en_description' );

		$itemId = new ItemId( 'Q123' );

		$ldLookup = $this->createMock( FallbackLabelDescriptionLookup::class );

		$revLookup = $this->createMock( RedirectResolvingLatestRevisionLookup::class );
		$revLookup->method( 'lookupLatestRevisionResolvingRedirect' )
			->willReturn( null );

		$lookup = new CachingFallbackLabelDescriptionLookup(
			new TermFallbackCacheFacade(
				$this->cache,
				self::TTL
			),
			$revLookup,
			$ldLookup,
			$this->newFallbackChain()
		);

		$got = $lookup->getDescription( $itemId );
		$this->assertNull( $got );
	}

	public function testRevisionFoundIsARedirect_UsesLabelFromTargetEntity() {
		$itemId = new ItemId( 'Q1' );
		$redirectsToItemId = new ItemId( 'Q2' );
		$expectedLabel = $this->someTerm();
		$ldLookup = $this->createMock( FallbackLabelDescriptionLookup::class );
		$ldLookup
			->method( 'getLabel' )
			->with( $redirectsToItemId )
			->willReturn( $expectedLabel );

		$lookup = new CachingFallbackLabelDescriptionLookup(
			new TermFallbackCacheFacade(
				$this->cache,
				self::TTL
			),
			$this->newRedirectResolvingLatestRevisionLookup( 2, $redirectsToItemId ),
			$ldLookup,
			$this->newFallbackChain()
		);

		$gotLabel = $lookup->getLabel( $itemId );
		$this->assertEquals( $expectedLabel, $gotLabel );
	}

	private function newRedirectResolvingLatestRevisionLookup( int $revision, EntityId $entityId ) {
		$revLookup = $this->createMock( RedirectResolvingLatestRevisionLookup::class );
		$revLookup->method( 'lookupLatestRevisionResolvingRedirect' )
			->willReturn( [ $revision, $entityId ] );

		return $revLookup;
	}

	private function newFallbackChain() {
		$fallbackChain = $this->createMock( TermLanguageFallbackChain::class );
		$fallbackChain->method( 'getFetchLanguageCodes' )->willReturn( [ 'en' ] );
		return $fallbackChain;
	}

	/**
	 * @return TermFallback
	 */
	private function someTerm() {
		return new TermFallback( 'en', 'text', 'en', 'en' );
	}

}
