<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityTermLookup;

/**
 * @covers Wikibase\Lib\Store\EntityTermLookup
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert
 */
class EntityTermLookupTest extends \MediaWikiTestCase {

	public function testGetLabel() {
		$termLookup = $this->getEntityTermLookup();

		$label = $termLookup->getLabel( new ItemId( 'Q116' ), 'en' );
		$this->assertEquals( 'New York City', $label );
	}

	public function testGetLabel_noLabelFoundThrowsException() {
		$termLookup = $this->getEntityTermLookup();

		$this->setExpectedException( 'OutOfBoundsException' );
		$termLookup->getLabel( new ItemId( 'Q116' ), 'fa' );
	}

	public function testGetLabels() {
		$termLookup = $this->getEntityTermLookup();

		$expected =  array(
			'en' => 'New York City',
			'es' => 'Nueva York'
		);

		$labels = $termLookup->getLabels( new ItemId( 'Q116' ) );
		$this->assertEquals( $expected, $labels );
	}

	public function testGetLabels_entityNotFoundThrowsStorageException() {
		$termLookup = $this->getEntityTermLookup();

		$this->setExpectedException( 'Wikibase\Lib\Store\StorageException' );

		$termLookup->getLabels( new ItemId( 'Q9999' ) );
	}


	public function testGetDescription() {
		$termLookup = $this->getEntityTermLookup();

		$description = $termLookup->getDescription( new ItemId( 'Q116' ), 'de' );
		$expected = 'Metropole an der Ostküste der Vereinigten Staaten';

		$this->assertEquals( $expected, $description );
	}

	public function testGetDescription_descriptionNotFoundThrowsException() {
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
		$entityLookup = $this->getMockBuilder( 'Wikibase\Lib\Store\EntityLookup' )
			->disableOriginalConstructor()
			->getMock();

		$entityLookup->expects( $this->any() )
			->method( 'hasEntity' )
			->will( $this->returnCallback( function( EntityId $entityId ) {
				return $entityId->getSerialization() === 'Q116';
			} ) );

		$termIndex = $this->getTermIndex();
		return new EntityTermLookup( $termIndex, $entityLookup );
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
				'termText' => 'Nueva York'
			) ),
			new \Wikibase\Term( array(
				'entityId' => 116,
				'entityType' => 'item',
				'termType' => 'description',
				'termLanguage' => 'en',
				'termText' => 'largest city in New York and the United States of America'
			) ),
			new \Wikibase\Term( array(
				'entityId' => 116,
				'entityType' => 'item',
				'termType' => 'description',
				'termLanguage' => 'de',
				'termText' => 'Metropole an der Ostküste der Vereinigten Staaten'
			) ),
		);

		return new MockTermIndex( $terms );
	}

}
