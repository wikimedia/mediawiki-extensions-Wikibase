<?php

namespace Wikibase\Test;

use Language;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\LabelLookup;

class LabelLookupTest extends \MediaWikiTestCase {

	public function testGetLabel() {
		$termLookup = $this->getTermLookup();
		$labelLookup = new LabelLookup( $termLookup, 'en' );

		$label = $labelLookup->getLabel( new ItemId( 'Q116' ), 'en' );

		$this->assertEquals( 'New York City', $label );
	}

	public function testGetLabel_notFound() {
		$termLookup = $this->getTermLookup();
		$labelLookup = new LabelLookup( $termLookup, 'en' );

		$this->setExpectedException( 'OutOfBoundsException' );
		$label = $labelLookup->getLabel( new ItemId( 'Q120' ), 'en' );
	}

	public function testGetLabelForFallbackChain() {
		$languageFallbackChainFactory = new LanguageFallbackChainFactory();
		$fallbackChain = $languageFallbackChainFactory->newFromLanguage( Language::factory( 'zh' ) );

		$termLookup = $this->getTermLookup();
		$labelLookup = new LabelLookup( $termLookup, 'en' );

		$label = $labelLookup->getLabelForFallbackChain( new ItemId( 'Q118' ), $fallbackChain );
		$this->assertEquals( '测试', $label );
	}

	private function getTermLookup() {
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
