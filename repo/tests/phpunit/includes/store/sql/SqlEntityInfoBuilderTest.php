<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Property;
use Wikibase\Item;
use Wikibase\SqlEntityInfoBuilder;

/**
 * @covers Wikibase\SqlEntityInfoBuilder
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseStore
 * @group WikibasePropertyInfo
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class SqlEntityInfoBuilderTest extends \MediaWikiTestCase {


	public function __construct( $name = null, $data = array(), $dataName = '' ) {
		parent::__construct( $name, $data, $dataName );
	}

	public function setUp() {
		parent::setUp();

		$this->tablesUsed[] = 'wb_property_info';
		$this->tablesUsed[] = 'wb_terms';
		$this->tablesUsed[] = 'wb_entity_per_page';

		$this->insertRows(
			'wb_terms',
			array( 'term_entity_type', 'term_entity_id', 'term_type', 'term_language', 'term_text', 'term_search_key' ),
			array(
				array( Item::ENTITY_TYPE, 1, 'label', 'en', 'label:Q1/en', '-/-' ),
				array( Item::ENTITY_TYPE, 1, 'label', 'de', 'label:Q1/de', '-/-' ),
				array( Item::ENTITY_TYPE, 1, 'description', 'en', 'description:Q1/en', '-/-' ),
				array( Item::ENTITY_TYPE, 1, 'description', 'de', 'description:Q1/de', '-/-' ),
				array( Item::ENTITY_TYPE, 1, 'alias', 'en', 'alias:Q1/en#1', '-/-' ),
				array( Item::ENTITY_TYPE, 1, 'alias', 'de', 'alias:Q1/de#1', '-/-' ),
				array( Item::ENTITY_TYPE, 1, 'alias', 'de', 'alias:Q1/de#2', '-/-' ),

				array( Item::ENTITY_TYPE, 2, 'label', 'en', 'label:Q2/en', '-/-' ),
				array( Item::ENTITY_TYPE, 2, 'label', 'de', 'label:Q2/de', '-/-' ),
				array( Item::ENTITY_TYPE, 2, 'alias', 'en', 'alias:Q2/en#1', '-/-' ),
				array( Item::ENTITY_TYPE, 2, 'alias', 'de', 'alias:Q2/de#1', '-/-' ),
				array( Item::ENTITY_TYPE, 2, 'alias', 'de', 'alias:Q2/de#2', '-/-' ),

				array( Property::ENTITY_TYPE, 2, 'label', 'en', 'label:P2/en', '-/-' ),
				array( Property::ENTITY_TYPE, 2, 'label', 'de', 'label:P2/de', '-/-' ),
				array( Property::ENTITY_TYPE, 2, 'description', 'en', 'description:P2/en', '-/-' ),
				array( Property::ENTITY_TYPE, 2, 'description', 'de', 'description:P2/de', '-/-' ),
				array( Property::ENTITY_TYPE, 2, 'alias', 'en', 'alias:P2/en#1', '-/-' ),
				array( Property::ENTITY_TYPE, 2, 'alias', 'de', 'alias:P2/de#1', '-/-' ),
				array( Property::ENTITY_TYPE, 2, 'alias', 'de', 'alias:P2/de#2', '-/-' ),

				array( Property::ENTITY_TYPE, 3, 'label', 'en', 'label:P3/en', '-/-' ),
				array( Property::ENTITY_TYPE, 3, 'label', 'de', 'label:P3/de', '-/-' ),
				array( Property::ENTITY_TYPE, 3, 'description', 'en', 'description:P3/en', '-/-' ),
				array( Property::ENTITY_TYPE, 3, 'description', 'de', 'description:P3/de', '-/-' ),
			) );

		$this->insertRows(
			'wb_property_info',
			array( 'pi_property_id', 'pi_type', 'pi_info' ),
			array(
				array( 2, 'type2', '{"type":"type2"}' ),
				array( 3, 'type3', '{"type":"type3"}' ),
			) );

		$this->insertRows(
			'wb_entity_per_page',
			array( 'epp_entity_type', 'epp_entity_id', 'epp_page_id' ),
			array(
				array( Item::ENTITY_TYPE, 1, 1001 ),
				array( Item::ENTITY_TYPE, 2, 1002 ),
				array( Property::ENTITY_TYPE, 2, 2002 ),
				array( Property::ENTITY_TYPE, 3, 2003 ),
			) );
	}

	protected function insertRows( $table, $fields, $rows ) {
		$dbw = wfGetDB( DB_MASTER );

		foreach ( $rows as $row ) {
			$dbw->insert(
				$table,
				array_combine( $fields, $row ),
				__METHOD__,
				// Just ignore insertation errors... if similar data already is in the DB
				// it's probably good enough for the tests (as this is only testing for UNIQUE
				// fields anyway).
				array( 'IGNORE' )
			);
		}
	}

	public function newEntityInfoBuilder() {
		return new SqlEntityInfoBuilder( new BasicEntityIdParser() );
	}

	public function provideBuildEntityInfo() {
		return array(
			array(
				array(),
				array()
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
	 * @dataProvider provideBuildEntityInfo
	 */
	public function testBuildEntityInfo( array $ids, array $expected ) {
		$builder = $this->newEntityInfoBuilder();

		$actual = $builder->buildEntityInfo( $ids );

		$this->assertArrayEquals( $expected, $actual, false, true );
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
		$records = array();

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

	public function provideAddTerms() {
		return array(
			array(
				array(),
				null,
				null,
				array()
			),

			array(
				array(
					'Q1' => array( 'id' => 'Q1', 'type' => Item::ENTITY_TYPE ),
					'P3' => array( 'id' => 'P3', 'type' => Property::ENTITY_TYPE ),
					'Q7' => array( 'id' => 'Q7', 'type' => Item::ENTITY_TYPE ),
				),
				null,
				null,
				array(
					'Q1' => array( 'id' => 'Q1', 'type' => Item::ENTITY_TYPE,
						'labels' => $this->makeLanguageValueRecords( array( 'en' => 'label:Q1/en', 'de' => 'label:Q1/de' ) ),
						'descriptions' =>  $this->makeLanguageValueRecords( array( 'en' => 'description:Q1/en', 'de' => 'description:Q1/de' ) ),
						'aliases' =>  $this->makeLanguageValueRecords( array( 'en' => array( 'alias:Q1/en#1' ), 'de' => array( 'alias:Q1/de#1', 'alias:Q1/de#2' ) ) ),
					),
					'P3' => array( 'id' => 'P3', 'type' => Property::ENTITY_TYPE,
						'labels' => $this->makeLanguageValueRecords( array( 'en' => 'label:P3/en', 'de' => 'label:P3/de' ) ),
						'descriptions' =>  $this->makeLanguageValueRecords( array( 'en' => 'description:P3/en', 'de' => 'description:P3/de' ) ),
						'aliases' =>  array(),
					),
					'Q7' => array( 'id' => 'Q7', 'type' => Item::ENTITY_TYPE, 'labels' => array(), 'descriptions' => array(), 'aliases' => array() ),
				)
			),

			array(
				array(
					'Q1' => array( 'id' => 'Q1', 'type' => Item::ENTITY_TYPE ),
					'P3' => array( 'id' => 'P3', 'type' => Property::ENTITY_TYPE ),
					'Q7' => array( 'id' => 'Q7', 'type' => Item::ENTITY_TYPE ),
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
					'Q7' => array( 'id' => 'Q7', 'type' => Item::ENTITY_TYPE, 'labels' => array() ),
				)
			),
		);
	}

	/**
	 * @dataProvider provideAddTerms
	 */
	public function testAddTerms( array $entityInfo, array $types = null, array $languages = null, array $expected = null ) {
		$builder = $this->newEntityInfoBuilder();

		$builder->addTerms( $entityInfo, $types, $languages );

		foreach ( $expected as $id => $expectedRecord ) {
			$this->assertArrayHasKey( $id, $entityInfo );
			$actualRecord = $entityInfo[$id];

			$this->assertArrayEquals( $expectedRecord, $actualRecord, false, true );
		}
	}

	public function provideAddDataTypes() {
		return array(
			array(
				array(),
				array()
			),

			array(
				array(
					'P2' => array( 'id' => 'P2', 'type' => Property::ENTITY_TYPE ),
					'P3' => array( 'id' => 'P3', 'type' => Property::ENTITY_TYPE ),
					'Q7' => array( 'id' => 'Q7', 'type' => Item::ENTITY_TYPE ),
					'P7' => array( 'id' => 'P7', 'type' => Property::ENTITY_TYPE ),
				),
				array(
					'P2' => array( 'id' => 'P2', 'type' => Property::ENTITY_TYPE, 'datatype' => 'type2' ),
					'P3' => array( 'id' => 'P3', 'type' => Property::ENTITY_TYPE, 'datatype' => 'type3' ),
					'Q7' => array( 'id' => 'Q7', 'type' => Item::ENTITY_TYPE ),
					'P7' => array( 'id' => 'P7', 'type' => Property::ENTITY_TYPE, 'datatype' => null ),
				)
			),
		);
	}

	/**
	 * @dataProvider provideAddDataTypes
	 */
	public function testAddDataTypes( array $entityInfo, array $expected = null ) {
		$builder = $this->newEntityInfoBuilder();

		$builder->addDataTypes( $entityInfo );

		foreach ( $expected as $id => $expectedRecord ) {
			$this->assertArrayHasKey( $id, $entityInfo );
			$actualRecord = $entityInfo[$id];

			$this->assertArrayEquals( $expectedRecord, $actualRecord, false, true );
		}
	}

	public function provideRemoveMissing() {
		return array(
			array(
				array(),
				array()
			),

			array(
				array(
					'Q2' => array( 'id' => 'Q2', 'type' => Item::ENTITY_TYPE ),
				),
				array(
					'Q2' => array( 'id' => 'Q2', 'type' => Item::ENTITY_TYPE ),
				),
			),

			array(
				array(
					'Q7' => array( 'id' => 'Q7', 'type' => Item::ENTITY_TYPE ),
				),
				array()
			),

			array(
				array(
					'P2' => array( 'id' => 'P2', 'type' => Property::ENTITY_TYPE ),
					'Q7' => array( 'id' => 'Q7', 'type' => Item::ENTITY_TYPE ),
					'P7' => array( 'id' => 'P7', 'type' => Property::ENTITY_TYPE ),
					'Q2' => array( 'id' => 'Q2', 'type' => Item::ENTITY_TYPE ),
				),
				array(
					'P2' => array( 'id' => 'P2', 'type' => Property::ENTITY_TYPE ),
					'Q2' => array( 'id' => 'Q2', 'type' => Item::ENTITY_TYPE ),
				)
			),
		);
	}

	/**
	 * @dataProvider provideRemoveMissing
	 */
	public function testRemoveMissing( array $entityInfo, array $expected = null ) {
		$builder = $this->newEntityInfoBuilder();

		$builder->removeMissing( $entityInfo );

		$this->assertArrayEquals( array_keys( $expected ), array_keys( $entityInfo ) );
	}
}
