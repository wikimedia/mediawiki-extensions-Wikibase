<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityTermLookup;

class EntityTermLookupTest extends \MediaWikiTestCase {

	public function testGetLabel() {
		$termLookup = $this->getEntityTermLookup();

		$label = $termLookup->getLabel( new ItemId( 'Q116' ), 'en' );
		$this->assertEquals( 'New York City', $label );
	}

	public function testGetLabel_notFoundThrowsException() {
		$termLookup = $this->getEntityTermLookup();

		$this->setExpectedException( 'OutOfBoundsException' );
		$termLookup->getLabel( new ItemId( 'Q120' ), 'en' );
	}

	/**
	 * @dataProvider getLabelsProvider
	 */
	public function testGetLabels( $expected, EntityId $entityId ) {
		$termLookup = $this->getEntityTermLookup();

		$labels = $termLookup->getLabels( $entityId );
		$this->assertEquals( $expected, $labels );
	}

	public function getLabelsProvider() {
		return array(
			array(
				array( 'en' => 'New York City', 'es' => 'Nueva York' ),
				new ItemId( 'Q116' )
			),
			array(
				array(),
				new ItemId( 'Q120' )
			)
		);
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
		$termLookup->getDescription( new ItemId( 'Q90000' ), 'fr' );
	}

	/**
	 * @dataProvider getDescriptionsProvider
	 */
	public function getDescriptions( $expected, EntityId $entityId ) {
		$termLookup = $this->getEntityTermLookup();

		$descriptions = $termLookup->getDescriptions( $entityId );
		$this->assertEquals( $expected, $descriptions );
	}

	public function getDescriptionsProvider() {
		return array(
			array(
				array(
					'de' => 'Metropole an der Ostküste der Vereinigten Staaten',
					'en' => 'largest city in New York and the United States of America',
				),
				new ItemId( 'Q116' )
			),
			array(
				array(),
				new ItemId( 'Q90001' )
			)
		);
	}

	private function getEntityTermLookup() {
		$termIndex = $this->getTermIndex();
		return new EntityTermLookup( $termIndex );
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
			new \Wikibase\Term( array(
				'entityId' => 117,
				'entityType' => 'item',
				'termType' => 'label',
				'termLanguage' => 'en',
				'termText' => 'Berlin'
			) ),
		);

		return new MockTermIndex( $terms );
	}

}
