<?php

namespace Wikibase\Repo\Tests\Store\Sql;

use ArrayObject;
use MediaWiki\Storage\NameTableStore;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\Sql\EntityIdLocalPartPageTableEntityQuery;

/**
 * @group Wikibase
 * @group WikibaseRepo
 * @license GPL-2.0-or-later
 */
class EntityIdLocalPartPageTableEntityQueryHelperTest extends TestCase {

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

	public function testGetQueryInfo() {
		$slotRoleStore = $this->prophesize( NameTableStore::class );
		$slotRoleStore->getId( 'otherSlot' )->willReturn( 76 );

		$helper = new EntityIdLocalPartPageTableEntityQuery(
			new EntityNamespaceLookup( [ 'item' => 0, 'other' => 2 ], [ 'other' => 'otherSlot' ] ),
			$slotRoleStore->reveal()
		);

		$queryInfo = $helper->getQueryInfo(
			[ $this->getMockEntityId( 'item', 'Q1' ), $this->getMockEntityId( 'other', 'ooo123' ) ],
			wfGetDB( DB_REPLICA )
		);

		$this->assertEquals(
			[
				"(page_title = 'Q1' AND page_namespace = '0') OR (page_title = 'ooo123' AND page_namespace = '2' AND slot_role_id = '76')",
				[
					'slots' => [
						'INNER JOIN',
						'page_latest=slot_revision_id'
					]
				],
				[ 'page_title' ]
			],
			$queryInfo
		);
	}

	public function testMapRowsToEntityIds() {
		$helper = new EntityIdLocalPartPageTableEntityQuery(
			new EntityNamespaceLookup( [] ),
			$this->prophesize( NameTableStore::class )->reveal()
		);

		$rows = [
			(object)[ 'page_title' => 'Q1' ],
			(object)[ 'page_title' => 'U7' ],
		];

		$mappedRows = $helper->mapRowsToEntityIds( new ArrayObject( $rows ) );

		$this->assertEquals(
			[ 'Q1' => $rows[0], 'U7' => $rows[1] ],
			$mappedRows
		);
	}

}
