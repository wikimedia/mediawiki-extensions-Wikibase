<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityRetrievingTermLookup;

class EntityRetrievingTermLookupTest extends \PHPUnit_Framework_TestCase {

	public function testGetLabel() {
		$termLookup = $this->getEntityTermLookup();

		$label = $termLookup->getLabel( new ItemId( 'Q116' ), 'en' );
		$this->assertEquals( 'New York City', $label );
	}

	public function testGetLabel_notFoundThrowsException() {
		$termLookup = $this->getEntityTermLookup();

		$this->setExpectedException( 'OutOfBoundsException' );
		$termLookup->getLabel( new ItemId( 'Q116' ), 'fa' );
	}

	public function testGetLabel_entityNotFound() {
		$termLookup = $this->getEntityTermLookup();

		$this->setExpectedException( '\Wikibase\Lib\Store\StorageException' );
		$termLookup->getLabel( new ItemId( 'Q120' ), 'en' );
	}

	public function testGetLabels() {
		$termLookup = $this->getEntityTermLookup();

		$labels = $termLookup->getLabels( new ItemId( 'Q116' ) );

		$expected = array(
			'en' => 'New York City',
			'es' => 'Nueva York'
		);

		$this->assertEquals( $expected, $labels );
	}

	public function testGetDescription() {
		$termLookup = $this->getEntityTermLookup();

		$description = $termLookup->getDescription( new ItemId( 'Q116' ), 'de' );
		$expected = 'Metropole an der Ostküste der Vereinigten Staaten';

		$this->assertEquals( $expected, $description );
	}

	public function testGetDescription_notFoundThrowsException() {
		$termLookup = $this->getEntityTermLookup();

		$this->setExpectedException( 'OutOfBoundsException' );
		$termLookup->getDescription( new ItemId( 'Q116' ), 'fr' );
	}

	public function getDescriptions() {
		$termLookup = $this->getEntityTermLookup();

		$descriptions = $termLookup->getDescriptions( new ItemId( 'Q116' ) );

		$expected = array(
			'de' => 'Metropole an der Ostküste der Vereinigten Staaten',
			'en' => 'largest city in New York and the United States of America',
		);

		$this->assertEquals( $expected, $descriptions );
	}

	private function getEntityTermLookup() {
		return new EntityRetrievingTermLookup( $this->getEntityLookup() );
	}

	private function getEntityLookup() {
		$mockRepository = new MockRepository();

		$item = Item::newEmpty();
		$item->setId( new ItemId( 'Q116' ) );

		$item->setLabel( 'en', 'New York City' );
		$item->setLabel( 'es', 'Nueva York' );

		$item->setDescription( 'de', 'Metropole an der Ostküste der Vereinigten Staaten' );
		$item->setDescription( 'en', 'largest city in New York and the United States of America' );

		$mockRepository->putEntity( $item );

		return $mockRepository;
	}

}
