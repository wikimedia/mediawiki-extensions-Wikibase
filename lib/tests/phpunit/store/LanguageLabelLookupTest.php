<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityTermLookup;
use Wikibase\Lib\Store\LanguageLabelLookup;

/**
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseStore
 */
class LanguageLabelLookupTest extends \MediaWikiTestCase {

	public function testGetLabel() {
		$termLookup = $this->getTermLookup();
		$labelLookup = new LanguageLabelLookup( $termLookup, 'en' );

		$label = $labelLookup->getLabel( new ItemId( 'Q116' ) );

		$this->assertEquals( 'New York City', $label );
	}

	public function testGetLabel_entityNotFound() {
		$termLookup = $this->getTermLookup();
		$labelLookup = new LanguageLabelLookup( $termLookup, 'en' );

		$this->setExpectedException( 'Wikibase\Lib\Store\StorageException' );
		$labelLookup->getLabel( new ItemId( 'Q120' ) );
	}

	public function testGetLabel_notFound() {
		$termLookup = $this->getTermLookup();
		$labelLookup = new LanguageLabelLookup( $termLookup, 'fa' );

		$this->setExpectedException( 'OutOfBoundsException' );

		$labelLookup->getLabel( new ItemId( 'Q116' ) );
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
