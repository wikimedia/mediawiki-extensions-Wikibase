<?php

namespace Wikibase\Test;

use Language;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\EntityTermLookup;
use Wikibase\Lib\Store\LanguageFallbackLabelLookup;

class LanguageFallbackLabelLookupTest extends \MediaWikiTestCase {

	public function testGetLabel() {
		$languageFallbackChainFactory = new LanguageFallbackChainFactory();
		$fallbackChain = $languageFallbackChainFactory->newFromLanguage( Language::factory( 'zh' ) );

		$termLookup = new EntityTermLookup( $this->getTermIndex() );
		$labelLookup = new LanguageFallbackLabelLookup( $termLookup, $fallbackChain );

		$label = $labelLookup->getLabel( new ItemId( 'Q118' ) );
		$this->assertEquals( '测试', $label );
	}

	public function testGetLabel_notFound() {
		$languageFallbackChainFactory = new LanguageFallbackChainFactory();
		$fallbackChain = $languageFallbackChainFactory->newFromLanguage( Language::factory( 'zh' ) );

		$termLookup = new EntityTermLookup( $this->getTermIndex() );
		$labelLookup = new LanguageFallbackLabelLookup( $termLookup, $fallbackChain );

		$this->setExpectedException( 'OutOfBoundsException' );
		$label = $labelLookup->getLabel( new ItemId( 'Q120' ) );
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
