<?php

namespace Wikibase\Repo\Tests\Store\Sql;

use MediaWiki\MediaWikiServices;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Reporting\ObservableMessageReporter;
use Wikibase\Lib\Store\Sql\TermSqlIndex;
use Wikibase\Repo\Store\Sql\SqlEntityIdPagerFactory;
use Wikibase\Repo\Store\Sql\TermSqlIndexBuilder;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\StringNormalizer;

/**
 * @covers Wikibase\Repo\Store\Sql\TermFullEntityIdBuilder
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

	public function testRebuild() {
		$termSqlIndex = WikibaseRepo::getDefaultInstance()->getStore()->getTermIndex();

		foreach ( $this->getItems() as $item ) {
			$termSqlIndex->saveTermsOfEntity( $item );
		}

		$this->clearFullEntityIdColumn();

		$termSqlIndex = new TermSqlIndex(
			new StringNormalizer(),
			WikibaseRepo::getDefaultInstance()->getEntityIdComposer(),
			new BasicEntityIdParser(),
			false,
			'',
			true
		);

		$sqlEntityIdPagerFactory = new SqlEntityIdPagerFactory(
			WikibaseRepo::getDefaultInstance()->getEntityNamespaceLookup(),
			new BasicEntityIdParser()
		);

		$termFullEntityIdBuilder = new TermSqlIndexBuilder(
			MediaWikiServices::getInstance()->getDBLoadBalancerFactory(),
			$termSqlIndex,
			$sqlEntityIdPagerFactory,
			WikibaseRepo::getDefaultInstance()->getEntityRevisionLookup( 'uncached' ),
			$this->getMockBuilder( ObservableMessageReporter::class )->getMock(),
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
