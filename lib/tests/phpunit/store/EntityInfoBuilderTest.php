<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Store\Sql\SqlEntityInfoBuilder;

/**
 * Base class for tests of EntityInfoBuilder implementation.
 * This abstract test case tests conformance to the contract of the EntityInfoBuilder interface.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
abstract class EntityInfoBuilderTest extends \MediaWikiTestCase {

	/**
	 * @return EntityId[]
	 */
	protected function getKnownRedirects() {
		return array(
			'Q7' => new ItemId( 'Q2' ),
			'Q12' => new ItemId( 'Q2' ),
			'Q22' => new ItemId( 'Q2' ),
		);
	}

	/**
	 * @return EntityDocument[]
	 */
	protected function getKnownEntities() {
		$q1 = new Item( new ItemId( 'Q1' ) );
		$q1->setLabel( 'en', 'label:Q1/en' );
		$q1->setLabel( 'de', 'label:Q1/de' );
		$q1->setDescription( 'en', 'description:Q1/en' );
		$q1->setDescription( 'de', 'description:Q1/de' );
		$q1->setAliases( 'en', array( 'alias:Q1/en#1' ) );
		$q1->setAliases( 'de', array( 'alias:Q1/de#1', 'alias:Q1/de#2' ) );

		$q2 = new Item( new ItemId( 'Q2' ) );
		$q2->setLabel( 'en', 'label:Q2/en' );
		$q2->setLabel( 'de', 'label:Q2/de' );
		$q2->setAliases( 'en', array( 'alias:Q2/en#1' ) );
		$q2->setAliases( 'de', array( 'alias:Q2/de#1', 'alias:Q2/de#2' ) );

		$p2 = Property::newFromType( 'string' );
		$p2->setId( new PropertyId( 'P2' ) );
		$p2->setLabel( 'en', 'label:P2/en' );
		$p2->setLabel( 'de', 'label:P2/de' );
		$p2->setDescription( 'en', 'description:P2/en' );
		$p2->setDescription( 'de', 'description:P2/de' );
		$p2->setAliases( 'en', array( 'alias:P2/en#1' ) );
		$p2->setAliases( 'de', array( 'alias:P2/de#1', 'alias:P2/de#2' ) );

		$p3 = Property::newFromType( 'string' );
		$p3->setId( new PropertyId( 'P3' ) );
		$p3->setLabel( 'en', 'label:P3/en' );
		$p3->setLabel( 'de', 'label:P3/de' );
		$p3->setDescription( 'en', 'description:P3/en' );
		$p3->setDescription( 'de', 'description:P3/de' );

		return array( $q1, $q2, $p2, $p3 );
	}

	/**
	 * @param EntityId[] $ids
	 *
	 * @return SqlEntityInfoBuilder
	 */
	abstract protected function newEntityInfoBuilder( array $ids );

	public function getEntityInfoProvider() {
		return array(
			array(
				[],
				[]
			),

			array(
				array(
					new ItemId( 'Q1' ),
					new PropertyId( 'P3' )
				),
				array(
					'Q1' => array( 'id' => 'Q1', 'type' => Item::ENTITY_TYPE ),
					'P3' => array( 'id' => 'P3', 'type' => Property::ENTITY_TYPE ),
				)
			),

			array(
				array(
					new ItemId( 'Q1' ),
					new ItemId( 'Q1' ),
				),
				array(
					'Q1' => array( 'id' => 'Q1', 'type' => Item::ENTITY_TYPE ),
				)
			),
		);
	}

	/**
	 * @dataProvider getEntityInfoProvider
	 */
	public function testGetEntityInfo( array $ids, array $expected ) {
		$builder = $this->newEntityInfoBuilder( $ids );
		$actual = $builder->getEntityInfo()->asArray();

		$this->assertArrayEquals( $expected, $actual, false, true );
	}

	public function resolveRedirectsProvider() {
		return array(
			'empty' => array(
				[],
				[]
			),

			'some redirects' => array(
				array(
					new ItemId( 'Q2' ),
					new ItemId( 'Q12' ),
					new ItemId( 'Q22' ),
				),
				array(
					'Q2' => 'Q2',
					'Q12' => 'Q2',
					'Q22' => 'Q2',
				),
			),
		);
	}

	/**
	 * @dataProvider resolveRedirectsProvider
	 */
	public function testResolveRedirects( array $ids, array $expected ) {
		$builder = $this->newEntityInfoBuilder( $ids );

		$builder->resolveRedirects();
		$entityInfo = $builder->getEntityInfo()->asArray();

		$resolvedIds = array_map(
			function( $record ) {
				return $record['id'];
			},
			$entityInfo
		);

		$this->assertArrayEquals( $expected, $resolvedIds );
	}

	/**
	 * Converts a map of the form $language => $value into a ist of records
	 * of the form $language => array( 'language' => $language, 'value' => $value ).
	 *
	 * @param array $map map if the form $language => $value
	 * @param string|null $language For the language for all entries. Useful if $map is a list, not an associative array.
	 *
	 * @return array map if the form $language => array( 'language' => $language, 'value' => $value )
	 */
	protected function makeLanguageValueRecords( array $map, $language = null ) {
		$records = [];

		foreach ( $map as $key => $value ) {
			if ( $language !== null ) {
				$lang = $language;
			} else {
				$lang = $key;
			}

			if ( is_array( $value ) ) {
				$records[$key] = $this->makeLanguageValueRecords( $value, $lang );
			} else {
				$records[$key] = array(
					'language' => $lang,
					'value' => $value
				);
			}
		}

		return $records;
	}

	public function collectTermsProvider() {
		return array(
			array(
				[],
				null,
				null,
				[]
			),

			array(
				array(
					new ItemId( 'Q1' ),
					new PropertyId( 'P3' ),
					new ItemId( 'Q7' ),
				),
				null,
				null,
				array(
					'Q1' => array( 'id' => 'Q1', 'type' => Item::ENTITY_TYPE,
						'labels' => $this->makeLanguageValueRecords( array(
							'en' => 'label:Q1/en', 'de' => 'label:Q1/de' ) ),
						'descriptions' => $this->makeLanguageValueRecords( array(
							'en' => 'description:Q1/en', 'de' => 'description:Q1/de' ) ),
						'aliases' => $this->makeLanguageValueRecords( array(
							'en' => array( 'alias:Q1/en#1' ),
							'de' => array( 'alias:Q1/de#1', 'alias:Q1/de#2' ) ) ),
					),
					'P3' => array( 'id' => 'P3', 'type' => Property::ENTITY_TYPE,
						'labels' => $this->makeLanguageValueRecords( array(
							'en' => 'label:P3/en', 'de' => 'label:P3/de' ) ),
						'descriptions' => $this->makeLanguageValueRecords( array(
							'en' => 'description:P3/en', 'de' => 'description:P3/de' ) ),
						'aliases' => [],
					),
					'Q7' => array( 'id' => 'Q7', 'type' => Item::ENTITY_TYPE,
						'labels' => [],
						'descriptions' => [],
						'aliases' => []
					),
				)
			),

			array(
				array(
					new ItemId( 'Q1' ),
					new PropertyId( 'P3' ),
					new ItemId( 'Q7' ),
				),
				array( 'label' ),
				array( 'de' ),
				array(
					'Q1' => array( 'id' => 'Q1', 'type' => Item::ENTITY_TYPE,
						'labels' => $this->makeLanguageValueRecords( array( 'de' => 'label:Q1/de' ) ),
					),
					'P3' => array( 'id' => 'P3', 'type' => Property::ENTITY_TYPE,
						'labels' => $this->makeLanguageValueRecords( array( 'de' => 'label:P3/de' ) ),
					),
					'Q7' => array( 'id' => 'Q7', 'type' => Item::ENTITY_TYPE, 'labels' => [] ),
				)
			),
		);
	}

	/**
	 * @dataProvider collectTermsProvider
	 */
	public function testCollectTerms(
		array $ids,
		array $types = null,
		array $languages = null,
		array $expected
	) {
		$builder = $this->newEntityInfoBuilder( $ids );

		$builder->collectTerms( $types, $languages );
		$entityInfo = $builder->getEntityInfo()->asArray();

		$this->assertSameSize( $expected, $entityInfo );

		foreach ( $expected as $id => $expectedRecord ) {
			$this->assertArrayHasKey( $id, $entityInfo );
			$actualRecord = $entityInfo[$id];

			$this->assertArrayEquals( $expectedRecord, $actualRecord, false, true );
		}
	}

	public function testCollectTerms_redirect() {
		$ids = array( new ItemId( 'Q7' ), new ItemId( 'Q1' ) );

		$expected = array(
			'Q1' => array( 'id' => 'Q1', 'type' => Item::ENTITY_TYPE,
				'labels' => $this->makeLanguageValueRecords( array( 'de' => 'label:Q1/de' ) ),
			),
			'Q2' => array( 'id' => 'Q2', 'type' => Item::ENTITY_TYPE,
				'labels' => $this->makeLanguageValueRecords( array( 'de' => 'label:Q2/de' ) ),
			),
			'Q7' => array( 'id' => 'Q2', 'type' => Item::ENTITY_TYPE,
				'labels' => $this->makeLanguageValueRecords( array( 'de' => 'label:Q2/de' ) ),
		)
		);

		$builder = $this->newEntityInfoBuilder( $ids );

		$builder->resolveRedirects();
		$builder->collectTerms( array( 'label' ), array( 'de' ) );
		$entityInfo = $builder->getEntityInfo()->asArray();

		$this->assertEquals( array_keys( $expected ), array_keys( $entityInfo ) );

		foreach ( $expected as $id => $expectedRecord ) {
			$this->assertArrayHasKey( $id, $entityInfo );
			$actualRecord = $entityInfo[$id];

			$this->assertArrayEquals( $expectedRecord, $actualRecord, false, true );
		}
	}

	public function collectDataTypesProvider() {
		return array(
			array(
				[],
				[]
			),

			array(
				array(
					new PropertyId( 'P2' ),
					new PropertyId( 'P3' ),
					new ItemId( 'Q7' ),
					new PropertyId( 'P7' ),
				),
				array(
					'P2' => array( 'id' => 'P2', 'type' => Property::ENTITY_TYPE, 'datatype' => 'string' ),
					'P3' => array( 'id' => 'P3', 'type' => Property::ENTITY_TYPE, 'datatype' => 'string' ),
					'Q7' => array( 'id' => 'Q7', 'type' => Item::ENTITY_TYPE ),
					'P7' => array( 'id' => 'P7', 'type' => Property::ENTITY_TYPE, 'datatype' => null ),
				)
			),
		);
	}

	/**
	 * @dataProvider collectDataTypesProvider
	 */
	public function testCollectDataTypes( array $ids, array $expected ) {
		$builder = $this->newEntityInfoBuilder( $ids );

		$builder->collectDataTypes();
		$entityInfo = $builder->getEntityInfo()->asArray();

		$this->assertSameSize( $expected, $entityInfo );

		foreach ( $expected as $id => $expectedRecord ) {
			$this->assertArrayHasKey( $id, $entityInfo );
			$actualRecord = $entityInfo[$id];

			$this->assertArrayEquals( $expectedRecord, $actualRecord, false, true );
		}
	}

	public function removeMissingAndRedirectsProvider() {
		return array(
			'empty' => array(
				[],
				[]
			),

			'found' => array(
				array(
					new ItemId( 'Q2' ),
				),
				array(
					'Q2' => array( 'id' => 'Q2', 'type' => Item::ENTITY_TYPE ),
				),
			),

			'missing' => array(
				array(
					new ItemId( 'Q77' ),
				),
				[]
			),

			'some found' => array(
				array(
					new ItemId( 'Q2' ),
					new PropertyId( 'P7' ),
					new ItemId( 'Q7' ),
					new PropertyId( 'P2' ),
				),
				array(
					'P2' => array( 'id' => 'P2', 'type' => Property::ENTITY_TYPE ),
					'Q2' => array( 'id' => 'Q2', 'type' => Item::ENTITY_TYPE ),
				)
			),
		);
	}

	/**
	 * @dataProvider removeMissingAndRedirectsProvider
	 */
	public function testRemoveMissingAndRedirects( array $ids, array $expected ) {
		$builder = $this->newEntityInfoBuilder( $ids );

		$builder->removeMissing( 'remove-redirects' );
		$entityInfo = $builder->getEntityInfo()->asArray();

		$this->assertArrayEquals( array_keys( $expected ), array_keys( $entityInfo ) );
	}

	public function removeMissingButKeepRedirects() {
		return array(
			'empty' => array(
				[],
				[]
			),

			'unrelated redirect' => array(
				array(
					new ItemId( 'Q2' ),
				),
				array(
					'Q2' => array( 'id' => 'Q2', 'type' => Item::ENTITY_TYPE ),
				),
			),

			'redirect resolved' => array(
				array(
					new ItemId( 'Q7' ),
				),
				array(
					'Q7' => array( 'id' => 'Q2', 'type' => Item::ENTITY_TYPE ),
				),
			),

			'some found, some resolved' => array(
				array(
					new ItemId( 'Q2' ),
					new PropertyId( 'P7' ),
					new ItemId( 'Q7' ),
					new PropertyId( 'P2' ),
				),
				array(
					'P2' => array( 'id' => 'P2', 'type' => Property::ENTITY_TYPE ),
					'Q2' => array( 'id' => 'Q2', 'type' => Item::ENTITY_TYPE ),
					'Q7' => array( 'id' => 'Q2', 'type' => Item::ENTITY_TYPE ),
				)
			),
		);
	}

	/**
	 * @dataProvider removeMissingButKeepRedirects
	 */
	public function testRemoveMissingButKeepRedirects( array $ids, array $expected ) {
		$builder = $this->newEntityInfoBuilder( $ids );

		$builder->removeMissing();
		$entityInfo = $builder->getEntityInfo()->asArray();

		$this->assertArrayEquals( array_keys( $expected ), array_keys( $entityInfo ) );
	}

	public function removeEntityInfoProvider() {
		return array(
			'empty' => array(
				[],
				[],
				[],
			),
			'remove nonexisting' => array(
				array(
					new ItemId( 'Q1' ),
				),
				array(
					new ItemId( 'Q2' ),
				),
				array( 'Q1' ),
			),
			'remove some' => array(
				array(
					new ItemId( 'Q1' ),
					new ItemId( 'Q2' ),
					new ItemId( 'Q3' ),
				),
				array(
					new ItemId( 'Q2' ),
				),
				array( 'Q1', 'Q3' ),
			),
		);
	}

	/**
	 * @dataProvider removeEntityInfoProvider
	 */
	public function testRemoveEntityInfo( array $ids, array $remove, array $expected ) {
		$builder = $this->newEntityInfoBuilder( $ids );

		$builder->removeEntityInfo( $remove );
		$entityInfo = $builder->getEntityInfo()->asArray();

		$this->assertArrayEquals( $expected, array_keys( $entityInfo ) );
	}

	public function retainEntityInfoProvider() {
		return array(
			'empty' => array(
				[],
				[],
				[],
			),
			'retain nonexisting' => array(
				array(
					new ItemId( 'Q1' ),
				),
				array(
					new ItemId( 'Q2' ),
				),
				[],
			),
			'retain some' => array(
				array(
					new ItemId( 'Q1' ),
					new ItemId( 'Q2' ),
					new ItemId( 'Q3' ),
				),
				array(
					new ItemId( 'Q2' ),
				),
				array( 'Q2' ),
			),
		);
	}

	/**
	 * @dataProvider retainEntityInfoProvider
	 */
	public function testRetainEntityInfo( array $ids, array $retain, array $expected ) {
		$builder = $this->newEntityInfoBuilder( $ids );

		$builder->retainEntityInfo( $retain );
		$entityInfo = $builder->getEntityInfo()->asArray();

		$this->assertArrayEquals( $expected, array_keys( $entityInfo ) );
	}

}
