<?php

namespace Wikibase\Test;

use MediaWikiTestCase;
use Wikibase\Term;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\TermFallback;
use Wikibase\Lib\Store\EntityTermLookup;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup;

/**
 * @covers Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseStore
 *
 * @licence GNU GPL v2+
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

		$this->assertInstanceOf( 'Wikibase\DataModel\Term\TermFallback', $term );
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

		$this->assertInstanceOf( 'Wikibase\DataModel\Term\TermFallback', $term );
		$this->assertEquals( 'fallbackterm', $term->getText() );
		$this->assertEquals( 'zh', $term->getLanguageCode() );
		$this->assertEquals( 'zh-cn', $term->getActualLanguageCode() );
		$this->assertEquals( 'zh-xy', $term->getSourceLanguageCode() );
	}

	public function testGetLabel_entityNotFound() {
		$termLookup = $this->getTermLookup();
		$fallbackChain = $this->getLanguageFallbackChain( 'zh' );

		$labelDescriptionLookup = new LanguageFallbackLabelDescriptionLookup( $termLookup, $fallbackChain );

		$this->setExpectedException( 'OutOfBoundsException' );
		$labelDescriptionLookup->getLabel( new ItemId( 'Q120' ) );
	}

	public function testGetDescription_entityNotFound() {
		$termLookup = $this->getTermLookup();
		$fallbackChain = $this->getLanguageFallbackChain( 'zh' );

		$labelDescriptionLookup = new LanguageFallbackLabelDescriptionLookup( $termLookup, $fallbackChain );

		$this->setExpectedException( 'OutOfBoundsException' );
		$labelDescriptionLookup->getDescription( new ItemId( 'Q120' ) );
	}

	public function testGetLabel_notFound() {
		$termLookup = $this->getTermLookup();
		$fallbackChain = $this->getLanguageFallbackChain( 'ar' );

		$labelDescriptionLookup = new LanguageFallbackLabelDescriptionLookup( $termLookup, $fallbackChain );

		$this->setExpectedException( 'OutOfBoundsException' );
		$labelDescriptionLookup->getLabel( new ItemId( 'Q116' ) );
	}

	public function testGetDescription_notFound() {
		$termLookup = $this->getTermLookup();
		$fallbackChain = $this->getLanguageFallbackChain( 'ar' );

		$labelDescriptionLookup = new LanguageFallbackLabelDescriptionLookup( $termLookup, $fallbackChain );

		$this->setExpectedException( 'OutOfBoundsException' );
		$labelDescriptionLookup->getDescription( new ItemId( 'Q116' ) );
	}

	private function getLanguageFallbackChain( $languageCode ) {
		$languageFallbackChain = $this->getMockBuilder( 'Wikibase\LanguageFallbackChain' )
			->disableOriginalConstructor()
			->getMock();

		$languageFallbackChain->expects( $this->any() )
			->method( 'extractPreferredValue' )
			->will( $this->returnCallback( function( array $fallbackData ) use ( $languageCode ) {
				if ( $languageCode === 'zh' && array_key_exists( 'zh-cn', $fallbackData ) ) {
					return array( 'value' => 'fallbackterm', 'language' => 'zh-cn', 'source' => 'zh-xy' );
				} else {
					return null;
				}
			} ) );

		$languageFallbackChain->expects( $this->any() )
			->method( 'getFetchLanguageCodes' )
			->will( $this->returnCallback( function() use ( $languageCode ) {
				if ( $languageCode === 'zh' ) {
					return array( 'zh', 'zh-cn', 'zh-xy' );
				} else {
					return array( $languageCode );
				}
			} ) );

		return $languageFallbackChain;
	}

	private function getTermLookup() {
		return new EntityTermLookup( $this->getTermIndex() );
	}

	private function getTermIndex() {
		$terms = array(
			new Term( array(
				'entityId' => 116,
				'entityType' => 'item',
				'termType' => 'label',
				'termLanguage' => 'en',
				'termText' => 'New York City'
			) ),
			new Term( array(
				'entityId' => 116,
				'entityType' => 'item',
				'termType' => 'label',
				'termLanguage' => 'es',
				'termText' => 'New York City'
			) ),
			new Term( array(
				'entityId' => 116,
				'entityType' => 'item',
				'termType' => 'description',
				'termLanguage' => 'en',
				'termText' => 'Big Apple'
			) ),
			new Term( array(
				'entityId' => 117,
				'entityType' => 'item',
				'termType' => 'label',
				'termLanguage' => 'en',
				'termText' => 'Berlin'
			) ),
			new Term( array(
				'entityId' => 118,
				'entityType' => 'item',
				'termType' => 'label',
				'termLanguage' => 'zh-cn',
				'termText' => '测试'
			) ),
			new Term( array(
				'entityId' => 118,
				'entityType' => 'item',
				'termType' => 'description',
				'termLanguage' => 'zh-cn',
				'termText' => 'zh-cn description'
			) ),
		);

		return new MockTermIndex( $terms );
	}

}
