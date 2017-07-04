<?php

namespace Wikibase\Lib\Tests\Store;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityTermLookup;
use Wikibase\TermIndexEntry;

/**
 * @covers Wikibase\Lib\Store\EntityTermLookup
 * @covers Wikibase\Lib\Store\EntityTermLookupBase
 *
 * @group Wikibase
 * @group WikibaseStore
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class EntityTermLookupTest extends \MediaWikiTestCase {

	public function testGetLabel() {
		$termLookup = $this->getEntityTermLookup();

		$label = $termLookup->getLabel( new ItemId( 'Q116' ), 'en' );
		$this->assertEquals( 'New York City', $label );
	}

	public function testWhenNoLabelFound_getLabelReturnsNull() {
		$termLookup = $this->getEntityTermLookup();
		$this->assertNull( $termLookup->getLabel( new ItemId( 'Q116' ), 'fa' ) );
	}

	public function provideGetLabels() {
		$q116 = new ItemId( 'Q116' );

		return [
			'all languages' => [
				$q116,
				[ 'en', 'es' ],
				[
					'en' => 'New York City',
					'es' => 'Nueva York'
				]
			],
			'some languages' => [
				$q116,
				[ 'en' ],
				[
					'en' => 'New York City',
				]
			],
			'no languages' => [
				$q116,
				[],
				[]
			],
		];
	}

	/**
	 * @dataProvider provideGetLabels
	 */
	public function testGetLabels( ItemId $itemId, $languages, array $expected ) {
		$termLookup = $this->getEntityTermLookup();

		$labels = $termLookup->getLabels( $itemId, $languages );
		$this->assertEquals( $expected, $labels );
	}

	public function testGetDescription() {
		$termLookup = $this->getEntityTermLookup();

		$description = $termLookup->getDescription( new ItemId( 'Q116' ), 'de' );
		$expected = 'Metropole an der Ostküste der Vereinigten Staaten';

		$this->assertEquals( $expected, $description );
	}

	public function testWhenNoDescriptionFound_getDescriptionReturnsNull() {
		$termLookup = $this->getEntityTermLookup();
		$this->assertNull( $termLookup->getDescription( new ItemId( 'Q116' ), 'fr' ) );
	}

	public function provideGetDescriptions() {
		$q116 = new ItemId( 'Q116' );

		return [
			'all languages' => [
				$q116,
				[ 'de', 'en' ],
				[
					'de' => 'Metropole an der Ostküste der Vereinigten Staaten',
					'en' => 'largest city in New York and the United States of America',
				]
			],
			'some languages' => [
				$q116,
				[ 'de' ],
				[
					'de' => 'Metropole an der Ostküste der Vereinigten Staaten',
				]
			],
			'no languages' => [
				$q116,
				[],
				[]
			],
		];
	}

	/**
	 * @dataProvider provideGetDescriptions
	 */
	public function testGetDescriptions( ItemId $itemId, $languages, array $expected ) {
		$termLookup = $this->getEntityTermLookup();

		$descriptions = $termLookup->getDescriptions( $itemId, $languages );
		$this->assertEquals( $expected, $descriptions );
	}

	protected function getEntityTermLookup() {
		$termIndex = $this->getTermIndex();
		return new EntityTermLookup( $termIndex );
	}

	protected function getTermIndex() {
		$terms = [
			new TermIndexEntry( [
				'entityId' => new ItemId( 'Q116' ),
				'termType' => 'label',
				'termLanguage' => 'en',
				'termText' => 'New York City'
			] ),
			new TermIndexEntry( [
				'entityId' => new ItemId( 'Q116' ),
				'termType' => 'label',
				'termLanguage' => 'es',
				'termText' => 'Nueva York'
			] ),
			new TermIndexEntry( [
				'entityId' => new ItemId( 'Q116' ),
				'termType' => 'description',
				'termLanguage' => 'en',
				'termText' => 'largest city in New York and the United States of America'
			] ),
			new TermIndexEntry( [
				'entityId' => new ItemId( 'Q116' ),
				'termType' => 'description',
				'termLanguage' => 'de',
				'termText' => 'Metropole an der Ostküste der Vereinigten Staaten'
			] ),
		];

		return new MockTermIndex( $terms );
	}

}
