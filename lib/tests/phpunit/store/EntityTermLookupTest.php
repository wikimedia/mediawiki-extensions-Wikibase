<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityTermLookup;

/**
 * @covers Wikibase\Lib\Store\EntityTermLookup
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
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

	public function provideGetLabels() {
		$q116 = new ItemId( 'Q116' );

		return array(
			'all languages' => array(
				$q116,
				array( 'en', 'es' ),
				array(
					'en' => 'New York City',
					'es' => 'Nueva York'
				)
			),
			'some languages' => array(
				$q116,
				array( 'en' ),
				array(
					'en' => 'New York City',
				)
			),
			'no languages' => array(
				$q116,
				array(),
				array()
			),
		);
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

	public function testGetDescription_descriptionNotFoundThrowsException() {
		$termLookup = $this->getEntityTermLookup();

		$this->setExpectedException( 'OutOfBoundsException' );
		$termLookup->getDescription( new ItemId( 'Q116' ), 'fr' );
	}

	public function provideGetDescriptions() {
		$q116 = new ItemId( 'Q116' );

		return array(
			'all languages' => array(
				$q116,
				array( 'de', 'en' ),
				array(
					'de' => 'Metropole an der Ostküste der Vereinigten Staaten',
					'en' => 'largest city in New York and the United States of America',
				)
			),
			'some languages' => array(
				$q116,
				array( 'de' ),
				array(
					'de' => 'Metropole an der Ostküste der Vereinigten Staaten',
				)
			),
			'no languages' => array(
				$q116,
				array(),
				array()
			),
		);
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
