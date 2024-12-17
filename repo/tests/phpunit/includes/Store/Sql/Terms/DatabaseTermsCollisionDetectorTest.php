<?php

declare( strict_types = 1 );

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

/**
 * @covers \Wikibase\Repo\Store\Sql\Terms\DatabaseTermsCollisionDetector
 *
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class DatabaseTermsCollisionDetectorTest extends MediaWikiIntegrationTestCase {

	use LocalRepoDbTestHelper;

	private const TYPE_LABEL = 1;
	private const TYPE_DESCRIPTION = 2;

	private TypeIdsLookup $typeIdsLookup;

	/**
	 * Following *TermInLangId are ids of records in
	 * wbt_term_in_lang table, and can be used by individual
	 * tests to link to them in wbt_item_terms and wbt_property_terms
	 * tables as desired.
	 *
	 * They will be setup before each test, in setUpTerms()
	 */
	private int $enFooLabelTermInLangId;
	private int $enBarLabelTermInLangId;

	private int $deFooLabelTermInLangId;
	private int $deBarLabelTermInLangId;

	private int $enFooDescriptionTermInLangId;
	private int $enBarDescriptionTermInLangId;

	private int $deFooDescriptionTermInLangId;
	private int $deBarDescriptionTermInLangId;

	protected function setUp(): void {
		parent::setUp();

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
	private function setUpTerms(): void {
		// text records
		$this->getDb()->newInsertQueryBuilder()
			->insertInto( 'wbt_text' )
			->row( [ 'wbx_text' => 'foo' ] )
			->caller( __METHOD__ )
			->execute();
		$fooTextId = $this->getDb()->insertId();
		$this->getDb()->newInsertQueryBuilder()
			->insertInto( 'wbt_text' )
			->row( [ 'wbx_text' => 'bar' ] )
			->caller( __METHOD__ )
			->execute();
		$barTextId = $this->getDb()->insertId();

		// text_in_lang records
		$this->getDb()->newInsertQueryBuilder()
			->insertInto( 'wbt_text_in_lang' )
			->row( [ 'wbxl_language' => 'en', 'wbxl_text_id' => $fooTextId ] )
			->caller( __METHOD__ )
			->execute();
		$enFooTextInLangId = $this->getDb()->insertId();
		$this->getDb()->newInsertQueryBuilder()
			->insertInto( 'wbt_text_in_lang' )
			->row( [ 'wbxl_language' => 'de', 'wbxl_text_id' => $fooTextId ] )
			->caller( __METHOD__ )
			->execute();
		$deFooTextInLangId = $this->getDb()->insertId();
		$this->getDb()->newInsertQueryBuilder()
			->insertInto( 'wbt_text_in_lang' )
			->row( [ 'wbxl_language' => 'en', 'wbxl_text_id' => $barTextId ] )
			->caller( __METHOD__ )
			->execute();
		$enBarTextInLangId = $this->getDb()->insertId();
		$this->getDb()->newInsertQueryBuilder()
			->insertInto( 'wbt_text_in_lang' )
			->row( [ 'wbxl_language' => 'de', 'wbxl_text_id' => $barTextId ] )
			->caller( __METHOD__ )
			->execute();
		$deBarTextInLangId = $this->getDb()->insertId();

		// label term_in_lang records
		$this->getDb()->newInsertQueryBuilder()
			->insertInto( 'wbt_term_in_lang' )
			->row( [ 'wbtl_type_id' => self::TYPE_LABEL, 'wbtl_text_in_lang_id' => $enFooTextInLangId ] )
			->caller( __METHOD__ )
			->execute();
		$this->enFooLabelTermInLangId = $this->getDb()->insertId();
		$this->getDb()->newInsertQueryBuilder()
			->insertInto( 'wbt_term_in_lang' )
			->row( [ 'wbtl_type_id' => self::TYPE_LABEL, 'wbtl_text_in_lang_id' => $enBarTextInLangId ] )
			->caller( __METHOD__ )
			->execute();
		$this->enBarLabelTermInLangId = $this->getDb()->insertId();
		$this->getDb()->newInsertQueryBuilder()
			->insertInto( 'wbt_term_in_lang' )
			->row( [ 'wbtl_type_id' => self::TYPE_LABEL, 'wbtl_text_in_lang_id' => $deFooTextInLangId ] )
			->caller( __METHOD__ )
			->execute();
		$this->deFooLabelTermInLangId = $this->getDb()->insertId();
		$this->getDb()->newInsertQueryBuilder()
			->insertInto( 'wbt_term_in_lang' )
			->row( [ 'wbtl_type_id' => self::TYPE_LABEL, 'wbtl_text_in_lang_id' => $deBarTextInLangId ] )
			->caller( __METHOD__ )
			->execute();
		$this->deBarLabelTermInLangId = $this->getDb()->insertId();

		// description term_in_lang records
		$this->getDb()->newInsertQueryBuilder()
			->insertInto( 'wbt_term_in_lang' )
			->row( [ 'wbtl_type_id' => self::TYPE_DESCRIPTION, 'wbtl_text_in_lang_id' => $enFooTextInLangId ] )
			->caller( __METHOD__ )
			->execute();
		$this->enFooDescriptionTermInLangId = $this->getDb()->insertId();
		$this->getDb()->newInsertQueryBuilder()
			->insertInto( 'wbt_term_in_lang' )
			->row( [ 'wbtl_type_id' => self::TYPE_DESCRIPTION, 'wbtl_text_in_lang_id' => $enBarTextInLangId ] )
			->caller( __METHOD__ )
			->execute();
		$this->enBarDescriptionTermInLangId = $this->getDb()->insertId();
		$this->getDb()->newInsertQueryBuilder()
			->insertInto( 'wbt_term_in_lang' )
			->row( [ 'wbtl_type_id' => self::TYPE_DESCRIPTION, 'wbtl_text_in_lang_id' => $deFooTextInLangId ] )
			->caller( __METHOD__ )
			->execute();
		$this->deFooDescriptionTermInLangId = $this->getDb()->insertId();
		$this->getDb()->newInsertQueryBuilder()
			->insertInto( 'wbt_term_in_lang' )
			->row( [ 'wbtl_type_id' => self::TYPE_DESCRIPTION, 'wbtl_text_in_lang_id' => $deBarTextInLangId ] )
			->caller( __METHOD__ )
			->execute();
		$this->deBarDescriptionTermInLangId = $this->getDb()->insertId();
	}

	private function makeTestSubject( string $entityType ): DatabaseTermsCollisionDetector {
		return new DatabaseTermsCollisionDetector(
			$entityType,
			$this->getTermsDomainDb(),
			$this->typeIdsLookup
		);
	}

	public function testGivenPropertyLabelTest_whenCollisionExists_returnsCollidingProperyId(): void {
		$this->getDb()->newInsertQueryBuilder()
			->insertInto( 'wbt_property_terms' )
			->rows( [
				[ 'wbpt_term_in_lang_id' => $this->enBarLabelTermInLangId, 'wbpt_property_id' => 1 ],
				[ 'wbpt_term_in_lang_id' => $this->deFooLabelTermInLangId, 'wbpt_property_id' => 1 ],
				[ 'wbpt_term_in_lang_id' => $this->enFooLabelTermInLangId, 'wbpt_property_id' => 2 ],
				[ 'wbpt_term_in_lang_id' => $this->deBarLabelTermInLangId, 'wbpt_property_id' => 2 ],
			] )
			->caller( __METHOD__ )
			->execute();

		$collisionDetector = $this->makeTestSubject( 'property' );

		$propertyId = $collisionDetector->detectLabelCollision( 'de', 'foo' );
		$this->assertEquals( NumericPropertyId::newFromNumber( 1 ), $propertyId );

		$propertyId = $collisionDetector->detectLabelCollision( 'en', 'foo' );
		$this->assertEquals( NumericPropertyId::newFromNumber( 2 ), $propertyId );
	}

	public function testGivenPropertyLabelTest_whenNoCollisionsExists_returnsNull(): void {
		$this->getDb()->newInsertQueryBuilder()
			->insertInto( 'wbt_property_terms' )
			->rows( [
				[ 'wbpt_term_in_lang_id' => $this->deFooLabelTermInLangId, 'wbpt_property_id' => 1 ],
				[ 'wbpt_term_in_lang_id' => $this->enBarLabelTermInLangId, 'wbpt_property_id' => 2 ],
			] )
			->caller( __METHOD__ )
			->execute();

		$collisionDetector = $this->makeTestSubject( 'property' );

		$propertyId = $collisionDetector->detectLabelCollision( 'en', 'foo' );
		$this->assertNull( $propertyId );

		$propertyId = $collisionDetector->detectLabelCollision( 'de', 'bar' );
		$this->assertNull( $propertyId );
	}

	public function testGivenItemLabelTest_whenCollisionExists_returnsCollidingProperyId(): void {
		$this->getDb()->newInsertQueryBuilder()
			->insertInto( 'wbt_item_terms' )
			->rows( [
				[ 'wbit_term_in_lang_id' => $this->enBarLabelTermInLangId, 'wbit_item_id' => 1 ],
				[ 'wbit_term_in_lang_id' => $this->deFooLabelTermInLangId, 'wbit_item_id' => 1 ],
				[ 'wbit_term_in_lang_id' => $this->enFooLabelTermInLangId, 'wbit_item_id' => 2 ],
				[ 'wbit_term_in_lang_id' => $this->deBarLabelTermInLangId, 'wbit_item_id' => 2 ],
			] )
			->caller( __METHOD__ )
			->execute();

		$collisionDetector = $this->makeTestSubject( 'item' );

		$itemId = $collisionDetector->detectLabelCollision( 'de', 'foo' );
		$this->assertEquals( ItemId::newFromNumber( 1 ), $itemId );

		$itemId = $collisionDetector->detectLabelCollision( 'en', 'foo' );
		$this->assertEquals( ItemId::newFromNumber( 2 ), $itemId );
	}

	public function testGivenItemLabelTest_whenNoCollisionsExists_returnsNull(): void {
		$this->getDb()->newInsertQueryBuilder()
			->insertInto( 'wbt_item_terms' )
			->rows( [
				[ 'wbit_term_in_lang_id' => $this->deFooLabelTermInLangId, 'wbit_item_id' => 1 ],
				[ 'wbit_term_in_lang_id' => $this->enBarLabelTermInLangId, 'wbit_item_id' => 2 ],
			] )
			->caller( __METHOD__ )
			->execute();

		$collisionDetector = $this->makeTestSubject( 'item' );

		$itemId = $collisionDetector->detectLabelCollision( 'en', 'foo' );
		$this->assertNull( $itemId );

		$itemId = $collisionDetector->detectLabelCollision( 'de', 'bar' );
		$this->assertNull( $itemId );
	}

	public function testGivenPropertyLabelDescriptionTest_whenCollisionExists_returnsCollidingProperyId(): void {
		$this->getDb()->newInsertQueryBuilder()
			->insertInto( 'wbt_property_terms' )
			->rows( [
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
			] )
			->caller( __METHOD__ )
			->execute();

		$collisionDetector = $this->makeTestSubject( 'property' );

		$propertyId = $collisionDetector->detectLabelAndDescriptionCollision( 'en', 'bar', 'bar' );
		$this->assertEquals( NumericPropertyId::newFromNumber( 1 ), $propertyId );

		$propertyId = $collisionDetector->detectLabelAndDescriptionCollision( 'de', 'bar', 'foo' );
		$this->assertEquals( NumericPropertyId::newFromNumber( 2 ), $propertyId );

		$propertyId = $collisionDetector->detectLabelAndDescriptionCollision( 'en', 'bar', 'foo' );
		$this->assertEquals( NumericPropertyId::newFromNumber( 3 ), $propertyId );
	}

	public function testGivenPropertyLabelDescriptionTest_whenNoCollisionsExists_returnsNull(): void {
		$this->getDb()->newInsertQueryBuilder()
			->insertInto( 'wbt_property_terms' )
			->rows( [
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
			] )
			->caller( __METHOD__ )
			->execute();

		$collisionDetector = $this->makeTestSubject( 'property' );

		$propertyId = $collisionDetector->detectLabelAndDescriptionCollision( 'en', 'foo', 'foo' );
		$this->assertNull( $propertyId );

		$propertyId = $collisionDetector->detectLabelAndDescriptionCollision( 'de', 'bar', 'bar' );
		$this->assertNull( $propertyId );
	}

	public function testGivenItemLabelDescriptionTest_whenCollisionExists_returnsCollidingProperyId(): void {
		$this->getDb()->newInsertQueryBuilder()
			->insertInto( 'wbt_item_terms' )
			->rows( [
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
			] )
			->caller( __METHOD__ )
			->execute();

		$collisionDetector = $this->makeTestSubject( 'item' );

		$itemId = $collisionDetector->detectLabelAndDescriptionCollision( 'en', 'bar', 'bar' );
		$this->assertEquals( ItemId::newFromNumber( 1 ), $itemId );

		$itemId = $collisionDetector->detectLabelAndDescriptionCollision( 'de', 'bar', 'foo' );
		$this->assertEquals( ItemId::newFromNumber( 2 ), $itemId );

		$itemId = $collisionDetector->detectLabelAndDescriptionCollision( 'en', 'bar', 'foo' );
		$this->assertEquals( ItemId::newFromNumber( 3 ), $itemId );
	}

	public function testGivenItemLabelDescriptionTest_whenNoCollisionsExists_returnsNull(): void {
		$this->getDb()->newInsertQueryBuilder()
			->insertInto( 'wbt_item_terms' )
			->rows( [
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
			] )
			->caller( __METHOD__ )
			->execute();

		$collisionDetector = $this->makeTestSubject( 'item' );

		$itemId = $collisionDetector->detectLabelAndDescriptionCollision( 'en', 'foo', 'foo' );
		$this->assertNull( $itemId );

		$itemId = $collisionDetector->detectLabelAndDescriptionCollision( 'de', 'bar', 'bar' );
		$this->assertNull( $itemId );
	}

	/**
	 * @dataProvider termListProvider
	 */
	public function testDetectLabelsCollision( array $databaseRecords, TermList $termsList, array $expectedResults ): void {
		$records = [];
		foreach ( $databaseRecords as $key => $propertyRecords ) {
			foreach ( $propertyRecords as $record ) {
				$records[] = [ 'wbpt_term_in_lang_id' => $this->{$record}, 'wbpt_property_id' => $key ];
			}
		}

		$this->getDb()->newInsertQueryBuilder()
			->insertInto( 'wbt_property_terms' )
			->rows( $records )
			->caller( __METHOD__ )
			->execute();

		$collisionDetector = $this->makeTestSubject( 'property' );

		$properties = $collisionDetector->detectLabelsCollision( $termsList );

		$this->assertEquals( $expectedResults, $properties );
	}

	public static function termListProvider(): iterable {

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
