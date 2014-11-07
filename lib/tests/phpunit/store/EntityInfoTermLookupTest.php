<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityInfoTermLookup;

/**
 * @covers Wikibase\Lib\Store\EntityInfoTermLookup
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert
 * @author Daniel Kinzler
 */
class EntityInfoTermLookupTest extends \MediaWikiTestCase {

	public function testGetLabel() {
		$termLookup = $this->getEntityInfoTermLookup();

		$label = $termLookup->getLabel( new ItemId( 'Q116' ), 'en' );
		$this->assertEquals( 'New York City', $label );
	}

	public function testGetLabel_notFoundThrowsException() {
		$termLookup = $this->getEntityInfoTermLookup();

		$this->setExpectedException( 'OutOfBoundsException' );
		$termLookup->getLabel( new ItemId( 'Q120' ), 'en' );
	}

	/**
	 * @dataProvider getLabelsProvider
	 */
	public function testGetLabels( $expected, EntityId $entityId ) {
		$termLookup = $this->getEntityInfoTermLookup();

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
		$termLookup = $this->getEntityInfoTermLookup();

		$description = $termLookup->getDescription( new ItemId( 'Q116' ), 'de' );
		$expected = 'Metropole an der Ostküste der Vereinigten Staaten';

		$this->assertEquals( $expected, $description );
	}

	public function testGetDescription_notFoundThrowsException() {
		$termLookup = $this->getEntityInfoTermLookup();

		$this->setExpectedException( 'OutOfBoundsException' );
		$termLookup->getDescription( new ItemId( 'Q90000' ), 'fr' );
	}

	/**
	 * @dataProvider getDescriptionsProvider
	 */
	public function getDescriptions( $expected, EntityId $entityId ) {
		$termLookup = $this->getEntityInfoTermLookup();

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

	private function getEntityInfoTermLookup() {
		$entityInfo = $this->makeEntityInfo();
		return new EntityInfoTermLookup( $entityInfo );
	}

	private function makeEntityInfo() {
		$entityInfo = array(
			'Q116' => array(
				'labels' => array(
					'en' => array( 'language' => 'en', 'value' => 'New York City' ),
					'es' => 'Nueva York', // terse form also supported
				),
				'descriptions' => array(
					'en' => array( 'language' => 'en', 'value' => 'largest city in New York and the United States of America' ),
					'de' => array( 'language' => 'de', 'value' => 'Metropole an der Ostküste der Vereinigten Staaten' ),
				),
			),

			'Q117' => array(
				'labels' => array(
					'de' => array( 'language' => 'de', 'value' => 'Berlin' ),
				),
			),
		);

		return $entityInfo;
	}

}
