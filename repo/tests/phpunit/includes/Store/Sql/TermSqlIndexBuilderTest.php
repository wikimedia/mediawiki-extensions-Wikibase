<?php

namespace Wikibase\Repo\Tests\Store\Sql;

use InvalidArgumentException;
use MediaWiki\MediaWikiServices;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\Store\Sql\SqlEntityIdPagerFactory;
use Wikibase\Repo\Store\Sql\TermSqlIndexBuilder;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\StringNormalizer;
use Wikibase\TermIndexEntry;

/**
 * @covers \Wikibase\Repo\Store\Sql\TermSqlIndexBuilder
 *
 * @group Wikibase
 * @group Database
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class TermSqlIndexBuilderTest extends \MediaWikiTestCase {

	protected function setUp() {
		$this->tablesUsed[] = 'page';
		$this->tablesUsed[] = 'wb_terms';

		parent::setUp();
	}

	public function testRebuildPopulatesFullEntityIdColumn() {
		$firstItem = $this->createItemWithNTerms( 'Q111', 5 );
		$secondItem = $this->createItemWithNTerms( 'Q112', 2 );
		$this->saveEntities( [ $firstItem, $secondItem ] );

		$this->getBuilder( [ Item::ENTITY_TYPE ] )->rebuild();

		$this->assertTermIndexRowsHaveFullEntityId( $firstItem->getId(), 5 );
		$this->assertTermIndexRowsHaveFullEntityId( $secondItem->getId(), 2 );
	}

	public function testGivenRebuildAllFlagSet_rebuildPopulatesFullEntityIdColumn() {
		$firstItem = $this->createItemWithNTerms( 'Q111', 5 );
		$secondItem = $this->createItemWithNTerms( 'Q112', 2 );
		$this->saveEntities( [ $firstItem, $secondItem ] );

		$builder = $this->getBuilder( [ Item::ENTITY_TYPE ] );
		$builder->setRebuildAllEntityTerms( true );

		$builder->rebuild();

		$this->assertTermIndexRowsHaveFullEntityId( $firstItem->getId(), 5 );
		$this->assertTermIndexRowsHaveFullEntityId( $secondItem->getId(), 2 );
	}

	public function testRebuildDoesNotDeleteDuplicateTermTableEntriesByDefault() {
		$label = 'cat';
		$languageCode = 'en';
		$item = $this->createItemWithIllegallyDuplicatedTerm( 'Q1', $languageCode, $label );

		$this->getBuilder( [ Item::ENTITY_TYPE ] )->rebuild();

		$terms = $this->getLabelTerms( $item, $languageCode );
		$this->assertCount( 2, $terms );
		$this->assertSame( $label, $terms[0]->getText() );
		$this->assertSame( $label, $terms[1]->getText() );
	}

	public function testGivenRemoveDupsFlagSet_rebuildDeletesDuplicateTermTableEntries() {
		$label = 'cat';
		$languageCode = 'en';
		$item = $this->createItemWithIllegallyDuplicatedTerm( 'Q1', $languageCode, $label );

		$builder = $this->getBuilder( [ Item::ENTITY_TYPE ] );
		$builder->setRemoveDuplicateTerms( true );

		$builder->rebuild();

		$terms = $this->getLabelTerms( $item, $languageCode );
		$this->assertCount( 1, $terms );
		$this->assertSame( $label, $terms[0]->getText() );
	}

	public function testGivenRebuildAllFlagSet_rebuildDeletesDuplicateTermTableEntries() {
		$label = 'cat';
		$languageCode = 'en';
		$item = $this->createItemWithIllegallyDuplicatedTerm( 'Q1', $languageCode, $label );

		$builder = $this->getBuilder( [ Item::ENTITY_TYPE ] );
		$builder->setRebuildAllEntityTerms( true );

		$builder->rebuild();

		$terms = $this->getLabelTerms( $item, $languageCode );
		$this->assertCount( 1, $terms );
		$this->assertSame( $label, $terms[0]->getText() );
	}

	public function testGivenRemoveDupsFlagSet_rebuildPopulatesFullEntityIdColumn() {
		$firstItem = $this->createItemWithNTerms( 'Q111', 5 );
		$secondItem = $this->createItemWithNTerms( 'Q112', 2 );
		$this->saveEntities( [ $firstItem, $secondItem ] );

		$builder = $this->getBuilder( [ Item::ENTITY_TYPE ] );
		$builder->setRemoveDuplicateTerms( true );

		$builder->rebuild();

		$this->assertTermIndexRowsHaveFullEntityId( $firstItem->getId(), 5 );
		$this->assertTermIndexRowsHaveFullEntityId( $secondItem->getId(), 2 );
	}

	public function testGivenRebuildAllFlag_rebuildAddsNewRowsToIndex() {
		$item = $this->createItemWithNTerms( 'Q1', 1 );
		$this->saveEntities( [ $item ] );

		$originalRowIds = $this->getTermRowIdsForEntity( $item->getId() );

		$builder = $this->getBuilder( [ Item::ENTITY_TYPE ] );
		$builder->setRebuildAllEntityTerms( true );

		$builder->rebuild();

		$this->assertTermIndexRowsHaveFullEntityId( $item->getId(), 1 );

		$rowIds = $this->getTermRowIdsForEntity( $item->getId() );

		$this->assertNotEquals( $originalRowIds, $rowIds );
	}

	public function testGivenRebuildAllFlagNotSet_rebuildOnlyUpdatesExistingRows() {
		$item = $this->createItemWithNTerms( 'Q1', 1 );
		$this->saveEntities( [ $item ] );

		$originalRowIds = $this->getTermRowIdsForEntity( $item->getId() );

		$this->getBuilder( [ Item::ENTITY_TYPE ] )->rebuild();

		$this->assertTermIndexRowsHaveFullEntityId( $item->getId(), 1 );

		$rowIds = $this->getTermRowIdsForEntity( $item->getId() );

		$this->assertEquals( $originalRowIds, $rowIds );
	}

	/**
	 * @param string[] $entityTypes
	 *
	 * @return TermSqlIndexBuilder
	 */
	private function getBuilder( array $entityTypes ) {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		$sqlEntityIdPagerFactory = new SqlEntityIdPagerFactory(
			$wikibaseRepo->getEntityNamespaceLookup(),
			new BasicEntityIdParser()
		);

		$builder = new TermSqlIndexBuilder(
			MediaWikiServices::getInstance()->getDBLoadBalancerFactory(),
			$wikibaseRepo->getStore()->getTermIndex(),
			$sqlEntityIdPagerFactory,
			$wikibaseRepo->getEntityRevisionLookup( 'uncached' ),
			$entityTypes
		);

		$builder->setBatchSize( 2 );

		return $builder;
	}

	/**
	 * @param EntityDocument[] $entities
	 */
	private function saveEntities( array $entities ) {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		$entityStore = $wikibaseRepo->getEntityStore();
		$termSqlIndex = $wikibaseRepo->getStore()->getTermIndex();

		$testUser = $this->getTestUser()->getUser();

		foreach ( $entities as $entity ) {
			$entityStore->saveEntity(
				$entity,
				'Test entity: ' . $entity->getId()->getSerialization(),
				$testUser,
				EDIT_NEW
			);

			$termSqlIndex->saveTermsOfEntity( $entity );
		}
	}

	private function assertTermIndexRowsHaveFullEntityId( EntityId $entityId, $numRows ) {
		$entityId = $entityId->getSerialization();

		$rows = $this->db->select(
			TermSqlIndexBuilder::TABLE_NAME,
			'*',
			[ 'term_full_entity_id' => $entityId ],
			__METHOD__
		);

		$this->assertSame( $numRows, $rows->numRows() );

		foreach ( $rows as $row ) {
			$this->assertSame( $entityId, $row->term_full_entity_id );
		}
	}

	private function insertTerm( EntityDocument $entity, $termLanguage, $termType, $termText ) {
		$stringNormalizer = new StringNormalizer();

		$db = wfGetDB( DB_MASTER );
		$db->insert(
			'wb_terms',
			[
				[
					'term_full_entity_id' => $entity->getId()->getSerialization(),
					'term_entity_id' => 0,
					'term_entity_type' => $entity->getType(),
					'term_language' => $termLanguage,
					'term_type' => $termType,
					'term_text' => $termText,
					'term_search_key' => $stringNormalizer->cleanupToNFC( $termText ),
					'term_weight' => 0.001,
				]
			],
			__METHOD__
		);
	}

	private function createItemWithNTerms( $itemId, $numberOfTerms ) {
		$item = new Item( new ItemId( $itemId ) );

		for ( $i = 0; $i < $numberOfTerms; $i++ ) {
			$item->setLabel( "en-$i", "label-$i" );
		}

		return $item;
	}

	private function createItemWithIllegallyDuplicatedTerm( $itemId, $languageCode, $label ) {
		$item = new Item( new ItemId( $itemId ) );
		$item->setLabel( $languageCode, $label );

		$this->saveEntities( [ $item ] );

		$terms = $this->getLabelTerms( $item, $languageCode );

		$this->assertCount( 1, $terms );

		// Force duplicate term for the item
		$this->insertTerm( $item, $languageCode, 'label', $label );

		$terms = $this->getLabelTerms( $item, $languageCode );

		$this->assertCount( 2, $terms );
		$this->assertEquals( $terms[0], $terms[1] );
		$this->assertSame( $label, $terms[0]->getText() );
		return $item;
	}

	/**
	 * @param Item $item
	 * @param string $languageCode
	 *
	 * @return TermIndexEntry[]
	 */
	private function getLabelTerms( Item $item, $languageCode ) {
		$termIndex = WikibaseRepo::getDefaultInstance()->getStore()->getTermIndex();
		$terms = $termIndex->getTermsOfEntity( $item->getId(), [ 'label' ], [ $languageCode ] );
		return $terms;
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @return int[]
	 */
	private function getTermRowIdsForEntity( EntityId $entityId ) {
		$db = wfGetDB( DB_MASTER );

		return $db->selectFieldValues(
			'wb_terms',
			'term_row_id',
			[ 'term_full_entity_id' => $entityId->getSerialization() ],
			__METHOD__
		);
	}

	public function provideNonIntegerFromIdValues() {
		return [
			[ null ],
			[ '5' ]
		];
	}

	/**
	 * @dataProvider provideNonIntegerFromIdValues
	 */
	public function testGivenNotInteger_setFromIdThrowsException( $invalidFromId ) {
		$builder = $this->getBuilder( [ Item::ENTITY_TYPE ] );

		$this->setExpectedException( InvalidArgumentException::class );

		$builder->setFromId( $invalidFromId );
	}

}
