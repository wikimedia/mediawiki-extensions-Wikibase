<?php

namespace Wikibase\Test;

use Language;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\EntityTermLookup;
use Wikibase\Lib\Store\LanguageFallbackLabelLookup;

/**
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseStore
 */
class LanguageFallbackLabelLookupTest extends \MediaWikiTestCase {

	public function testGetLabel() {
		$termLookup = $this->getTermLookup();
		$fallbackChain = $this->getLanguageFallbackChain( 'zh' );

		$labelLookup = new LanguageFallbackLabelLookup( $termLookup, $fallbackChain );

		$label = $labelLookup->getLabel( new ItemId( 'Q118' ) );
		$this->assertEquals( 'fallbackLabel', $label );
	}

	public function testGetLabel_entityNotFound() {
		$termLookup = $this->getTermLookup();
		$fallbackChain = $this->getLanguageFallbackChain( 'zh' );

		$labelLookup = new LanguageFallbackLabelLookup( $termLookup, $fallbackChain );

		$this->setExpectedException( 'Wikibase\Lib\Store\StorageException' );
		$labelLookup->getLabel( new ItemId( 'Q120' ) );
	}

	public function testGetLabel_notFound() {
		$termLookup = $this->getTermLookup();
		$fallbackChain = $this->getLanguageFallbackChain( 'ar' );

		$labelLookup = new LanguageFallbackLabelLookup( $termLookup, $fallbackChain );

		$this->setExpectedException( 'OutOfBoundsException' );
		$labelLookup->getLabel( new ItemId( 'Q116' ) );
	}

	private function getLanguageFallbackChain( $languageCode ) {
		$languageFallbackChain = $this->getMockBuilder( 'Wikibase\LanguageFallbackChain' )
			->disableOriginalConstructor()
			->getMock();

		$languageFallbackChain->expects( $this->any() )
			->method( 'extractPreferredValue' )
			->will( $this->returnCallback( function( array $fallbackData ) use ( $languageCode ) {
				if ( $languageCode === 'zh' && array_key_exists( 'zh-cn', $fallbackData ) ) {
					return array( 'value' => 'fallbackLabel' );
				} else {
					return null;
				}
			} ) );

		return $languageFallbackChain;
	}

	private function getTermLookup() {
		$entityLookup = new MockRepository();

		return new EntityTermLookup( $this->getTermIndex(), $entityLookup );
	}

	private function getTermIndex() {
		$terms = array(
			new \Wikibase\Term( array(
				'entityId' => 116,
				'entityType' => 'item',
				'termType' => 'label',
				'termLanguage' => 'en',
				'termText' => 'New York City'
			) ),
			new \Wikibase\Term( array(
				'entityId' => 116,
				'entityType' => 'item',
				'termType' => 'label',
				'termLanguage' => 'es',
				'termText' => 'New York City'
			) ),
			new \Wikibase\Term( array(
				'entityId' => 116,
				'entityType' => 'item',
				'termType' => 'description',
				'termLanguage' => 'en',
				'termText' => 'Big Apple'
			) ),
			new \Wikibase\Term( array(
				'entityId' => 117,
				'entityType' => 'item',
				'termType' => 'label',
				'termLanguage' => 'en',
				'termText' => 'Berlin'
			) ),
			new \Wikibase\Term( array(
				'entityId' => 118,
				'entityType' => 'item',
				'termType' => 'label',
				'termLanguage' => 'zh-cn',
				'termText' => '测试'
			) ),
		);

		return new MockTermIndex( $terms );
	}

}
