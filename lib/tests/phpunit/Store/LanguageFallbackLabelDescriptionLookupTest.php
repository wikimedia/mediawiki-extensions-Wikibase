<?php

namespace Wikibase\Lib\Tests\Store;

use MediaWikiTestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\TermFallback;
use Wikibase\LanguageFallbackChain;
use Wikibase\Lib\Store\EntityTermLookup;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup;
use Wikibase\TermIndexEntry;

/**
 * @covers Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup
 *
 * @group Wikibase
 * @group WikibaseStore
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Marius Hoch < hoo@online.de >
 */
class LanguageFallbackLabelDescriptionLookupTest extends MediaWikiTestCase {

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
		$termLookup = $this->getTermLookup();
		$fallbackChain = $this->getLanguageFallbackChain( 'zh' );

		$labelDescriptionLookup = new LanguageFallbackLabelDescriptionLookup( $termLookup, $fallbackChain );

		$this->assertNull( $labelDescriptionLookup->getLabel( new ItemId( 'Q120' ) ) );
	}

	public function testGetDescription_entityNotFound() {
		$termLookup = $this->getTermLookup();
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
	 * @return LanguageFallbackChain
	 */
	private function getLanguageFallbackChain( $languageCode ) {
		$languageFallbackChain = $this->getMockBuilder( LanguageFallbackChain::class )
			->disableOriginalConstructor()
			->getMock();

		$languageFallbackChain->expects( $this->any() )
			->method( 'extractPreferredValue' )
			->will( $this->returnCallback( function( array $fallbackData ) use ( $languageCode ) {
				if ( $languageCode === 'zh' && array_key_exists( 'zh-cn', $fallbackData ) ) {
					return [ 'value' => 'fallbackterm', 'language' => 'zh-cn', 'source' => 'zh-xy' ];
				} else {
					return null;
				}
			} ) );

		$languageFallbackChain->expects( $this->any() )
			->method( 'getFetchLanguageCodes' )
			->will( $this->returnCallback( function() use ( $languageCode ) {
				if ( $languageCode === 'zh' ) {
					return [ 'zh', 'zh-cn', 'zh-xy' ];
				} else {
					return [ $languageCode ];
				}
			} ) );

		return $languageFallbackChain;
	}

	private function getTermLookup() {
		return new EntityTermLookup( $this->getTermIndex() );
	}

	private function getTermIndex() {
		$terms = [
			new TermIndexEntry( [
				'entityId' => new ItemId( 'Q116' ),
				'termType' => 'label',
				'termLanguage' => 'en',
				'termText' => 'New York City'
			] ),
			new TermIndexEntry( [
				'entityId' => new ItemId( 'Q116' ),
				'termType' => 'label',
				'termLanguage' => 'es',
				'termText' => 'New York City'
			] ),
			new TermIndexEntry( [
				'entityId' => new ItemId( 'Q116' ),
				'termType' => 'description',
				'termLanguage' => 'en',
				'termText' => 'Big Apple'
			] ),
			new TermIndexEntry( [
				'entityId' => new ItemId( 'Q117' ),
				'termType' => 'label',
				'termLanguage' => 'en',
				'termText' => 'Berlin'
			] ),
			new TermIndexEntry( [
				'entityId' => new ItemId( 'Q118' ),
				'termType' => 'label',
				'termLanguage' => 'zh-cn',
				'termText' => '测试'
			] ),
			new TermIndexEntry( [
				'entityId' => new ItemId( 'Q118' ),
				'termType' => 'description',
				'termLanguage' => 'zh-cn',
				'termText' => 'zh-cn description'
			] ),
		];

		return new MockTermIndex( $terms );
	}

}
