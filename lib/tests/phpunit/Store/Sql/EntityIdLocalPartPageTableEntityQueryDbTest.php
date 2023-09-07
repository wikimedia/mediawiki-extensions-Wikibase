<?php

namespace Wikibase\Lib\Tests\Store\Sql;

use Error;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\Storage\NameTableStore;
use MediaWiki\Title\Title;
use MediaWikiIntegrationTestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\Sql\EntityIdLocalPartPageTableEntityQuery;

/**
 * @group Wikibase
 * @group WikibaseLib
 * @group Database
 *
 * @covers \Wikibase\Lib\Store\Sql\EntityIdLocalPartPageTableEntityQuery
 * @covers \Wikibase\Lib\Store\Sql\PageTableEntityQueryBase
 *
 * @license GPL-2.0-or-later
 */
class EntityIdLocalPartPageTableEntityQueryDbTest extends MediaWikiIntegrationTestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->tablesUsed[] = 'page';
		$this->tablesUsed[] = 'revision';
		$this->tablesUsed[] = 'slots';
		$this->tablesUsed[] = 'slot_roles';
		$this->getExistingTestPage( Title::makeTitle( 1, 'LocalPartOne' ) );
		$this->db->insert(
			'page',
			[
				'page_title' => 'localPartTwo',
				'page_namespace' => 2,
				'page_random' => 2,
				'page_latest' => 221,
				'page_len' => 2,
				'page_touched' => $this->db->timestamp(),
			]
		);
		$this->db->insert( // insert an older revision for one tests (no other revisions)
			'revision',
			[
				'rev_id' => 220,
				'rev_actor' => 0,
				'rev_comment_id' => 0,
				'rev_page' => $this->db->insertId(),
				'rev_timestamp' => $this->db->timestamp(),
			]
		);
		$this->db->insert(
			'slots',
			[
				'slot_revision_id' => 221,
				'slot_role_id' => 22,
				'slot_content_id' => 223,
				'slot_origin' => 224,
			]
		);
		$this->db->insert(
			'slot_roles',
			[
				'role_id' => 22,
				'role_name' => 'second',
			]
		);
	}

	private function getQuery() {
		$slotRoleStore = $this->createMock( NameTableStore::class );
		$slotRoleStore->method( 'getId' )
			->willReturnCallback( static function ( string $name ) {
				if ( $name === SlotRecord::MAIN ) {
					return 0;
				} elseif ( $name === 'second' ) {
					return 22;
				} else {
					throw new Error( 'Unexpected getId() call' );
				}
			} );

		return new EntityIdLocalPartPageTableEntityQuery(
			new EntityNamespaceLookup(
				[ 'entityTypeOne' => 1, 'entityTypeTwo' => 2 ],
				[ 'entityTypeTwo' => 'second' ]
			), $slotRoleStore
		);
	}

	private function getMockEntityId( string $type, string $idString ): EntityId {
		$id = $this->createMock( EntityId::class );
		$id->method( 'getSerialization' )->willReturn( $idString );
		$id->method( 'getEntityType' )->willReturn( $type );
		return $id;
	}

	public function provideSelectRows() {
		return [
			[
				[],
				null,
				[ $this->getMockEntityId( 'entityTypeOne', 'LocalPartOne' ) ],
				[ 'LocalPartOne' => (object)[ 'page_title' => 'LocalPartOne' ] ],
			],
			[
				[],
				null,
				[ $this->getMockEntityId( 'entityTypeOne', 'localPartNone' ) ],
				[],
			],
			[
				[ 'page_namespace' ],
				null,
				[ $this->getMockEntityId( 'entityTypeOne', 'LocalPartOne' ) ],
				[
					'LocalPartOne' => (object)[
						'page_title' => 'LocalPartOne',
						'page_namespace' => 1,
					],
				],
			],
			[
				[ 'page_namespace' ],
				null,
				[ $this->getMockEntityId( 'entityTypeTwo', 'localPartTwo' ) ],
				[
					'localPartTwo' => (object)[
						'page_title' => 'localPartTwo',
						'page_namespace' => 2,
					],
				],
			],
			[
				[ 'page_namespace' ],
				null,
				[
					$this->getMockEntityId( 'entityTypeOne', 'LocalPartOne' ),
					$this->getMockEntityId( 'entityTypeTwo', 'localPartTwo' ),
				],
				[
					'LocalPartOne' => (object)[
						'page_title' => 'LocalPartOne',
						'page_namespace' => 1,
					],
					'localPartTwo' => (object)[
						'page_title' => 'localPartTwo',
						'page_namespace' => 2,
					],
				],
			],
			[
				[ 'page_namespace' ],
				[ 'rev_page=page_id', 'rev_id' => 220 ],
				[
					$this->getMockEntityId( 'entityTypeTwo', 'localPartTwo' ),
				],
				[],
			],
		];
	}

	/**
	 * @dataProvider provideSelectRows
	 */
	public function testSelectRows( $fields, $revisionJoinConds, $entityIds, $expected ) {
		$query = $this->getQuery();
		$rows = $query->selectRows(
			$fields,
			$revisionJoinConds,
			$entityIds,
			$this->db
		);
		$this->assertEquals( $expected, $rows );
	}

}
