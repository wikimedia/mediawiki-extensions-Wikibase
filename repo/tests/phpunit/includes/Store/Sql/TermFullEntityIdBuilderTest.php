<?php

namespace Wikibase\Repo\Tests\Store\Sql;

use MediaWiki\MediaWikiServices;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Reporting\ObservableMessageReporter;
use Wikibase\Repo\Store\Sql\TermFullEntityIdBuilder;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Wikibase\Repo\Store\Sql\TermFullEntityIdBuilder
 *
 * @group Wikibase
 * @group Database
 * @group medium
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class TermFullEntityIdBuilderTest extends \MediaWikiTestCase {

	protected function setUp() {
		$this->tablesUsed[] = 'wb_terms';

		parent::setUp();
	}

	public function testRebuild( ) {
		$termSqlIndex = WikibaseRepo::getDefaultInstance()->getStore()->getTermIndex();

		foreach ( $this->getItems() as $item ) {
			$termSqlIndex->saveTermsOfEntity( $item );
		}

		$this->clearFullEntityIdColumn();
		$this->assertFullEntityIdIsNull();

		$termFullEntityIdBuilder = new TermFullEntityIdBuilder(
			MediaWikiServices::getInstance()->getDBLoadBalancerFactory(),
			WikibaseRepo::getDefaultInstance()->getEntityIdComposer(),
			new BasicEntityIdParser(),
			$this->getMockBuilder( ObservableMessageReporter::class )->getMock(),
			2,
			false
		);

		$termFullEntityIdBuilder->rebuild();

		$rows = $this->db->select(
			$this->db->tableName( TermFullEntityIdBuilder::TABLE_NAME ),
			'*',
			[ 'term_full_entity_id' => 'Q111' ],
			__METHOD__
		);

		// TODO
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
			$this->db->tableName( TermFullEntityIdBuilder::TABLE_NAME ),
			[ 'term_full_entity_id' => NULL ],
			[],
			__METHOD__
		);
	}

	private function assertFullEntityIdIsNull() {
		$rows = $this->db->select(
			$this->db->tableName( TermFullEntityIdBuilder::TABLE_NAME ),
			'*',
			[],
			__METHOD__
		);

		foreach ( $rows as $row ) {
			$this->assertNull( $row->term_full_entity_id );
		}
	}
}
