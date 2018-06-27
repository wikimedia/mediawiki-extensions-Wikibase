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

	public function setUp() {
		if ( !interface_exists( CacheInterface::class ) ) {
			$this->markTestSkipped( 'psr/simple-cache not installed' );
		}
	}

	public function testGivenNoLabelInCache_getLabelPassesRequestToInnerLookup() {
		$cache = $this->prophesize( CacheInterface::class );

		$revLookup = $this->prophesize( EntityRevisionLookup::class );

		$itemId = new ItemId( 'Q123' );

		$ldLookup = $this->prophesize( LabelDescriptionLookup::class );
		$ldLookup->getLabel( $itemId )->willReturn( new TermFallback( 'en', 'tomato', 'en', 'en' ) );

		$ttl = 10;

		$lookup = new CachingFallbackLabelDescriptionLookup(
			$cache->reveal(),
			$revLookup->reveal(),
			$ldLookup->reveal(),
			$this->newFallbackChain(),
			$ttl
		);

		$this->assertEquals( 'tomato', $lookup->getLabel( $itemId )->getText() );
	}

	public function testGetLabelWritesLabelToCache() {
		$cache = $this->prophesize( CacheInterface::class );

		$revLookup = $this->prophesize( EntityRevisionLookup::class );
		$revLookup->getLatestRevisionId( Argument::any() )->willReturn( 99 );

		$itemId = new ItemId( 'Q123' );

		$ldLookup = $this->prophesize( LabelDescriptionLookup::class );
		$ldLookup->getLabel( $itemId )->willReturn( new TermFallback( 'en', 'tomato', 'en', 'en' ) );

		$ttl = 10;

		$lookup = new CachingFallbackLabelDescriptionLookup(
			$cache->reveal(),
			$revLookup->reveal(),
			$ldLookup->reveal(),
			$this->newFallbackChain(),
			$ttl
		);

		$lookup->getLabel( $itemId );

		$cache->set(
			'Q123_99_en_label',
			json_encode( [
				'language' => 'en',
				'value' => 'tomato',
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
			'value' => 'tomato',
			'requestLanguage' => 'en',
			'sourceLanguage' => 'en',
		] ) );

		$revLookup = $this->prophesize( EntityRevisionLookup::class );
		$revLookup->getLatestRevisionId( Argument::any() )->willReturn( 99 );

		$itemId = new ItemId( 'Q123' );

		$ldLookup = $this->prophesize( LabelDescriptionLookup::class );

		$ttl = 10;

		$lookup = new CachingFallbackLabelDescriptionLookup(
			$cache->reveal(),
			$revLookup->reveal(),
			$ldLookup->reveal(),
			$this->newFallbackChain(),
			$ttl
		);

		$this->assertEquals( 'tomato', $lookup->getLabel( $itemId )->getText() );

		$ldLookup->getLabel()->shouldNotHaveBeenCalled();
	}

	public function testGivenNoLabelFound_noEntryWrittenToCache() {
		$cache = $this->prophesize( CacheInterface::class );

		$revLookup = $this->prophesize( EntityRevisionLookup::class );
		$revLookup->getLatestRevisionId( Argument::any() )->willReturn( 99 );

		$itemId = new ItemId( 'Q123' );

		$ldLookup = $this->prophesize( LabelDescriptionLookup::class );
		$ldLookup->getLabel( Argument::any() )->willReturn( null );

		$ttl = 10;

		$lookup = new CachingFallbackLabelDescriptionLookup(
			$cache->reveal(),
			$revLookup->reveal(),
			$ldLookup->reveal(),
			$this->newFallbackChain(),
			$ttl
		);

		$lookup->getLabel( $itemId );

		$cache->set()->shouldNotHaveBeenCalled();
	}

	private function newFallbackChain() {
		$fallbackChain = $this->prophesize( LanguageFallbackChain::class );
		$fallbackChain->getFetchLanguageCodes()->willReturn( [ 'en' ] );
		return $fallbackChain->reveal();
	}

	// getDescription x4
}
