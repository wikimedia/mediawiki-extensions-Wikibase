<?php

namespace Wikibase\Lib\Test;

use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Lib\Store\GenericEntityInfoBuilder;
use Wikibase\Test\MockRepository;

/**
 * @covers Wikibase\Lib\Store\GenericEntityInfoBuilder
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseEntityLookup
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class GenericEntityInfoBuilderTest extends \MediaWikiTestCase {

	/**
	 * @param array $ids
	 *
	 * @return GenericEntityInfoBuilder
	 */
	private function newEntityInfoBuilder( array $ids ) {
		$repo = new MockRepository();

		$q2 = Item::newEmpty();
		$q2->setId( new ItemId( 'Q2' ) );
		$q2->setLabel( 'en', 'two' );
		$q2->setLabel( 'de', 'zwei' );
		$repo->putEntity( $q2 );

		$q3 = Item::newEmpty();
		$q3->setId( new ItemId( 'Q3' ) );
		$q3->setLabel( 'en', 'three' );
		$q3->setLabel( 'de', 'drei' );
		$q3->setDescription( 'en', 'the third' );
		$repo->putEntity( $q3 );

		$p3 = Property::newFromType( 'string' );
		$p3->setId( new PropertyId( 'P3' ) );
		$repo->putEntity( $p3 );

		$idParser = new BasicEntityIdParser();

		return new GenericEntityInfoBuilder( $ids, $idParser, $repo );
	}

	public function provideBuildEntityInfo() {
		return array(
			array(
				array(),
				array()
			),

			array(
				array(
					new ItemId( 'Q2' ),
					new PropertyId( 'P3' )
				),
				array(
					'Q2' => array( 'id' => 'Q2', 'type' => Item::ENTITY_TYPE ),
					'P3' => array( 'id' => 'P3', 'type' => Property::ENTITY_TYPE ),
				)
			),

			array(
				array(
					new ItemId( 'Q2' ),
					new ItemId( 'Q7' ),
				),
				array(
					'Q2' => array( 'id' => 'Q2', 'type' => Item::ENTITY_TYPE ),
					'Q7' => array( 'id' => 'Q7', 'type' => Item::ENTITY_TYPE ),
				)
			),
		);
	}

	/**
	 * @dataProvider provideBuildEntityInfo
	 */
	public function testGetEntityInfo( array $ids, array $expected ) {
		$builder = $this->newEntityInfoBuilder( $ids );
		$actual = $builder->getEntityInfo();

		$this->assertArrayEquals( $expected, $actual, false, true );
	}

	public function provideCollectTerms() {
		return array(
			array(
				array(
					new ItemId( 'Q2' ),
					new ItemId( 'Q3' ),
					new ItemId( 'Q7' ),
				),
				null,
				null,
				array(
					'Q2' => array( 'id' => 'Q2', 'type' => Item::ENTITY_TYPE,
						'labels' => array( 'en' => array( 'language' => 'en', 'value' => 'two' ),
											'de' => array( 'language' => 'de', 'value' => 'zwei' ), ),
						'descriptions' => array(),
						'aliases' => array(),
					),
					'Q3' => array( 'id' => 'Q3', 'type' => Item::ENTITY_TYPE,
						'labels' => array( 'en' => array( 'language' => 'en', 'value' => 'three' ),
											'de' => array( 'language' => 'de', 'value' => 'drei' ) ),
						'descriptions' => array( 'en' => array( 'language' => 'en', 'value' => 'the third' ) ),
						'aliases' => array(),
					),
					'Q7' => array( 'id' => 'Q7', 'type' => Item::ENTITY_TYPE,
						'labels' => array(),
						'descriptions' => array(),
						'aliases' => array() ),
				)
			),

			array(
				array(
					new ItemId( 'Q3' ),
				),
				array( 'label' ),
				array( 'de' ),
				array(
					'Q3' => array( 'id' => 'Q3', 'type' => Item::ENTITY_TYPE,
						'labels' => array( 'de' => array( 'language' => 'de', 'value' => 'drei' ) ),
					),
				)
			),
		);
	}

	/**
	 * @dataProvider provideCollectTerms
	 */
	public function testCollectTerms( array $ids, array $types = null, array $languages = null, array $expected = null ) {
		$builder = $this->newEntityInfoBuilder( $ids );
		$builder->collectTerms( $types, $languages );
		$entityInfo = $builder->getEntityInfo();

		foreach ( $expected as $id => $expectedRecord ) {
			$this->assertArrayHasKey( $id, $entityInfo );
			$actualRecord = $entityInfo[$id];

			$this->assertArrayEquals( $expectedRecord, $actualRecord, false, true );
		}
	}

	public function provideCollectDataTypes() {
		return array(
			array(
				array(
					new PropertyId( 'P4' ),
					new PropertyId( 'P7' ),
					new ItemId( 'Q7' ),
				),
				array(
					'P4' => array( 'id' => 'P4', 'type' => Property::ENTITY_TYPE, 'datatype' => 'string' ),
					'P7' => array( 'id' => 'P7', 'type' => Property::ENTITY_TYPE, 'datatype' => null ),
					'Q7' => array( 'id' => 'Q7', 'type' => Item::ENTITY_TYPE ),
				)
			),
		);
	}

	/**
	 * @dataProvider provideCollectDataTypes
	 */
	public function testCollectDataTypes( array $ids, array $expected ) {
		$builder = $this->newEntityInfoBuilder( $ids );
		$builder->collectDataTypes();
		$entityInfo = $builder->getEntityInfo();

		$this->assertArrayEquals( array_keys( $expected ), array_keys( $entityInfo ) );
	}

	public function provideRemoveMissing() {
		return array(
			array(
				array(),
				array()
			),

			array(
				array(
					new ItemId( 'Q2' ),
				),
				array(
					'Q2' => array( 'id' => 'Q2', 'type' => Item::ENTITY_TYPE ),
				),
			),

			array(
				array(
					new ItemId( 'Q7' ),
				),
				array()
			),

			array(
				array(
					new ItemId( 'Q7' ),
					new PropertyId( 'P7' ),
					new ItemId( 'Q2' ),
				),
				array(
					'Q2' => array( 'id' => 'Q2', 'type' => Item::ENTITY_TYPE ),
				)
			),
		);
	}

	/**
	 * @dataProvider provideRemoveMissing
	 */
	public function testRemoveMissing( array $ids, array $expected ) {
		$builder = $this->newEntityInfoBuilder( $ids );
		$builder->removeMissing();
		$entityInfo = $builder->getEntityInfo();

		$this->assertArrayEquals( array_keys( $expected ), array_keys( $entityInfo ) );
	}

}
