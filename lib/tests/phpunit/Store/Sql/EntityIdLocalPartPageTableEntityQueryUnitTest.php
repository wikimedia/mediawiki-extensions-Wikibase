<?php

namespace Wikibase\Lib\Tests\Store\Sql;

use MediaWiki\Storage\NameTableStore;
use MediaWiki\Tests\MockDatabase;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\Sql\EntityIdLocalPartPageTableEntityQuery;
use Wikimedia\Rdbms\FakeResultWrapper;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\IExpression;
use Wikimedia\Rdbms\SelectQueryBuilder;

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

	private function getMockEntityId( string $type, string $idString ): EntityId {
		$id = $this->createMock( EntityId::class );
		$id->method( 'getSerialization' )->willReturn( $idString );
		$id->method( 'getEntityType' )->willReturn( $type );
		return $id;
	}

	public function testSelectRowsSimple_noSlottedEntities() {
		$query = new EntityIdLocalPartPageTableEntityQuery(
			new EntityNamespaceLookup( [ 'item' => 0 ], [] ),
			$this->createMock( NameTableStore::class )
		);

		$queryBuilderDb = new MockDatabase();
		$database = $this->createMock( IDatabase::class );
		$database->method( 'newSelectQueryBuilder' )
			->willReturnCallback( fn() => new SelectQueryBuilder( $database ) );
		$database->method( 'andExpr' )
			->willReturnCallback( fn( $conds ) => $queryBuilderDb->andExpr( $conds ) );
		$database->method( 'orExpr' )
			->willReturnCallback( fn( $conds ) => $queryBuilderDb->orExpr( $conds ) );
		// Extra fields, tables and joins passed to the method should also be requested
		// No join on the slots table should happen, as we are not looking at a slotted entity
		$selectArgs = [
			[
				[ "page", "revision" => "revision" ],
				[ "extraField", "page_title" ],
				[ "((page_title = 'Q1' AND page_namespace = 0))" ],
				[],
				[ 'revision' => [ 'JOIN', [ 'rev_id=extraField' ] ] ],
				new FakeResultWrapper(
					[
						(object)[ 'page_title' => 'Q1' ],
					]
				),
			],
		];
		$database->method( 'select' )
			->willReturnCallback( function ( $table, $vars, $conds, $fname, $options, $join_conds ) use ( &$selectArgs, $queryBuilderDb ) {
				[ $nextTable, $nextVars, $nextConds, $nextOptions, $nextJoinConds, $returnValue ] = array_shift( $selectArgs );
				foreach ( $conds as &$cond ) {
					if ( $cond instanceof IExpression ) {
						$cond = $cond->toSql( $queryBuilderDb );
					}
				}
				$this->assertSame( $nextTable, $table );
				$this->assertSame( $nextVars, $vars );
				$this->assertSame( $nextConds, $conds );
				$this->assertSame( $nextOptions, $options );
				$this->assertSame( $nextJoinConds, $join_conds );
				return $returnValue;
			} );

		$rows = $query->selectRows(
			[ 'extraField' ],
			[ 'rev_id=extraField' ],
			[ $this->getMockEntityId( 'item', 'Q1' ) ],
			$database
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
		$slotRoleStore = $this->createMock( NameTableStore::class );
		$slotRoleStore->method( 'getId' )
			->with( 'otherSlot' )
			->willReturn( 76 );

		$query = new EntityIdLocalPartPageTableEntityQuery(
			new EntityNamespaceLookup( [ 'item' => 0, 'other' => 2 ], [ 'other' => 'otherSlot' ] ),
			$slotRoleStore
		);

		$queryBuilderDb = new MockDatabase();
		$database = $this->createMock( IDatabase::class );
		$database->method( 'newSelectQueryBuilder' )
			->willReturnCallback( fn() => new SelectQueryBuilder( $database ) );
		$database->method( 'andExpr' )
			->willReturnCallback( fn( $conds ) => $queryBuilderDb->andExpr( $conds ) );
		$database->method( 'orExpr' )
			->willReturnCallback( fn( $conds ) => $queryBuilderDb->orExpr( $conds ) );
		$database->method( 'expr' )
			->willReturnCallback( fn( $field, $op, $value ) => $queryBuilderDb->expr( $field, $op, $value ) );
		// Extra fields, tables and joins passed to the method should also be requested
		$selectArgs = [
			[
				[ "page", "revision" => "revision", "slots" => "slots" ],
				[ "extraField", "page_title" ],
				[ "((page_title = 'Q1' AND page_namespace = 0) OR (page_title = 'ooo123' AND page_namespace = 2 AND slot_role_id = 76))" ],
				[],
				[
					'revision' => [ 'JOIN', [ 'rev_id=extraField' ] ],
					"slots" => [ "JOIN", "rev_id=slot_revision_id" ],
				],
				new FakeResultWrapper(
					[
						(object)[ 'page_title' => 'Q1' ],
						(object)[ 'page_title' => 'ooo123' ],
					]
				),
			],
		];
		$database->method( 'select' )
			->willReturnCallback( function ( $table, $vars, $conds, $fname, $options, $join_conds ) use ( &$selectArgs, $queryBuilderDb ) {
				[ $nextTable, $nextVars, $nextConds, $nextOptions, $nextJoinConds, $returnValue ] = array_shift( $selectArgs );
				foreach ( $conds as &$cond ) {
					if ( $cond instanceof IExpression ) {
						$cond = $cond->toSql( $queryBuilderDb );
					}
				}
				$this->assertSame( $nextTable, $table );
				$this->assertSame( $nextVars, $vars );
				$this->assertSame( $nextConds, $conds );
				$this->assertSame( $nextOptions, $options );
				$this->assertSame( $nextJoinConds, $join_conds );
				return $returnValue;
			} );

		$rows = $query->selectRows(
			[ 'extraField' ],
			[ 'rev_id=extraField' ],
			[ $this->getMockEntityId( 'item', 'Q1' ), $this->getMockEntityId( 'other', 'ooo123' ) ],
			$database
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
