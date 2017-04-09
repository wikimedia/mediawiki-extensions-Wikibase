<?php

namespace Wikibase\Repo\Tests\Store\Sql;

use MediaWiki\MediaWikiServices;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Reporting\ObservableMessageReporter;
use Wikibase\Repo\Store\Sql\SqlEntityIdPagerFactory;
use Wikibase\Repo\Store\Sql\TermSqlIndexBuilder;
use Wikibase\Repo\WikibaseRepo;

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
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		$entityStore = $wikibaseRepo->getEntityStore();
		$termSqlIndex = $wikibaseRepo->getStore()->getTermIndex();

		$testUser = $this->getTestUser()->getUser();

		foreach ( $this->getItems() as $item ) {
			$entityStore->saveEntity( $item, 'Test item: ' . $item->getId()->getSerialization(), $testUser, EDIT_NEW );
			$termSqlIndex->saveTermsOfEntity( $item );
		}

		$this->clearFullEntityIdColumn();

		$sqlEntityIdPagerFactory = new SqlEntityIdPagerFactory(
			$wikibaseRepo->getEntityNamespaceLookup(),
			new BasicEntityIdParser()
		);

		$termFullEntityIdBuilder = new TermSqlIndexBuilder(
			MediaWikiServices::getInstance()->getDBLoadBalancerFactory(),
			$termSqlIndex,
			$sqlEntityIdPagerFactory,
			$wikibaseRepo->getEntityRevisionLookup( 'uncached' ),
			$this->getMock( ObservableMessageReporter::class ),
			2
		);

		$termFullEntityIdBuilder->rebuild();

		$this->assertTermIndexRowsHaveFullEntityId( 'Q111', 5 );
		$this->assertTermIndexRowsHaveFullEntityId( 'Q112', 2 );
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

}
