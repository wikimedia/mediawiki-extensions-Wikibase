<?php

namespace Wikibase\Lib\Tests\Store;

use Prophecy\Argument;
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

	const TEST_LABEL = 'tomato';
	const TEST_DESCRIPTION = 'The edible berry of the plant Solanum lycopersicum';

	public function setUp() {
		if ( !interface_exists( CacheInterface::class ) ) {
			$this->markTestSkipped( 'psr/simple-cache not installed' );
		}
	}

	public function testGivenNoLabelInCache_getLabelPassesRequestToInnerLookup() {
		$cache = $this->prophesize( CacheInterface::class );

		$itemId = new ItemId( 'Q123' );

		$ldLookup = $this->prophesize( LabelDescriptionLookup::class );
		$ldLookup->getLabel( $itemId )
			->willReturn( new TermFallback( 'en', self::TEST_LABEL, 'en', 'en' ) );

		$ttl = 10;

		$lookup = new CachingFallbackLabelDescriptionLookup(
			$cache->reveal(),
			$this->newRevisionLookup(),
			$ldLookup->reveal(),
			$this->newFallbackChain(),
			$ttl
		);

		$this->assertEquals( self::TEST_LABEL, $lookup->getLabel( $itemId )->getText() );
	}

	public function testGetLabelWritesLabelToCache() {
		$cache = $this->prophesize( CacheInterface::class );

		$itemId = new ItemId( 'Q123' );

		$ldLookup = $this->prophesize( LabelDescriptionLookup::class );
		$ldLookup->getLabel( $itemId )
			->willReturn( new TermFallback( 'en', self::TEST_LABEL, 'en', 'en' ) );

		$ttl = 10;

		$lookup = new CachingFallbackLabelDescriptionLookup(
			$cache->reveal(),
			$this->newRevisionLookup( 99 ),
			$ldLookup->reveal(),
			$this->newFallbackChain(),
			$ttl
		);

		$lookup->getLabel( $itemId );

		$cache->set(
			'Q123_99_en_label',
			json_encode( [
				'language' => 'en',
				'value' => self::TEST_LABEL,
				'requestLanguage' => 'en',
				'sourceLanguage' => 'en',
			] ),
			$ttl
		)->shouldHaveBeenCalled();
	}

	public function testGivenEntryInCacheExists_getLabelUsesCachedValue() {
		$cache = $this->prophesize( CacheInterface::class );
		$cache->get( 'Q123_99_en_label' )->willReturn( json_encode( [
			'language' => 'en',
			'value' => self::TEST_LABEL,
			'requestLanguage' => 'en',
			'sourceLanguage' => 'en',
		] ) );

		$itemId = new ItemId( 'Q123' );

		$ldLookup = $this->prophesize( LabelDescriptionLookup::class );

		$ttl = 10;

		$lookup = new CachingFallbackLabelDescriptionLookup(
			$cache->reveal(),
			$this->newRevisionLookup( 99 ),
			$ldLookup->reveal(),
			$this->newFallbackChain(),
			$ttl
		);

		$this->assertEquals( self::TEST_LABEL, $lookup->getLabel( $itemId )->getText() );

		$ldLookup->getLabel( Argument::any() )->shouldNotHaveBeenCalled();
	}

	public function testGivenNoLabelFound_nullEntryWrittenToCache() {
		$cache = $this->prophesize( CacheInterface::class );

		$itemId = new ItemId( 'Q123' );

		$ldLookup = $this->prophesize( LabelDescriptionLookup::class );
		$ldLookup->getLabel( Argument::any() )->willReturn( null );

		$ttl = 10;

		$lookup = new CachingFallbackLabelDescriptionLookup(
			$cache->reveal(),
			$this->newRevisionLookup( 99 ),
			$ldLookup->reveal(),
			$this->newFallbackChain(),
			$ttl
		);

		$lookup->getLabel( $itemId );

		$cache->set( 'Q123_99_en_label', 'null', $ttl )->shouldHaveBeenCalled();
	}

	public function testGivenNullEntryInCache_getLabelReturnsCachedNull() {
		$cache = $this->prophesize( CacheInterface::class );
		$cache->get( 'Q123_99_en_label' )->willReturn( 'null' );

		$itemId = new ItemId( 'Q123' );

		$ldLookup = $this->prophesize( LabelDescriptionLookup::class );

		$ttl = 10;

		$lookup = new CachingFallbackLabelDescriptionLookup(
			$cache->reveal(),
			$this->newRevisionLookup( 99 ),
			$ldLookup->reveal(),
			$this->newFallbackChain(),
			$ttl
		);

		$this->assertNull( $lookup->getLabel( $itemId ) );

		$ldLookup->getLabel()->shouldNotHaveBeenCalled();
	}

	public function testGivenNoDescriptionInCache_getDescriptionPassesRequestToInnerLookup() {
		$cache = $this->prophesize( CacheInterface::class );

		$itemId = new ItemId( 'Q123' );

		$ldLookup = $this->prophesize( LabelDescriptionLookup::class );
		$ldLookup->getDescription( $itemId )
			->willReturn( new TermFallback( 'en', self::TEST_DESCRIPTION, 'en', 'en' ) );

		$ttl = 10;

		$lookup = new CachingFallbackLabelDescriptionLookup(
			$cache->reveal(),
			$this->newRevisionLookup(),
			$ldLookup->reveal(),
			$this->newFallbackChain(),
			$ttl
		);

		$this->assertEquals( self::TEST_DESCRIPTION, $lookup->getDescription( $itemId )->getText() );
	}

	public function testGetDescriptionWritesDescriptionToCache() {
		$cache = $this->prophesize( CacheInterface::class );

		$itemId = new ItemId( 'Q123' );

		$ldLookup = $this->prophesize( LabelDescriptionLookup::class );
		$ldLookup->getDescription( $itemId )
			->willReturn( new TermFallback( 'en', self::TEST_DESCRIPTION, 'en', 'en' ) );

		$ttl = 10;

		$lookup = new CachingFallbackLabelDescriptionLookup(
			$cache->reveal(),
			$this->newRevisionLookup( 99 ),
			$ldLookup->reveal(),
			$this->newFallbackChain(),
			$ttl
		);

		$lookup->getDescription( $itemId );

		$cache->set(
			'Q123_99_en_description',
			json_encode( [
				'language' => 'en',
				'value' => self::TEST_DESCRIPTION,
				'requestLanguage' => 'en',
				'sourceLanguage' => 'en',
			] ),
			$ttl
		)->shouldHaveBeenCalled();
	}

	public function testGivenEntryInCacheExists_getDescriptionUsesCachedValue() {
		$cache = $this->prophesize( CacheInterface::class );
		$cache->get( 'Q123_99_en_description' )->willReturn( json_encode( [
			'language' => 'en',
			'value' => self::TEST_DESCRIPTION,
			'requestLanguage' => 'en',
			'sourceLanguage' => 'en',
		] ) );

		$itemId = new ItemId( 'Q123' );

		$ldLookup = $this->prophesize( LabelDescriptionLookup::class );

		$ttl = 10;

		$lookup = new CachingFallbackLabelDescriptionLookup(
			$cache->reveal(),
			$this->newRevisionLookup( 99 ),
			$ldLookup->reveal(),
			$this->newFallbackChain(),
			$ttl
		);

		$this->assertEquals( self::TEST_DESCRIPTION, $lookup->getDescription( $itemId )->getText() );

		$ldLookup->getDescription( Argument::any() )->shouldNotHaveBeenCalled();
	}

	public function testGivenNoDescriptionFound_nullEntryWrittenToCache() {
		$cache = $this->prophesize( CacheInterface::class );

		$itemId = new ItemId( 'Q123' );

		$ldLookup = $this->prophesize( LabelDescriptionLookup::class );
		$ldLookup->getDescription( Argument::any() )->willReturn( null );

		$ttl = 10;

		$lookup = new CachingFallbackLabelDescriptionLookup(
			$cache->reveal(),
			$this->newRevisionLookup( 99 ),
			$ldLookup->reveal(),
			$this->newFallbackChain(),
			$ttl
		);

		$lookup->getDescription( $itemId );

		$cache->set( 'Q123_99_en_description', 'null', $ttl )->shouldHaveBeenCalled();
	}

	public function testGivenNullEntryInCache_getDescriptionReturnsCachedNull() {
		$cache = $this->prophesize( CacheInterface::class );
		$cache->get( 'Q123_99_en_description' )->willReturn( 'null' );

		$itemId = new ItemId( 'Q123' );

		$ldLookup = $this->prophesize( LabelDescriptionLookup::class );

		$ttl = 10;

		$lookup = new CachingFallbackLabelDescriptionLookup(
			$cache->reveal(),
			$this->newRevisionLookup( 99 ),
			$ldLookup->reveal(),
			$this->newFallbackChain(),
			$ttl
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
