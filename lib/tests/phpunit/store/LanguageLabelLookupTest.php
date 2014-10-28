<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityTermLookup;
use Wikibase\Lib\Store\LanguageLabelLookup;

class LanguageLabelLookupTest extends \MediaWikiTestCase {

	public function testGetLabel() {
		$termLookup = new EntityTermLookup( $this->getTermIndex() );
		$labelLookup = new LanguageLabelLookup( $termLookup, 'en' );

		$label = $labelLookup->getLabel( new ItemId( 'Q116' ) );

		$this->assertEquals( 'New York City', $label );
	}

	public function testGetLabel_notFound() {
		$termLookup = new EntityTermLookup( $this->getTermIndex() );
		$labelLookup = new LanguageLabelLookup( $termLookup, 'en' );

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
