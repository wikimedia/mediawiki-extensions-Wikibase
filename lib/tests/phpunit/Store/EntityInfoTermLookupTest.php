<?php

namespace Wikibase\Lib\Tests\Store;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\TermLookupException;
use Wikibase\Lib\Store\EntityInfo;
use Wikibase\Lib\Store\EntityInfoTermLookup;

/**
 * @covers Wikibase\Lib\Store\EntityInfoTermLookup
 *
 * @group Wikibase
 * @group WikibaseStore
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 */
class EntityInfoTermLookupTest extends \MediaWikiTestCase {

	public function testGetLabel() {
		$termLookup = $this->getEntityInfoTermLookup();

		$label = $termLookup->getLabel( new ItemId( 'Q116' ), 'en' );
		$this->assertEquals( 'New York City', $label );
	}

	public function testWhenLabelNotFound_getLabelReturnsNull() {
		$termLookup = $this->getEntityInfoTermLookup();
		$this->assertNull( $termLookup->getLabel( new ItemId( 'Q117' ), 'fr' ) );
	}

	public function testWhenEntityNotFound_getLabelThrowsException() {
		$termLookup = $this->getEntityInfoTermLookup();

		$this->setExpectedException( TermLookupException::class );
		$termLookup->getLabel( new ItemId( 'Q90000' ), 'en' );
	}

	public function getLabelsProvider() {
		return [
			[
				[ 'en' => 'New York City', 'es' => 'Nueva York' ],
				new ItemId( 'Q116' ),
				[ 'en', 'es' ]
			],
			[
				[ 'es' => 'Nueva York' ],
				new ItemId( 'Q116' ),
				[ 'es' ]
			],
			[
				[ 'de' => 'Berlin' ],
				new ItemId( 'Q117' ),
				[ 'de' ]
			]
		];
	}

	/**
	 * @dataProvider getLabelsProvider
	 */
	public function testGetLabels( $expected, EntityId $entityId, $languages ) {
		$termLookup = $this->getEntityInfoTermLookup();

		$labels = $termLookup->getLabels( $entityId, $languages );
		$this->assertEquals( $expected, $labels );
	}

	public function testGetLabels_noEntityThrowsException() {
		$termLookup = $this->getEntityInfoTermLookup();

		$this->setExpectedException( TermLookupException::class );
		$termLookup->getLabels( new ItemId( 'Q90000' ), [ 'x' ] );
	}

	public function testGetDescription() {
		$termLookup = $this->getEntityInfoTermLookup();

		$description = $termLookup->getDescription( new ItemId( 'Q116' ), 'de' );
		$expected = 'Metropole an der Ostküste der Vereinigten Staaten';

		$this->assertEquals( $expected, $description );
	}

	public function testWhenDescriptionNotFound_getDescriptionReturnsNull() {
		$termLookup = $this->getEntityInfoTermLookup();
		$this->assertNull( $termLookup->getDescription( new ItemId( 'Q117' ), 'fr' ) );
	}

	public function testWhenEntityNotFound_getDescriptionThrowsException() {
		$termLookup = $this->getEntityInfoTermLookup();

		$this->setExpectedException( TermLookupException::class );
		$termLookup->getDescription( new ItemId( 'Q90000' ), 'en' );
	}

	public function getDescriptionsProvider() {
		return [
			[
				[
					'de' => 'Metropole an der Ostküste der Vereinigten Staaten',
					'en' => 'largest city in New York and the United States of America',
				],
				new ItemId( 'Q116' ),
				[ 'de', 'en' ]
			],
			[
				[
					'de' => 'Metropole an der Ostküste der Vereinigten Staaten',
				],
				new ItemId( 'Q116' ),
				[ 'de', 'fr' ]
			],
			[
				[],
				new ItemId( 'Q117' ),
				[]
			]
		];
	}

	/**
	 * @dataProvider getDescriptionsProvider
	 */
	public function testGetDescriptions( $expected, EntityId $entityId, $languages ) {
		$termLookup = $this->getEntityInfoTermLookup();

		$descriptions = $termLookup->getDescriptions( $entityId, $languages );
		$this->assertEquals( $expected, $descriptions );
	}

	public function testGetDescriptions_noEntityThrowsException() {
		$termLookup = $this->getEntityInfoTermLookup();

		$this->setExpectedException( TermLookupException::class );
		$termLookup->getDescriptions( new ItemId( 'Q90000' ), [ 'x' ] );
	}

	private function getEntityInfoTermLookup() {
		$entityInfo = $this->makeEntityInfo();
		return new EntityInfoTermLookup( $entityInfo );
	}

	private function makeEntityInfo() {
		$entityInfo = [
			'Q116' => [
				'labels' => [
					'en' => [ 'language' => 'en', 'value' => 'New York City' ],
					'es' => [ 'language' => 'es', 'value' => 'Nueva York' ],
				],
				'descriptions' => [
					'en' => [ 'language' => 'en', 'value' => 'largest city in New York and the United States of America' ],
					'de' => [ 'language' => 'de', 'value' => 'Metropole an der Ostküste der Vereinigten Staaten' ],
				],
			],

			'Q117' => [
				'labels' => [
					'de' => [ 'language' => 'de', 'value' => 'Berlin' ],
				],
				'descriptions' => []
			],
		];

		return new EntityInfo( $entityInfo );
	}

}
