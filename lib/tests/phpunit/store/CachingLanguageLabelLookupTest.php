<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\CachingLanguageLabelLookup;

class CachingLanguageLabelLookupTest extends \MediaWikiTestCase {

	public function testGetLabel() {
		$entityLookup = $this->getEntityLookup();
		$labelLookup = new CachingLanguageLabelLookup( $entityLookup, 'en' );

		$label = $labelLookup->getLabel( new ItemId( 'Q116' ) );

		$this->assertEquals( 'New York City', $label );
	}

	/**
	 * @dataProvider getLabel_notFoundProvider
	 */
	public function testGetLabel_notFound( $entityId, $languageCode ) {
		$entityLookup = $this->getEntityLookup();
		$labelLookup = new CachingLanguageLabelLookup( $entityLookup, $languageCode );

		$this->setExpectedException( 'OutOfBoundsException' );
		$labelLookup->getLabel( new ItemId( 'Q120' ) );
	}

	public function getLabel_notFoundProvider() {
		return array(
			array( new ItemId( 'Q120' ), 'en' ),
			array( new ItemId( 'Q116' ), 'fa' )
		);
	}

	private function getEntityLookup() {
		$mockRepo = new MockRepository();

		$item = Item::newEmpty();
		$item->setId( new ItemId( 'Q116' ) );
		$item->setLabel( 'en', 'New York City' );

		$mockRepo->putEntity( $item );

		return $mockRepo;
	}

}
