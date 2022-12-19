<?php

namespace Wikibase\Repo\Tests\Store\Sql\Terms;

use MediaWikiIntegrationTestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lib\Store\Sql\Terms\StaticTypeIdsStore;
use Wikibase\Lib\Store\Sql\Terms\TypeIdsLookup;
use Wikibase\Lib\Tests\Rdbms\LocalRepoDbTestHelper;
use Wikibase\Repo\Store\Sql\Terms\DatabaseTermsCollisionDetector;
use Wikibase\Repo\Tests\WikibaseTablesUsed;

/**
 * @covers \Wikibase\Repo\Store\Sql\Terms\DatabaseTermsCollisionDetector
 *
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class DatabaseTermsCollisionDetectorTest extends MediaWikiIntegrationTestCase {

	use WikibaseTablesUsed;
	use LocalRepoDbTestHelper;

	private const TYPE_LABEL = 1;
	private const TYPE_DESCRIPTION = 2;

	/** @var TypeIdsLookup */
	private $typeIdsLookup;

	/**
	 * Following *TermInLangId are ids of records in
	 * wbt_term_in_lang table, and can be used by individual
	 * tests to link to them in wbt_item_terms and wbt_property_terms
	 * tables as desired.
	 *
	 * They will be setup before each test, in setUpTerms()
	 */
	/** @var int */
	private $enFooLabelTermInLangId;
	/** @var int */
	private $enBarLabelTermInLangId;

	/** @var int */
	private $deFooLabelTermInLangId;
	/** @var int */
	private $deBarLabelTermInLangId;

	/** @var int */
	private $enFooDescriptionTermInLangId;
	/** @var int */
	private $enBarDescriptionTermInLangId;

	/** @var int */
	private $deFooDescriptionTermInLangId;
	/** @var int */
	private $deBarDescriptionTermInLangId;

	protected function setUp(): void {
		parent::setUp();
		$this->markAnyTermsStorageUsed();

		$this->typeIdsLookup = new StaticTypeIdsStore( [
			'label' => self::TYPE_LABEL,
			'description' => self::TYPE_DESCRIPTION,
		] );
		$this->setUpTerms();
	}

	/**
	 * Sets up the following terms in terms store:
	 * label => [ en => [ 'foo', 'bar' ], de => [ 'foo', 'bar' ] ]
	 * description => [ en => [ 'foo', 'bar' ], de => [ 'foo', 'bar' ] ]
	 */
	private function setUpTerms() {
		$this->db->truncate( [
				'wbt_text',
				'wbt_type',
				'wbt_text_in_lang',
				'wbt_term_in_lang',
				'wbt_property_terms',
				'wbt_item_terms',
			]
		);
		// text records
		$this->db->insert( 'wbt_text',
			[ 'wbx_text' => 'foo' ] );
		$fooTextId = $this->db->insertId();
		$this->db->insert( 'wbt_text',
			[ 'wbx_text' => 'bar' ] );
		$barTextId = $this->db->insertId();

		// text_in_lang records
		$this->db->insert( 'wbt_text_in_lang',
			[ 'wbxl_language' => 'en', 'wbxl_text_id' => $fooTextId ] );
		$enFooTextInLangId = $this->db->insertId();
		$this->db->insert( 'wbt_text_in_lang',
			[ 'wbxl_language' => 'de', 'wbxl_text_id' => $fooTextId ] );
		$deFooTextInLangId = $this->db->insertId();
		$this->db->insert( 'wbt_text_in_lang',
			[ 'wbxl_language' => 'en', 'wbxl_text_id' => $barTextId ] );
		$enBarTextInLangId = $this->db->insertId();
		$this->db->insert( 'wbt_text_in_lang',
			[ 'wbxl_language' => 'de', 'wbxl_text_id' => $barTextId ] );
		$deBarTextInLangId = $this->db->insertId();

		// label term_in_lang records
		$this->db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => self::TYPE_LABEL, 'wbtl_text_in_lang_id' => $enFooTextInLangId ] );
		$this->enFooLabelTermInLangId = $this->db->insertId();
		$this->db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => self::TYPE_LABEL, 'wbtl_text_in_lang_id' => $enBarTextInLangId ] );
		$this->enBarLabelTermInLangId = $this->db->insertId();
		$this->db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => self::TYPE_LABEL, 'wbtl_text_in_lang_id' => $deFooTextInLangId ] );
		$this->deFooLabelTermInLangId = $this->db->insertId();
		$this->db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => self::TYPE_LABEL, 'wbtl_text_in_lang_id' => $deBarTextInLangId ] );
		$this->deBarLabelTermInLangId = $this->db->insertId();

		// description term_in_lang records
		$this->db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => self::TYPE_DESCRIPTION, 'wbtl_text_in_lang_id' => $enFooTextInLangId ] );
		$this->enFooDescriptionTermInLangId = $this->db->insertId();
		$this->db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => self::TYPE_DESCRIPTION, 'wbtl_text_in_lang_id' => $enBarTextInLangId ] );
		$this->enBarDescriptionTermInLangId = $this->db->insertId();
		$this->db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => self::TYPE_DESCRIPTION, 'wbtl_text_in_lang_id' => $deFooTextInLangId ] );
		$this->deFooDescriptionTermInLangId = $this->db->insertId();
		$this->db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => self::TYPE_DESCRIPTION, 'wbtl_text_in_lang_id' => $deBarTextInLangId ] );
		$this->deBarDescriptionTermInLangId = $this->db->insertId();
	}

	private function makeTestSubject( $entityType ) {
		return new DatabaseTermsCollisionDetector(
			$entityType,
			$this->getRepoDomainDb(),
			$this->typeIdsLookup
		);
	}

	public function testGivenPropertyLabelTest_whenCollisionExists_returnsCollidingProperyId() {
		$this->db->insert( 'wbt_property_terms', [
			[ 'wbpt_term_in_lang_id' => $this->enBarLabelTermInLangId, 'wbpt_property_id' => 1 ],
			[ 'wbpt_term_in_lang_id' => $this->deFooLabelTermInLangId, 'wbpt_property_id' => 1 ],
			[ 'wbpt_term_in_lang_id' => $this->enFooLabelTermInLangId, 'wbpt_property_id' => 2 ],
			[ 'wbpt_term_in_lang_id' => $this->deBarLabelTermInLangId, 'wbpt_property_id' => 2 ],
		] );

		$collisionDetector = $this->makeTestSubject( 'property' );

		$propertyId = $collisionDetector->detectLabelCollision( 'de', 'foo' );
		$this->assertEquals( NumericPropertyId::newFromNumber( 1 ), $propertyId );

		$propertyId = $collisionDetector->detectLabelCollision( 'en', 'foo' );
		$this->assertEquals( NumericPropertyId::newFromNumber( 2 ), $propertyId );
	}

	public function testGivenPropertyLabelTest_whenNoCollisionsExists_returnsNull() {
		$this->db->insert( 'wbt_property_terms', [
			[ 'wbpt_term_in_lang_id' => $this->deFooLabelTermInLangId, 'wbpt_property_id' => 1 ],
			[ 'wbpt_term_in_lang_id' => $this->enBarLabelTermInLangId, 'wbpt_property_id' => 2 ],
		] );

		$collisionDetector = $this->makeTestSubject( 'property' );

		$propertyId = $collisionDetector->detectLabelCollision( 'en', 'foo' );
		$this->assertNull( $propertyId );

		$propertyId = $collisionDetector->detectLabelCollision( 'de', 'bar' );
		$this->assertNull( $propertyId );
	}

	public function testGivenItemLabelTest_whenCollisionExists_returnsCollidingProperyId() {
		$this->db->insert( 'wbt_item_terms', [
			[ 'wbit_term_in_lang_id' => $this->enBarLabelTermInLangId, 'wbit_item_id' => 1 ],
			[ 'wbit_term_in_lang_id' => $this->deFooLabelTermInLangId, 'wbit_item_id' => 1 ],
			[ 'wbit_term_in_lang_id' => $this->enFooLabelTermInLangId, 'wbit_item_id' => 2 ],
			[ 'wbit_term_in_lang_id' => $this->deBarLabelTermInLangId, 'wbit_item_id' => 2 ],
		] );

		$collisionDetector = $this->makeTestSubject( 'item' );

		$itemId = $collisionDetector->detectLabelCollision( 'de', 'foo' );
		$this->assertEquals( ItemId::newFromNumber( 1 ), $itemId );

		$itemId = $collisionDetector->detectLabelCollision( 'en', 'foo' );
		$this->assertEquals( ItemId::newFromNumber( 2 ), $itemId );
	}

	public function testGivenItemLabelTest_whenNoCollisionsExists_returnsNull() {
		$this->db->insert( 'wbt_item_terms', [
			[ 'wbit_term_in_lang_id' => $this->deFooLabelTermInLangId, 'wbit_item_id' => 1 ],
			[ 'wbit_term_in_lang_id' => $this->enBarLabelTermInLangId, 'wbit_item_id' => 2 ],
		] );

		$collisionDetector = $this->makeTestSubject( 'item' );

		$itemId = $collisionDetector->detectLabelCollision( 'en', 'foo' );
		$this->assertNull( $itemId );

		$itemId = $collisionDetector->detectLabelCollision( 'de', 'bar' );
		$this->assertNull( $itemId );
	}

	public function testGivenPropertyLabelDescriptionTest_whenCollisionExists_returnsCollidingProperyId() {
		$this->db->insert( 'wbt_property_terms', [
			// labels
			[ 'wbpt_term_in_lang_id' => $this->enBarLabelTermInLangId, 'wbpt_property_id' => 1 ],
			[ 'wbpt_term_in_lang_id' => $this->deFooLabelTermInLangId, 'wbpt_property_id' => 1 ],
			[ 'wbpt_term_in_lang_id' => $this->enFooLabelTermInLangId, 'wbpt_property_id' => 2 ],
			[ 'wbpt_term_in_lang_id' => $this->deBarLabelTermInLangId, 'wbpt_property_id' => 2 ],
			[ 'wbpt_term_in_lang_id' => $this->enBarLabelTermInLangId, 'wbpt_property_id' => 3 ],
			[ 'wbpt_term_in_lang_id' => $this->deFooLabelTermInLangId, 'wbpt_property_id' => 3 ],
			// descriptions
			[ 'wbpt_term_in_lang_id' => $this->enBarDescriptionTermInLangId, 'wbpt_property_id' => 1 ],
			[ 'wbpt_term_in_lang_id' => $this->deFooDescriptionTermInLangId, 'wbpt_property_id' => 1 ],
			[ 'wbpt_term_in_lang_id' => $this->enBarDescriptionTermInLangId, 'wbpt_property_id' => 2 ],
			[ 'wbpt_term_in_lang_id' => $this->deFooDescriptionTermInLangId, 'wbpt_property_id' => 2 ],
			[ 'wbpt_term_in_lang_id' => $this->enFooDescriptionTermInLangId, 'wbpt_property_id' => 3 ],
			[ 'wbpt_term_in_lang_id' => $this->deBarDescriptionTermInLangId, 'wbpt_property_id' => 3 ],
		] );

		$collisionDetector = $this->makeTestSubject( 'property' );

		$propertyId = $collisionDetector->detectLabelAndDescriptionCollision( 'en', 'bar', 'bar' );
		$this->assertEquals( NumericPropertyId::newFromNumber( 1 ), $propertyId );

		$propertyId = $collisionDetector->detectLabelAndDescriptionCollision( 'de', 'bar', 'foo' );
		$this->assertEquals( NumericPropertyId::newFromNumber( 2 ), $propertyId );

		$propertyId = $collisionDetector->detectLabelAndDescriptionCollision( 'en', 'bar', 'foo' );
		$this->assertEquals( NumericPropertyId::newFromNumber( 3 ), $propertyId );
	}

	public function testGivenPropertyLabelDescriptionTest_whenNoCollisionsExists_returnsNull() {
		$this->db->insert( 'wbt_property_terms', [
			// labels
			[ 'wbpt_term_in_lang_id' => $this->enBarLabelTermInLangId, 'wbpt_property_id' => 1 ],
			[ 'wbpt_term_in_lang_id' => $this->deFooLabelTermInLangId, 'wbpt_property_id' => 1 ],
			[ 'wbpt_term_in_lang_id' => $this->enFooLabelTermInLangId, 'wbpt_property_id' => 2 ],
			[ 'wbpt_term_in_lang_id' => $this->deBarLabelTermInLangId, 'wbpt_property_id' => 2 ],
			[ 'wbpt_term_in_lang_id' => $this->enBarLabelTermInLangId, 'wbpt_property_id' => 3 ],
			[ 'wbpt_term_in_lang_id' => $this->deFooLabelTermInLangId, 'wbpt_property_id' => 3 ],
			// descriptions
			[ 'wbpt_term_in_lang_id' => $this->enBarDescriptionTermInLangId, 'wbpt_property_id' => 1 ],
			[ 'wbpt_term_in_lang_id' => $this->deFooDescriptionTermInLangId, 'wbpt_property_id' => 1 ],
			[ 'wbpt_term_in_lang_id' => $this->enBarDescriptionTermInLangId, 'wbpt_property_id' => 2 ],
			[ 'wbpt_term_in_lang_id' => $this->deFooDescriptionTermInLangId, 'wbpt_property_id' => 2 ],
			[ 'wbpt_term_in_lang_id' => $this->enFooDescriptionTermInLangId, 'wbpt_property_id' => 3 ],
			[ 'wbpt_term_in_lang_id' => $this->deBarDescriptionTermInLangId, 'wbpt_property_id' => 3 ],
		] );

		$collisionDetector = $this->makeTestSubject( 'property' );

		$propertyId = $collisionDetector->detectLabelAndDescriptionCollision( 'en', 'foo', 'foo' );
		$this->assertNull( $propertyId );

		$propertyId = $collisionDetector->detectLabelAndDescriptionCollision( 'de', 'bar', 'bar' );
		$this->assertNull( $propertyId );
	}

	public function testGivenItemLabelDescriptionTest_whenCollisionExists_returnsCollidingProperyId() {
		$this->db->insert( 'wbt_item_terms', [
			// labels
			[ 'wbit_term_in_lang_id' => $this->enBarLabelTermInLangId, 'wbit_item_id' => 1 ],
			[ 'wbit_term_in_lang_id' => $this->deFooLabelTermInLangId, 'wbit_item_id' => 1 ],
			[ 'wbit_term_in_lang_id' => $this->enFooLabelTermInLangId, 'wbit_item_id' => 2 ],
			[ 'wbit_term_in_lang_id' => $this->deBarLabelTermInLangId, 'wbit_item_id' => 2 ],
			[ 'wbit_term_in_lang_id' => $this->enBarLabelTermInLangId, 'wbit_item_id' => 3 ],
			[ 'wbit_term_in_lang_id' => $this->deFooLabelTermInLangId, 'wbit_item_id' => 3 ],
			// descriptions
			[ 'wbit_term_in_lang_id' => $this->enBarDescriptionTermInLangId, 'wbit_item_id' => 1 ],
			[ 'wbit_term_in_lang_id' => $this->deFooDescriptionTermInLangId, 'wbit_item_id' => 1 ],
			[ 'wbit_term_in_lang_id' => $this->enBarDescriptionTermInLangId, 'wbit_item_id' => 2 ],
			[ 'wbit_term_in_lang_id' => $this->deFooDescriptionTermInLangId, 'wbit_item_id' => 2 ],
			[ 'wbit_term_in_lang_id' => $this->enFooDescriptionTermInLangId, 'wbit_item_id' => 3 ],
			[ 'wbit_term_in_lang_id' => $this->deBarDescriptionTermInLangId, 'wbit_item_id' => 3 ],
		] );

		$collisionDetector = $this->makeTestSubject( 'item' );

		$itemId = $collisionDetector->detectLabelAndDescriptionCollision( 'en', 'bar', 'bar' );
		$this->assertEquals( ItemId::newFromNumber( 1 ), $itemId );

		$itemId = $collisionDetector->detectLabelAndDescriptionCollision( 'de', 'bar', 'foo' );
		$this->assertEquals( ItemId::newFromNumber( 2 ), $itemId );

		$itemId = $collisionDetector->detectLabelAndDescriptionCollision( 'en', 'bar', 'foo' );
		$this->assertEquals( ItemId::newFromNumber( 3 ), $itemId );
	}

	public function testGivenItemLabelDescriptionTest_whenNoCollisionsExists_returnsNull() {
		$this->db->insert( 'wbt_item_terms', [
			// labels
			[ 'wbit_term_in_lang_id' => $this->enBarLabelTermInLangId, 'wbit_item_id' => 1 ],
			[ 'wbit_term_in_lang_id' => $this->deFooLabelTermInLangId, 'wbit_item_id' => 1 ],
			[ 'wbit_term_in_lang_id' => $this->enFooLabelTermInLangId, 'wbit_item_id' => 2 ],
			[ 'wbit_term_in_lang_id' => $this->deBarLabelTermInLangId, 'wbit_item_id' => 2 ],
			[ 'wbit_term_in_lang_id' => $this->enBarLabelTermInLangId, 'wbit_item_id' => 3 ],
			[ 'wbit_term_in_lang_id' => $this->deFooLabelTermInLangId, 'wbit_item_id' => 3 ],
			// descriptions
			[ 'wbit_term_in_lang_id' => $this->enBarDescriptionTermInLangId, 'wbit_item_id' => 1 ],
			[ 'wbit_term_in_lang_id' => $this->deFooDescriptionTermInLangId, 'wbit_item_id' => 1 ],
			[ 'wbit_term_in_lang_id' => $this->enBarDescriptionTermInLangId, 'wbit_item_id' => 2 ],
			[ 'wbit_term_in_lang_id' => $this->deFooDescriptionTermInLangId, 'wbit_item_id' => 2 ],
			[ 'wbit_term_in_lang_id' => $this->enFooDescriptionTermInLangId, 'wbit_item_id' => 3 ],
			[ 'wbit_term_in_lang_id' => $this->deBarDescriptionTermInLangId, 'wbit_item_id' => 3 ],
		] );

		$collisionDetector = $this->makeTestSubject( 'item' );

		$itemId = $collisionDetector->detectLabelAndDescriptionCollision( 'en', 'foo', 'foo' );
		$this->assertNull( $itemId );

		$itemId = $collisionDetector->detectLabelAndDescriptionCollision( 'de', 'bar', 'bar' );
		$this->assertNull( $itemId );
	}

	/**
	 * @dataProvider termListProvider
	 */
	public function testDetectLabelsCollision( array $databaseRecords, TermList $termsList, array $expectedResults ) {
		$records = [];
		foreach ( $databaseRecords as $key => $propertyRecords ) {
			foreach ( $propertyRecords as $record ) {
				$records[] = [ 'wbpt_term_in_lang_id' => $this->{$record}, 'wbpt_property_id' => $key ];
			}
		}

		$this->db->insert( 'wbt_property_terms', $records );

		$collisionDetector = $this->makeTestSubject( 'property' );

		$properties = $collisionDetector->detectLabelsCollision( $termsList );

		$this->assertEquals( $expectedResults, $properties );
	}

	public function termListProvider() {

		$deFoo = new Term( 'de', 'foo' );
		$enFoo = new Term( 'en', 'foo' );

		$deBar = new Term( 'de', 'bar' );
		$enBar = new Term( 'en', 'bar' );

		yield [
			[
				1 => [ 'enFooLabelTermInLangId' ],
				2 => [ 'deFooLabelTermInLangId' ],
			],
			new TermList( [ $deBar ] ),
			[],
		];

		yield [
			[
				1 => [ 'enFooLabelTermInLangId', 'deFooLabelTermInLangId' ],
				2 => [ 'enBarLabelTermInLangId', 'deBarLabelTermInLangId' ],
			],
			new TermList( [ $deFoo ] ),
			[
				'P1' => [ $deFoo ],
			],
		];

		yield [
			[
				1 => [ 'enFooLabelTermInLangId', 'deFooLabelTermInLangId' ],
				2 => [ 'enBarLabelTermInLangId', 'deBarLabelTermInLangId' ],
			],
			new TermList( [ $deFoo, $enFoo ] ),
			[
				'P1' => [ $deFoo, $enFoo ],
			],
		];

		yield [
			[
				1 => [ 'enFooLabelTermInLangId', 'deBarLabelTermInLangId' ],
				2 => [ 'enBarLabelTermInLangId' ],
			],
			new TermList( [ $deBar, $enBar ] ),
			[
				'P1' => [ $deBar ],
				'P2' => [ $enBar ],
			],
		];
	}

}
