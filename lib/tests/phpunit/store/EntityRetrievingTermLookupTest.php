<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityRetrievingTermLookup;

/**
 * @covers Wikibase\Lib\Store\EntityRetrievingTermLookup
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseStore
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 */
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

		$this->setExpectedException( 'OutOfBoundsException' );
		$termLookup->getLabel( new ItemId( 'Q120' ), 'en' );
	}

	public function getLabelsProvider() {
		return array(
			array(
				array( 'en' => 'New York City', 'es' => 'Nueva York' ),
				new ItemId( 'Q116' ),
				array( 'en', 'es' )
			),
			array(
				array( 'es' => 'Nueva York' ),
				new ItemId( 'Q116' ),
				array( 'es' )
			),
			array(
				array( 'de' => 'Berlin' ),
				new ItemId( 'Q117' ),
				array( 'de' )
			)
		);
	}

	/**
	 * @dataProvider getLabelsProvider
	 */
	public function testGetLabels( array $expected, ItemId $itemId, array $languageCodes ) {
		$termLookup = $this->getEntityTermLookup();

		$labels = $termLookup->getLabels( $itemId, $languageCodes );
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


	public function getDescriptionsProvider() {
		return array(
			array(
				array(
					'de' => 'Metropole an der Ostküste der Vereinigten Staaten',
					'en' => 'largest city in New York and the United States of America',
				),
				new ItemId( 'Q116' ),
				array( 'de', 'en' )
			),
			array(
				array(
					'de' => 'Metropole an der Ostküste der Vereinigten Staaten',
				),
				new ItemId( 'Q116' ),
				array( 'de', 'fr' )
			),
			array(
				array(),
				new ItemId( 'Q117' ),
				array()
			)
		);
	}

	/**
	 * @dataProvider getDescriptionsProvider
	 */
	public function testGetDescriptions( array $expected, ItemId $itemId, array $languageCodes ) {
		$termLookup = $this->getEntityTermLookup();

		$descriptions = $termLookup->getDescriptions( $itemId, $languageCodes );
		$this->assertEquals( $expected, $descriptions );
	}

	private function getEntityTermLookup() {
		return new EntityRetrievingTermLookup( $this->getEntityLookup() );
	}

	private function getEntityLookup() {
		$mockRepo = new MockRepository();

		$item = new Item( new ItemId( 'Q116' ) );

		$item->setLabel( 'en', 'New York City' );
		$item->setLabel( 'es', 'Nueva York' );

		$item->setDescription( 'de', 'Metropole an der Ostküste der Vereinigten Staaten' );
		$item->setDescription( 'en', 'largest city in New York and the United States of America' );

		$mockRepo->putEntity( $item );

		$item = new Item( new ItemId( 'Q117' ) );

		$item->setLabel( 'de', 'Berlin' );

		$mockRepo->putEntity( $item );

		return $mockRepo;
	}

}
