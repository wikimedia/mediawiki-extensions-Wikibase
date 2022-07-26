<?php

namespace Wikibase\Lib\Tests\Store;

use MediaWikiIntegrationTestCase;
use Wikibase\DataAccess\NullPrefetchingTermLookup;
use Wikibase\DataAccess\Tests\FakePrefetchingTermLookup;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\TermFallback;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup;
use Wikibase\Lib\TermLanguageFallbackChain;

/**
 * @covers \Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup
 *
 * @group Wikibase
 * @group WikibaseStore
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Marius Hoch < hoo@online.de >
 */
class LanguageFallbackLabelDescriptionLookupTest extends MediaWikiIntegrationTestCase {

	public function testGetLabel() {
		$termLookup = $this->getTermLookup();
		$fallbackChain = $this->getLanguageFallbackChain( 'zh' );

		$labelDescriptionLookup = new LanguageFallbackLabelDescriptionLookup( $termLookup, $fallbackChain );

		/** @var TermFallback $term */
		$term = $labelDescriptionLookup->getLabel( new ItemId( 'Q118' ) );

		$this->assertInstanceOf( TermFallback::class, $term );
		$this->assertEquals( 'fallbackterm', $term->getText() );
		$this->assertEquals( 'zh', $term->getLanguageCode() );
		$this->assertEquals( 'zh-cn', $term->getActualLanguageCode() );
		$this->assertEquals( 'zh-xy', $term->getSourceLanguageCode() );
	}

	public function testGetDescription() {
		$termLookup = $this->getTermLookup();
		$fallbackChain = $this->getLanguageFallbackChain( 'zh' );

		$labelDescriptionLookup = new LanguageFallbackLabelDescriptionLookup( $termLookup, $fallbackChain );

		/** @var TermFallback $term */
		$term = $labelDescriptionLookup->getLabel( new ItemId( 'Q118' ) );

		$this->assertInstanceOf( TermFallback::class, $term );
		$this->assertEquals( 'fallbackterm', $term->getText() );
		$this->assertEquals( 'zh', $term->getLanguageCode() );
		$this->assertEquals( 'zh-cn', $term->getActualLanguageCode() );
		$this->assertEquals( 'zh-xy', $term->getSourceLanguageCode() );
	}

	public function testGetLabel_entityNotFound() {
		$termLookup = new NullPrefetchingTermLookup();
		$fallbackChain = $this->getLanguageFallbackChain( 'zh' );

		$labelDescriptionLookup = new LanguageFallbackLabelDescriptionLookup( $termLookup, $fallbackChain );

		$this->assertNull( $labelDescriptionLookup->getLabel( new ItemId( 'Q120' ) ) );
	}

	public function testGetDescription_entityNotFound() {
		$termLookup = new NullPrefetchingTermLookup();
		$fallbackChain = $this->getLanguageFallbackChain( 'zh' );

		$labelDescriptionLookup = new LanguageFallbackLabelDescriptionLookup( $termLookup, $fallbackChain );

		$this->assertNull( $labelDescriptionLookup->getDescription( new ItemId( 'Q120' ) ) );
	}

	public function testGetLabel_notFound() {
		$termLookup = $this->getTermLookup();
		$fallbackChain = $this->getLanguageFallbackChain( 'ar' );

		$labelDescriptionLookup = new LanguageFallbackLabelDescriptionLookup( $termLookup, $fallbackChain );

		$this->assertNull( $labelDescriptionLookup->getLabel( new ItemId( 'Q116' ) ) );
	}

	public function testGetDescription_notFound() {
		$termLookup = $this->getTermLookup();
		$fallbackChain = $this->getLanguageFallbackChain( 'ar' );

		$labelDescriptionLookup = new LanguageFallbackLabelDescriptionLookup( $termLookup, $fallbackChain );

		$this->assertNull( $labelDescriptionLookup->getDescription( new ItemId( 'Q116' ) ) );
	}

	/**
	 * @param string $languageCode
	 *
	 * @return TermLanguageFallbackChain
	 */
	private function getLanguageFallbackChain( $languageCode ) {
		$languageFallbackChain = $this->createMock( TermLanguageFallbackChain::class );

		$languageFallbackChain->method( 'extractPreferredValue' )
			->willReturnCallback( function( array $fallbackData ) use ( $languageCode ) {
				if ( $languageCode === 'zh' && array_key_exists( 'zh-cn', $fallbackData ) ) {
					return [ 'value' => 'fallbackterm', 'language' => 'zh-cn', 'source' => 'zh-xy' ];
				} else {
					return null;
				}
			} );

		$languageFallbackChain->method( 'getFetchLanguageCodes' )
			->willReturnCallback( function() use ( $languageCode ) {
				if ( $languageCode === 'zh' ) {
					return [ 'zh', 'zh-cn', 'zh-xy' ];
				} else {
					return [ $languageCode ];
				}
			} );

		return $languageFallbackChain;
	}

	private function getTermLookup() {
		return new FakePrefetchingTermLookup();
	}
}
