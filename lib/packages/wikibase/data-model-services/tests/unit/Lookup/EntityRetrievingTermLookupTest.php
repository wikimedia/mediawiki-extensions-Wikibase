<?php

namespace Wikibase\DataModel\Services\Tests\Lookup;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\EntityLookupException;
use Wikibase\DataModel\Services\Lookup\EntityRetrievingTermLookup;
use Wikibase\DataModel\Services\Lookup\InMemoryEntityLookup;
use Wikibase\DataModel\Services\Lookup\TermLookupException;

/**
 * @covers \Wikibase\DataModel\Services\Lookup\EntityRetrievingTermLookup
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 */
class EntityRetrievingTermLookupTest extends TestCase {

	public function testGetLabel() {
		$termLookup = $this->getEntityRetrievingTermLookup();

		$label = $termLookup->getLabel( new ItemId( 'Q116' ), 'en' );
		$this->assertEquals( 'New York City', $label );
	}

	public function testGetLabel_nullOnNoLabel() {
		$termLookup = $this->getEntityRetrievingTermLookup();

		$this->assertNull( $termLookup->getLabel( new ItemId( 'Q116' ), 'fa' ) );
	}

	public function testGetLabel_entityNotFoundThrowsException() {
		$termLookup = $this->getEntityRetrievingTermLookup();

		$this->expectException( TermLookupException::class );
		$termLookup->getLabel( new ItemId( 'Q120' ), 'en' );
	}

	public function testGetLabel_entityLookupExceptionGetsHandled() {
		$termLookup = $this->getEntityRetrievingTermLookup();

		$this->expectException( TermLookupException::class );
		$termLookup->getLabel( new ItemId( 'Q503' ), 'en' );
	}

	public function getLabelsProvider() {
		return [
			[
				[ 'en' => 'New York City', 'es' => 'Nueva York' ],
				new ItemId( 'Q116' ),
				[ 'en', 'es' ],
			],
			[
				[ 'es' => 'Nueva York' ],
				new ItemId( 'Q116' ),
				[ 'es' ],
			],
			[
				[ 'de' => 'Berlin' ],
				new ItemId( 'Q117' ),
				[ 'de' ],
			],
		];
	}

	/**
	 * @dataProvider getLabelsProvider
	 */
	public function testGetLabels( array $expected, ItemId $itemId, array $languageCodes ) {
		$termLookup = $this->getEntityRetrievingTermLookup();

		$labels = $termLookup->getLabels( $itemId, $languageCodes );
		$this->assertEquals( $expected, $labels );
	}

	public function testGetDescription() {
		$termLookup = $this->getEntityRetrievingTermLookup();

		$description = $termLookup->getDescription( new ItemId( 'Q116' ), 'de' );
		$expected = 'Metropole an der Ostküste der Vereinigten Staaten';

		$this->assertEquals( $expected, $description );
	}

	public function testGetDescription_entityNotFoundThrowsException() {
		$termLookup = $this->getEntityRetrievingTermLookup();

		$this->expectException( TermLookupException::class );
		$termLookup->getDescription( new ItemId( 'Q120' ), 'en' );
	}

	public function testGetDescription_entityLookupExceptionGetHandled() {
		$termLookup = $this->getEntityRetrievingTermLookup();

		$this->expectException( TermLookupException::class );
		$termLookup->getDescription( new ItemId( 'Q503' ), 'en' );
	}

	public function testGetDescription_nullOnNoDescription() {
		$termLookup = $this->getEntityRetrievingTermLookup();

		$this->assertNull( $termLookup->getDescription( new ItemId( 'Q116' ), 'fr' ) );
	}

	public function getDescriptionsProvider() {
		return [
			[
				[
					'de' => 'Metropole an der Ostküste der Vereinigten Staaten',
					'en' => 'largest city in New York and the United States of America',
				],
				new ItemId( 'Q116' ),
				[ 'de', 'en' ],
			],
			[
				[
					'de' => 'Metropole an der Ostküste der Vereinigten Staaten',
				],
				new ItemId( 'Q116' ),
				[ 'de', 'fr' ],
			],
			[
				[],
				new ItemId( 'Q117' ),
				[],
			],
		];
	}

	/**
	 * @dataProvider getDescriptionsProvider
	 */
	public function testGetDescriptions( array $expected, ItemId $itemId, array $languageCodes ) {
		$termLookup = $this->getEntityRetrievingTermLookup();

		$descriptions = $termLookup->getDescriptions( $itemId, $languageCodes );
		$this->assertEquals( $expected, $descriptions );
	}

	/**
	 * @return EntityRetrievingTermLookup
	 */
	private function getEntityRetrievingTermLookup() {
		return new EntityRetrievingTermLookup( $this->getEntityLookup() );
	}

	/**
	 * @return EntityLookup
	 */
	private function getEntityLookup() {
		$entityLookup = new InMemoryEntityLookup();
		$entityLookup->addException( new EntityLookupException( new ItemId( 'Q503' ) ) );

		$item = new Item( new ItemId( 'Q116' ) );

		$item->setLabel( 'en', 'New York City' );
		$item->setLabel( 'es', 'Nueva York' );

		$item->setDescription( 'de', 'Metropole an der Ostküste der Vereinigten Staaten' );
		$item->setDescription( 'en', 'largest city in New York and the United States of America' );

		$entityLookup->addEntity( $item );

		$item = new Item( new ItemId( 'Q117' ) );

		$item->setLabel( 'de', 'Berlin' );

		$entityLookup->addEntity( $item );

		return $entityLookup;
	}

}
