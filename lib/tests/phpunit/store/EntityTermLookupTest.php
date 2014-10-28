<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityTermLookup;

class EntityTermLookupTest extends \MediaWikiTestCase {

	public function testGetLabel() {
		$termIndex = $this->getTermIndex();
		$termLookup = new EntityTermLookup( $termIndex );

		$label = $termLookup->getLabel( new ItemId( 'Q116' ), 'en' );

		$this->assertEquals( 'New York City', $label );
	}

	public function testGetLabel_notFound() {
		$termIndex = $this->getTermIndex();
		$termLookup = new EntityTermLookup( $termIndex, 'en' );

		$this->setExpectedException( 'OutOfBoundsException' );
		$label = $termLookup->getLabel( new ItemId( 'Q120' ), 'en' );
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
