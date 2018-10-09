<?php

namespace Wikibase\Lib\Tests\Store\Sql;

use ArrayObject;
use IDatabase;
use MediaWiki\Storage\NameTableStore;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\Sql\EntityIdLocalPartPageTableEntityQuery;
use Wikibase\Lib\Store\Sql\PageTableEntityQueryBase;

/**
 * @group Wikibase
 * @group WikibaseLib
 *
 * @covers \Wikibase\Lib\Store\Sql\EntityIdLocalPartPageTableEntityQuery
 * @covers \Wikibase\Lib\Store\Sql\PageTableEntityQueryBase
 *
 * @license GPL-2.0-or-later
 */
class EntityIdLocalPartPageTableEntityQueryUnitTest extends TestCase {

	/**
	 * @param string $type
	 * @param string $localPart
	 * @return EntityId
	 */
	private function getMockEntityId( $type, $localPart ) {
		$id = $this->prophesize( EntityId::class );
		$id->getLocalPart()->willReturn( $localPart );
		$id->getEntityType()->willReturn( $type );

		return $id->reveal();
	}

	public function testSelectRowsSimple_noSlottedEntities() {
		$query = new EntityIdLocalPartPageTableEntityQuery(
			new EntityNamespaceLookup( [ 'item' => 0 ], [] ),
			$this->prophesize( NameTableStore::class )->reveal()
		);

		$database = $this->prophesize( IDatabase::class );
		// For regular entities no slot_role_id should be requested
		$database->makeList( [ 'page_title' => 'Q1', 'page_namespace' => 0 ], LIST_AND )->will(
			function () {
				return 'Q1-condition';
			}
		);
		$database->makeList( [ "Q1-condition" ], LIST_OR )->will(
			function () {
				return 'combined-condition';
			}
		);
		// Extra fields, tables and joins passed to the method should also be requested
		// No join on the slots table should happen, as we are not looking at a slotted entity
		$database->select(
			[ "page", "extraTable" ],
			[ "extraField", "page_title" ],
			"combined-condition",
			PageTableEntityQueryBase::class . "::selectRows",
			[],
			[ 'extraTable' => "extraJoin" ]
		)->willReturn(
		// A Traversable object
			new ArrayObject(
				[
					(object)[ 'page_title' => 'Q1' ],
				]
			)
		);

		$rows = $query->selectRows(
			[ 'extraField' ],
			[ 'extraTable' => 'extraJoin' ],
			[ $this->getMockEntityId( 'item', 'Q1' ) ],
			$database->reveal()
		);

		// Result should be indexed by entity ID / by page_title
		$this->assertEquals(
			[
				'Q1' => (object)[ 'page_title' => 'Q1' ],
			],
			$rows
		);
	}

	public function testSelectRowsCombination() {
		$slotRoleStore = $this->prophesize( NameTableStore::class );
		$slotRoleStore->getId( 'otherSlot' )->willReturn( 76 );

		$query = new EntityIdLocalPartPageTableEntityQuery(
			new EntityNamespaceLookup( [ 'item' => 0, 'other' => 2 ], [ 'other' => 'otherSlot' ] ),
			$slotRoleStore->reveal()
		);

		$database = $this->prophesize( IDatabase::class );
		// For regular entities no slot_role_id should be requested
		$database->makeList( [ 'page_title' => 'Q1', 'page_namespace' => 0 ], LIST_AND )->will(
			function () {
				return 'Q1-condition';
			}
		);
		// For entities in slots the slot role ID should be in the query
		$database->makeList(
			[ "page_title" => "ooo123", "page_namespace" => 2, "slot_role_id" => 76 ],
			LIST_AND
		)->will(
			function () {
				return 'ooo123-condition';
			}
		);
		$database->makeList( [ "Q1-condition", "ooo123-condition" ], LIST_OR )->will(
			function () {
				return 'combined-condition';
			}
		);
		// Extra fields, tables and joins passed to the method should also be requested
		$database->select(
			[ "page", "extraTable", "slots" ],
			[ "extraField", "page_title" ],
			"combined-condition",
			PageTableEntityQueryBase::class . "::selectRows",
			[],
			[ 'extraTable' => "extraJoin", "slots" => [ "INNER JOIN", "page_latest=slot_revision_id" ] ]
		)->willReturn(
		// A Traversable object
			new ArrayObject(
				[
					(object)[ 'page_title' => 'Q1' ],
					(object)[ 'page_title' => 'ooo123' ],
				]
			)
		);

		$rows = $query->selectRows(
			[ 'extraField' ],
			[ 'extraTable' => 'extraJoin' ],
			[ $this->getMockEntityId( 'item', 'Q1' ), $this->getMockEntityId( 'other', 'ooo123' ) ],
			$database->reveal()
		);

		// Result should be indexed by entity ID / by page_title
		$this->assertEquals(
			[
				'Q1' => (object)[ 'page_title' => 'Q1' ],
				'ooo123' => (object)[ 'page_title' => 'ooo123' ],
			],
			$rows
		);
	}

}
