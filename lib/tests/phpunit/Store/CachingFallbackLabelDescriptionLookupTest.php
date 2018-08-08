<?php

namespace Wikibase\Lib\Tests\Store;

use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\SimpleCache\CacheInterface;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Term\TermFallback;
use Wikibase\LanguageFallbackChain;
use Wikibase\Lib\Store\CachingFallbackLabelDescriptionLookup;
use Wikibase\Lib\Store\EntityRevisionLookup;

/**
 * @covers \Wikibase\Lib\Store\CachingFallbackLabelDescriptionLookup
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class CachingFallbackLabelDescriptionLookupTest extends TestCase {

	/*private */ const TEST_LABEL = 'tomato';
	/*private */ const TEST_DESCRIPTION = 'The edible berry of the plant Solanum lycopersicum';

	/**
	 * @var ObjectProphecy|CacheInterface
	 */
	private $cache;

	protected function setUp() {
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

		$ldLookup = $this->prophesize( LabelDescriptionLookup::class );
		$ldLookup->getLabel( $itemId )
			->willReturn( new TermFallback( 'en', self::TEST_LABEL, 'en', 'en' ) );

		$ttlInSeconds = 10;

		$lookup = new CachingFallbackLabelDescriptionLookup(
			$this->cache->reveal(),
			$this->newRevisionLookup(),
			$ldLookup->reveal(),
			$this->newFallbackChain(),
			$ttlInSeconds
		);

		$this->assertSame( self::TEST_LABEL, $lookup->getLabel( $itemId )->getText() );
	}

	public function testGetLabelWritesLabelToCache() {
		$itemId = new ItemId( 'Q123' );

		$ldLookup = $this->prophesize( LabelDescriptionLookup::class );
		$ldLookup->getLabel( $itemId )
			->willReturn( new TermFallback( 'en', self::TEST_LABEL, 'en', 'en' ) );

		$ttlInSeconds = 10;

		$lookup = new CachingFallbackLabelDescriptionLookup(
			$this->cache->reveal(),
			$this->newRevisionLookup( 99 ),
			$ldLookup->reveal(),
			$this->newFallbackChain(),
			$ttlInSeconds
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

		$ldLookup = $this->prophesize( LabelDescriptionLookup::class );

		$ttlInSeconds = 10;

		$lookup = new CachingFallbackLabelDescriptionLookup(
			$this->cache->reveal(),
			$this->newRevisionLookup( 99 ),
			$ldLookup->reveal(),
			$this->newFallbackChain(),
			$ttlInSeconds
		);

		$this->assertSame( self::TEST_LABEL, $lookup->getLabel( $itemId )->getText() );

		$ldLookup->getLabel( Argument::any() )->shouldNotHaveBeenCalled();
	}

	public function testGivenNoLabelFound_nullEntryWrittenToCache() {
		$itemId = new ItemId( 'Q123' );

		$ldLookup = $this->prophesize( LabelDescriptionLookup::class );
		$ldLookup->getLabel( Argument::any() )->willReturn( null );

		$ttlInSeconds = 10;

		$lookup = new CachingFallbackLabelDescriptionLookup(
			$this->cache->reveal(),
			$this->newRevisionLookup( 99 ),
			$ldLookup->reveal(),
			$this->newFallbackChain(),
			$ttlInSeconds
		);

		$lookup->getLabel( $itemId );

		$this->cache->set( 'Q123_99_en_label', null, $ttlInSeconds )->shouldHaveBeenCalled();
	}

	public function testGivenNullEntryInCache_getLabelReturnsCachedNull() {
		$ttlInSeconds = 10;
		$this->cache->get( 'Q123_99_en_label' )->willReturn( null );

		$itemId = new ItemId( 'Q123' );

		$ldLookup = $this->prophesize( LabelDescriptionLookup::class );

		$lookup = new CachingFallbackLabelDescriptionLookup(
			$this->cache->reveal(),
			$this->newRevisionLookup( 99 ),
			$ldLookup->reveal(),
			$this->newFallbackChain(),
			$ttlInSeconds
		);

		$this->assertNull( $lookup->getLabel( $itemId ) );

		$ldLookup->getLabel()->shouldNotHaveBeenCalled();
	}

	public function testGivenNoDescriptionInCache_getDescriptionPassesRequestToInnerLookup() {
		$itemId = new ItemId( 'Q123' );

		$ldLookup = $this->prophesize( LabelDescriptionLookup::class );
		$ldLookup->getDescription( $itemId )
			->willReturn( new TermFallback( 'en', self::TEST_DESCRIPTION, 'en', 'en' ) );

		$ttlInSeconds = 10;

		$lookup = new CachingFallbackLabelDescriptionLookup(
			$this->cache->reveal(),
			$this->newRevisionLookup(),
			$ldLookup->reveal(),
			$this->newFallbackChain(),
			$ttlInSeconds
		);

		$this->assertSame( self::TEST_DESCRIPTION, $lookup->getDescription( $itemId )->getText() );
	}

	public function testGetDescriptionWritesDescriptionToCache() {
		$itemId = new ItemId( 'Q123' );

		$ldLookup = $this->prophesize( LabelDescriptionLookup::class );
		$ldLookup->getDescription( $itemId )
			->willReturn( new TermFallback( 'en', self::TEST_DESCRIPTION, 'en', 'en' ) );

		$ttlInSeconds = 10;

		$lookup = new CachingFallbackLabelDescriptionLookup(
			$this->cache->reveal(),
			$this->newRevisionLookup( 99 ),
			$ldLookup->reveal(),
			$this->newFallbackChain(),
			$ttlInSeconds
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

		$ldLookup = $this->prophesize( LabelDescriptionLookup::class );

		$ttlInSeconds = 10;

		$lookup = new CachingFallbackLabelDescriptionLookup(
			$this->cache->reveal(),
			$this->newRevisionLookup( 99 ),
			$ldLookup->reveal(),
			$this->newFallbackChain(),
			$ttlInSeconds
		);

		$this->assertSame( self::TEST_DESCRIPTION, $lookup->getDescription( $itemId )->getText() );

		$ldLookup->getDescription( Argument::any() )->shouldNotHaveBeenCalled();
	}

	public function testGivenNoDescriptionFound_nullEntryWrittenToCache() {
		$itemId = new ItemId( 'Q123' );

		$ldLookup = $this->prophesize( LabelDescriptionLookup::class );
		$ldLookup->getDescription( Argument::any() )->willReturn( null );

		$ttl = 10;

		$lookup = new CachingFallbackLabelDescriptionLookup(
			$this->cache->reveal(),
			$this->newRevisionLookup( 99 ),
			$ldLookup->reveal(),
			$this->newFallbackChain(),
			$ttl
		);

		$lookup->getDescription( $itemId );

		$this->cache->set( 'Q123_99_en_description', null, $ttl )->shouldHaveBeenCalled();
	}

	public function testGivenNullEntryInCache_getDescriptionReturnsCachedNull() {
		$ttlInSeconds = 10;
		$this->cache->get( 'Q123_99_en_description' )->willReturn( null );

		$itemId = new ItemId( 'Q123' );

		$ldLookup = $this->prophesize( LabelDescriptionLookup::class );

		$lookup = new CachingFallbackLabelDescriptionLookup(
			$this->cache->reveal(),
			$this->newRevisionLookup( 99 ),
			$ldLookup->reveal(),
			$this->newFallbackChain(),
			$ttlInSeconds
		);

		$this->assertNull( $lookup->getDescription( $itemId ) );

		$ldLookup->getDescription()->shouldNotHaveBeenCalled();
	}

	private function newRevisionLookup( $revisionIdToReturn = 0 ) {
		$revLookup = $this->prophesize( EntityRevisionLookup::class );
		if ( $revisionIdToReturn ) {
			$revLookup->getLatestRevisionId( Argument::any() )->willReturn( $revisionIdToReturn );
		}
		return $revLookup->reveal();
	}

	private function newFallbackChain() {
		$fallbackChain = $this->prophesize( LanguageFallbackChain::class );
		$fallbackChain->getFetchLanguageCodes()->willReturn( [ 'en' ] );
		return $fallbackChain->reveal();
	}

}
