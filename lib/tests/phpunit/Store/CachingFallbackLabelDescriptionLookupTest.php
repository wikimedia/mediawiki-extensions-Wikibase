<?php

namespace Wikibase\Lib\Tests\Store;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
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
	 * @var ObjectProphecy|CacheInterface
	 */
	private $cache;

	protected function setUp(): void {
		parent::setUp();

		/** @var ObjectProphecy $cache */
		$this->cache = $this->prophesize( CacheInterface::class );
		// Cache is empty by default - returning default value
		$this->cache->get( Argument::any(), Argument::any() )->willReturnArgument( 1 );
		// Can save anything
		$this->cache->set( Argument::type( 'string' ), Argument::any(), Argument::type( 'integer' ) )->willReturn( true );
	}

	public function testGivenNoLabelInCache_getLabelPassesRequestToInnerLookup() {
		$itemId = new ItemId( 'Q123' );

		$ldLookup = $this->prophesize( FallbackLabelDescriptionLookup::class );
		$ldLookup->getLabel( $itemId )
			->willReturn( new TermFallback( 'en', self::TEST_LABEL, 'en', 'en' ) );

		$ttlInSeconds = 10;

		$lookup = new CachingFallbackLabelDescriptionLookup(
			new TermFallbackCacheFacade(
				$this->cache->reveal(),
				$ttlInSeconds
			),
			$this->newRedirectResolvingLatestRevisionLookup( 1, $itemId ),
			$ldLookup->reveal(),
			$this->newFallbackChain()
		);

		$this->assertSame( self::TEST_LABEL, $lookup->getLabel( $itemId )->getText() );
	}

	public function testGetLabelWritesLabelToCache() {
		$itemId = new ItemId( 'Q123' );

		$ldLookup = $this->prophesize( FallbackLabelDescriptionLookup::class );
		$ldLookup->getLabel( $itemId )
			->willReturn( new TermFallback( 'en', self::TEST_LABEL, 'en', 'en' ) );

		$ttlInSeconds = 10;

		$lookup = new CachingFallbackLabelDescriptionLookup(
			new TermFallbackCacheFacade(
				$this->cache->reveal(),
				$ttlInSeconds
			),
			$this->newRedirectResolvingLatestRevisionLookup( 99, $itemId ),
			$ldLookup->reveal(),
			$this->newFallbackChain()
		);

		$lookup->getLabel( $itemId );

		$this->cache->set(
			'Q123_99_en_label',
			 [
				'language' => 'en',
				'value' => self::TEST_LABEL,
				'requestLanguage' => 'en',
				'sourceLanguage' => 'en',
			],
			$ttlInSeconds
		)->shouldHaveBeenCalled();
	}

	public function testGivenEntryInCacheExists_getLabelUsesCachedValue() {
		$this->cache->get( 'Q123_99_en_label', Argument::any() )->willReturn( [
			'language' => 'en',
			'value' => self::TEST_LABEL,
			'requestLanguage' => 'en',
			'sourceLanguage' => 'en',
		] );

		$itemId = new ItemId( 'Q123' );

		$ldLookup = $this->prophesize( FallbackLabelDescriptionLookup::class );

		$ttlInSeconds = 10;

		$lookup = new CachingFallbackLabelDescriptionLookup(
			new TermFallbackCacheFacade(
				$this->cache->reveal(),
				$ttlInSeconds
			),
			$this->newRedirectResolvingLatestRevisionLookup( 99, $itemId ),
			$ldLookup->reveal(),
			$this->newFallbackChain()
		);

		$this->assertSame( self::TEST_LABEL, $lookup->getLabel( $itemId )->getText() );

		$ldLookup->getLabel( Argument::any() )->shouldNotHaveBeenCalled();
	}

	public function testGivenNoLabelFound_nullEntryWrittenToCache() {
		$itemId = new ItemId( 'Q123' );

		$ldLookup = $this->prophesize( FallbackLabelDescriptionLookup::class );
		$ldLookup->getLabel( Argument::any() )->willReturn( null );

		$ttlInSeconds = 10;

		$lookup = new CachingFallbackLabelDescriptionLookup(
			new TermFallbackCacheFacade(
				$this->cache->reveal(),
				$ttlInSeconds
			),
			$this->newRedirectResolvingLatestRevisionLookup( 99, $itemId ),
			$ldLookup->reveal(),
			$this->newFallbackChain()
		);

		$lookup->getLabel( $itemId );

		$this->cache->set( 'Q123_99_en_label', null, $ttlInSeconds )->shouldHaveBeenCalled();
	}

	public function testGivenNullEntryInCache_getLabelReturnsCachedNull() {
		$ttlInSeconds = 10;
		$this->cache->get( 'Q123_99_en_label' )->willReturn( null );

		$itemId = new ItemId( 'Q123' );

		$ldLookup = $this->prophesize( FallbackLabelDescriptionLookup::class );

		$lookup = new CachingFallbackLabelDescriptionLookup(
			new TermFallbackCacheFacade(
				$this->cache->reveal(),
				$ttlInSeconds
			),
			$this->newRedirectResolvingLatestRevisionLookup( 99, $itemId ),
			$ldLookup->reveal(),
			$this->newFallbackChain()
		);

		$this->assertNull( $lookup->getLabel( $itemId ) );

		$ldLookup->getLabel()->shouldNotHaveBeenCalled();
	}

	public function testGivenEmptyLanguageCodes_getLabelReturnsNull() {
		$itemId = new ItemId( 'Q123' );
		$ttlInSeconds = 10;

		$fallbackChain = $this->prophesize( TermLanguageFallbackChain::class );
		$fallbackChain->getFetchLanguageCodes()->willReturn( [] );
		$revLookup = $this->prophesize( RedirectResolvingLatestRevisionLookup::class );

		$lookup = new CachingFallbackLabelDescriptionLookup(
			new TermFallbackCacheFacade(
				$this->cache->reveal(),
				$ttlInSeconds
			),
			$revLookup->reveal(),
			$this->prophesize( FallbackLabelDescriptionLookup::class )->reveal(),
			$fallbackChain->reveal()
		);

		$this->assertNull( $lookup->getLabel( $itemId ) );
		$revLookup->lookupLatestRevisionResolvingRedirect()->shouldNotHaveBeenCalled();
	}

	public function testGivenNoDescriptionInCache_getDescriptionPassesRequestToInnerLookup() {
		$itemId = new ItemId( 'Q123' );

		$ldLookup = $this->prophesize( FallbackLabelDescriptionLookup::class );
		$ldLookup->getDescription( $itemId )
			->willReturn( new TermFallback( 'en', self::TEST_DESCRIPTION, 'en', 'en' ) );

		$ttlInSeconds = 10;

		$lookup = new CachingFallbackLabelDescriptionLookup(
			new TermFallbackCacheFacade(
				$this->cache->reveal(),
				$ttlInSeconds
			),
			$this->newRedirectResolvingLatestRevisionLookup( 1, $itemId ),
			$ldLookup->reveal(),
			$this->newFallbackChain()
		);

		$this->assertSame( self::TEST_DESCRIPTION, $lookup->getDescription( $itemId )->getText() );
	}

	public function testGetDescriptionWritesDescriptionToCache() {
		$itemId = new ItemId( 'Q123' );

		$ldLookup = $this->prophesize( FallbackLabelDescriptionLookup::class );
		$ldLookup->getDescription( $itemId )
			->willReturn( new TermFallback( 'en', self::TEST_DESCRIPTION, 'en', 'en' ) );

		$ttlInSeconds = 10;

		$lookup = new CachingFallbackLabelDescriptionLookup(
			new TermFallbackCacheFacade(
				$this->cache->reveal(),
				$ttlInSeconds
			),
			$this->newRedirectResolvingLatestRevisionLookup( 99, $itemId ),
			$ldLookup->reveal(),
			$this->newFallbackChain()
		);

		$lookup->getDescription( $itemId );

		$this->cache->set(
			'Q123_99_en_description',
			[
				'language' => 'en',
				'value' => self::TEST_DESCRIPTION,
				'requestLanguage' => 'en',
				'sourceLanguage' => 'en',
			],
			$ttlInSeconds
		)->shouldHaveBeenCalled();
	}

	public function testGivenEntryInCacheExists_getDescriptionUsesCachedValue() {
		$this->cache->get( 'Q123_99_en_description', Argument::any() )->willReturn( [
			'language' => 'en',
			'value' => self::TEST_DESCRIPTION,
			'requestLanguage' => 'en',
			'sourceLanguage' => 'en',
		] );

		$itemId = new ItemId( 'Q123' );

		$ldLookup = $this->prophesize( FallbackLabelDescriptionLookup::class );

		$ttlInSeconds = 10;

		$lookup = new CachingFallbackLabelDescriptionLookup(
			new TermFallbackCacheFacade(
				$this->cache->reveal(),
				$ttlInSeconds
			),
			$this->newRedirectResolvingLatestRevisionLookup( 99, $itemId ),
			$ldLookup->reveal(),
			$this->newFallbackChain()
		);

		$this->assertSame( self::TEST_DESCRIPTION, $lookup->getDescription( $itemId )->getText() );

		$ldLookup->getDescription( Argument::any() )->shouldNotHaveBeenCalled();
	}

	public function testGivenNoDescriptionFound_nullEntryWrittenToCache() {
		$itemId = new ItemId( 'Q123' );

		$ldLookup = $this->prophesize( FallbackLabelDescriptionLookup::class );
		$ldLookup->getDescription( Argument::any() )->willReturn( null );

		$ttl = 10;

		$lookup = new CachingFallbackLabelDescriptionLookup(
			new TermFallbackCacheFacade(
				$this->cache->reveal(),
				$ttl
			),
			$this->newRedirectResolvingLatestRevisionLookup( 99, $itemId ),
			$ldLookup->reveal(),
			$this->newFallbackChain()
		);

		$lookup->getDescription( $itemId );

		$this->cache->set( 'Q123_99_en_description', null, $ttl )->shouldHaveBeenCalled();
	}

	public function testGivenNullEntryInCache_getDescriptionReturnsCachedNull() {
		$ttlInSeconds = 10;
		$this->cache->get( 'Q123_99_en_description' )->willReturn( null );

		$itemId = new ItemId( 'Q123' );

		$ldLookup = $this->prophesize( FallbackLabelDescriptionLookup::class );

		$lookup = new CachingFallbackLabelDescriptionLookup(
			new TermFallbackCacheFacade(
				$this->cache->reveal(),
				$ttlInSeconds
			),
			$this->newRedirectResolvingLatestRevisionLookup( 99, $itemId ),
			$ldLookup->reveal(),
			$this->newFallbackChain()
		);

		$this->assertNull( $lookup->getDescription( $itemId ) );

		$ldLookup->getDescription()->shouldNotHaveBeenCalled();
	}

	public function testGivenEmptyLanguageCodes_getDescriptionReturnsNull() {
		$itemId = new ItemId( 'Q123' );
		$ttlInSeconds = 10;

		$fallbackChain = $this->prophesize( TermLanguageFallbackChain::class );
		$fallbackChain->getFetchLanguageCodes()->willReturn( [] );
		$revLookup = $this->prophesize( RedirectResolvingLatestRevisionLookup::class );

		$lookup = new CachingFallbackLabelDescriptionLookup(
			new TermFallbackCacheFacade(
				$this->cache->reveal(),
				$ttlInSeconds
			),
			$revLookup->reveal(),
			$this->prophesize( FallbackLabelDescriptionLookup::class )->reveal(),
			$fallbackChain->reveal()
		);

		$this->assertNull( $lookup->getDescription( $itemId ) );
		$revLookup->lookupLatestRevisionResolvingRedirect()->shouldNotHaveBeenCalled();
	}

	public function testNoRevisionFoundForTheEntity_ReturnsNull() {
		$this->cache->get( 'Q123_99_en_description' )->willReturn( null );

		$itemId = new ItemId( 'Q123' );

		$ldLookup = $this->prophesize( FallbackLabelDescriptionLookup::class );

		$revLookup = $this->prophesize( RedirectResolvingLatestRevisionLookup::class );
		$revLookup->lookupLatestRevisionResolvingRedirect( Argument::any() )
			->willReturn( null );

		$lookup = new CachingFallbackLabelDescriptionLookup(
			new TermFallbackCacheFacade(
				$this->cache->reveal(),
				self::TTL
			),
			$revLookup->reveal(),
			$ldLookup->reveal(),
			$this->newFallbackChain()
		);

		$got = $lookup->getDescription( $itemId );
		$this->assertNull( $got );
	}

	public function testRevisionFoundIsARedirect_UsesLabelFromTargetEntity() {
		$itemId = new ItemId( 'Q1' );
		$redirectsToItemId = new ItemId( 'Q2' );
		$expectedLabel = $this->someTerm();
		$ldLookup = $this->prophesize( FallbackLabelDescriptionLookup::class );
		$ldLookup->getLabel( $redirectsToItemId )->willReturn( $expectedLabel );

		$lookup = new CachingFallbackLabelDescriptionLookup(
			new TermFallbackCacheFacade(
				$this->cache->reveal(),
				self::TTL
			),
			$this->newRedirectResolvingLatestRevisionLookup( 2, $redirectsToItemId ),
			$ldLookup->reveal(),
			$this->newFallbackChain()
		);

		$gotLabel = $lookup->getLabel( $itemId );
		$this->assertEquals( $expectedLabel, $gotLabel );
	}

	private function newRedirectResolvingLatestRevisionLookup( int $revision, EntityId $entityId ) {
		$revLookup = $this->prophesize( RedirectResolvingLatestRevisionLookup::class );
		$revLookup->lookupLatestRevisionResolvingRedirect( Argument::any() )
			->willReturn( [ $revision, $entityId ] );

		return $revLookup->reveal();
	}

	private function newFallbackChain() {
		$fallbackChain = $this->prophesize( TermLanguageFallbackChain::class );
		$fallbackChain->getFetchLanguageCodes()->willReturn( [ 'en' ] );
		return $fallbackChain->reveal();
	}

	/**
	 * @return TermFallback
	 */
	private function someTerm() {
		return new TermFallback( 'en', 'text', 'en', 'en' );
	}

}
