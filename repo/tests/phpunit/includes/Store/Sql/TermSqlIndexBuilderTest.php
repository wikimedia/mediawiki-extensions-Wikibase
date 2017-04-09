<?php

namespace Wikibase\Repo\Tests\Store\Sql;

use MediaWiki\MediaWikiServices;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Reporting\ObservableMessageReporter;
use Wikibase\Repo\Store\Sql\SqlEntityIdPagerFactory;
use Wikibase\Repo\Store\Sql\TermSqlIndexBuilder;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\StringNormalizer;

/**
 * @covers Wikibase\Repo\Store\Sql\TermSqlIndexBuilder
 *
 * @group Wikibase
 * @group Database
 * @group medium
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class TermSqlIndexBuilderTest extends \MediaWikiTestCase {

	protected function setUp() {
		$this->tablesUsed[] = 'page';
		$this->tablesUsed[] = 'wb_terms';

		parent::setUp();
	}

	public function testRebuildPopulatesFullEntityIdColumn() {
		$this->saveEntities( $this->getItems() );

		$this->clearFullEntityIdColumn();

		$termFullEntityIdBuilder = $this->getBuilder();

		$termFullEntityIdBuilder->rebuild();

		$this->assertTermIndexRowsHaveFullEntityId( 'Q111', 5 );
		$this->assertTermIndexRowsHaveFullEntityId( 'Q112', 2 );
	}

	/**
	 * @return TermSqlIndexBuilder
	 */
	private function getBuilder() {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		$sqlEntityIdPagerFactory = new SqlEntityIdPagerFactory(
			$wikibaseRepo->getEntityNamespaceLookup(),
			new BasicEntityIdParser()
		);

		return new TermSqlIndexBuilder(
			MediaWikiServices::getInstance()->getDBLoadBalancerFactory(),
			$wikibaseRepo->getStore()->getTermIndex(),
			$sqlEntityIdPagerFactory,
			$wikibaseRepo->getEntityRevisionLookup( 'uncached' ),
			$this->getMock( ObservableMessageReporter::class ),
			2
		);
	}

	private function saveEntities( array $entities ) {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		$entityStore = $wikibaseRepo->getEntityStore();
		$termSqlIndex = $wikibaseRepo->getStore()->getTermIndex();

		$testUser = $this->getTestUser()->getUser();

		foreach ( $entities as $entity ) {
			$entityStore->saveEntity( $entity, 'Test entity: ' . $entity->getId()->getSerialization(), $testUser, EDIT_NEW );
			$termSqlIndex->saveTermsOfEntity( $entity );
		}
	}

	private function assertTermIndexRowsHaveFullEntityId( $entityIdString, $numRows ) {
		$rows = $this->db->select(
			TermSqlIndexBuilder::TABLE_NAME,
			'*',
			[ 'term_full_entity_id' => $entityIdString ],
			__METHOD__
		);

		$this->assertSame( $numRows, $rows->numRows() );

		foreach ( $rows as $row ) {
			$this->assertSame( $entityIdString, $row->term_full_entity_id );
		}
	}

	/**
	 * @return Item[]
	 */
	private function getItems() {
		$items = [];

		$item = new Item();
		$item->setId( new ItemId( 'Q111' ) );
		$item->setLabel( 'en', 'cat' );
		$item->setDescription( 'en', 'feline' );
		$item->setLabel( 'de', 'Katze' );
		$item->setLabel( 'es', 'gato' );
		$item->setLabel( 'fr', 'chat' );

		$items[] = $item;

		$item2 = new Item();
		$item2->setId( new ItemId( 'Q112' ) );
		$item2->setLabel( 'en', 'dog' );
		$item2->setLabel( 'es', 'perro' );

		$items[] = $item2;

		return $items;
	}

	private function clearFullEntityIdColumn() {
		$this->db->update(
			TermSqlIndexBuilder::TABLE_NAME,
			[ 'term_full_entity_id' => null ],
			[],
			__METHOD__
		);
	}

	public function testRebuildDeletesDuplicateTermTableEntries() {
		$item = $this->getItems()[0];

		$this->saveEntities( [ $item ] );

		$termIndex = WikibaseRepo::getDefaultInstance()->getStore()->getTermIndex();

		$terms = $termIndex->getTermsOfEntity( $item->getId(), [ 'label' ], [ 'en' ] );

		$this->assertCount( 1, $terms );

		// Force duplicate term for the item
		$this->addTerm( $item, 'en', 'label', 'cat' );

		$terms = $termIndex->getTermsOfEntity( $item->getId(), [ 'label' ], [ 'en' ] );

		$this->assertCount( 2, $terms );
		$this->assertEquals( $terms[0], $terms[1] );
		$this->assertSame( 'cat', $terms[0]->getText() );

		$termFullEntityIdBuilder = $this->getBuilder();

		$termFullEntityIdBuilder->rebuild();

		$terms = $termIndex->getTermsOfEntity( $item->getId(), [ 'label' ], [ 'en' ] );

		$this->assertCount( 1, $terms );
		$this->assertSame( 'cat', $terms[0]->getText() );
	}

	private function addTerm( EntityDocument $entity, $termLanguage, $termType, $termText ) {
		$stringNormalizer = new StringNormalizer();

		$db = wfGetDB( DB_MASTER );
		$db->insert(
			'wb_terms',
			[
				[
					'term_full_entity_id' => $entity->getId()->getSerialization(),
					'term_entity_id' => $entity->getId()->getNumericId(),
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

}
