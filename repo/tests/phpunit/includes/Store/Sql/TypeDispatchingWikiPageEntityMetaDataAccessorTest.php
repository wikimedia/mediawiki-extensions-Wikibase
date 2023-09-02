<?php

namespace Wikibase\Repo\Tests\Store\Sql;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\LookupConstants;
use Wikibase\Lib\Store\Sql\TypeDispatchingWikiPageEntityMetaDataAccessor;
use Wikibase\Lib\Store\Sql\WikiPageEntityMetaDataAccessor;

/**
 * @covers \Wikibase\Lib\Store\Sql\TypeDispatchingWikiPageEntityMetaDataAccessor
 *
 * @group Wikibase
 * @group WikibaseStore
 *
 * @license GPL-2.0-or-later
 */
class TypeDispatchingWikiPageEntityMetaDataAccessorTest extends TestCase {

	public function testValidConstruction() {
		$callbackCalled = false;

		$i = new TypeDispatchingWikiPageEntityMetaDataAccessor(
			[ 'a' => function( $paramOne, $paramTwo ) use ( &$callbackCalled ) {
				$this->assertEquals( 'db', $paramOne );
				$this->assertEquals( 'repo', $paramTwo );
				$callbackCalled = true;
				return $this->createMock( WikiPageEntityMetaDataAccessor::class );
			} ],
			$this->createMock( WikiPageEntityMetaDataAccessor::class ),
			'db',
			'repo'
		);

		$this->assertInstanceOf( WikiPageEntityMetaDataAccessor::class, $i );
		$i->loadRevisionInformationByRevisionId( $this->getMockEntityId( 'a', 'ID' ), 1 );
		$this->assertTrue( $callbackCalled, 'Assert the callback was called with the correct parameters' );
	}

	private function getMockEntityId( string $type, string $serialization ): EntityId {
		$id = $this->createMock( EntityId::class );
		$id->method( 'getSerialization' )->willReturn( $serialization );
		$id->method( 'getEntityType' )->willReturn( $type );
		return $id;
	}

	public function testLoadRevisionInformationDispatching() {
		$entityIdOne = $this->getMockEntityId( 'type1', 'ID1' );
		$entityIdTwo = $this->getMockEntityId( 'type2', 'ID2' );
		$mode = LookupConstants::LATEST_FROM_REPLICA_WITH_FALLBACK;

		$accessor = $this->createMock( WikiPageEntityMetaDataAccessor::class );
		$accessor->method( 'loadRevisionInformation' )
			->with( [ $entityIdOne ], $mode )
			->willReturn( [ 'ID1' => 1 ] );

		$i = new TypeDispatchingWikiPageEntityMetaDataAccessor(
			[ 'type2' => function() use ( $entityIdTwo, $mode ) {
				$accessor = $this->createMock( WikiPageEntityMetaDataAccessor::class );
				$accessor->method( 'loadRevisionInformation' )
					->with( [ $entityIdTwo ], $mode )
					->willReturn( [ 'ID2' => 2 ] );
				return $accessor;
			} ],
			$accessor,
			'db',
			'repo'
		);

		$result = $i->loadRevisionInformation( [ $entityIdOne, $entityIdTwo ], $mode );
		$this->assertEquals(
			[
				'ID1' => 1,
				'ID2' => 2,
			],
			$result
		);
	}

	public function testLoadRevisionInformationByRevisionId() {
		$entityIdOne = $this->getMockEntityId( 'type1', 'ID1' );
		$entityIdTwo = $this->getMockEntityId( 'type2', 'ID2' );
		$mode = LookupConstants::LATEST_FROM_REPLICA_WITH_FALLBACK;

		$accessor = $this->createMock( WikiPageEntityMetaDataAccessor::class );
		$accessor->method( 'loadRevisionInformationByRevisionId' )
			->with( $entityIdOne, 1, $mode )
			->willReturn( 'ID1' );

		$i = new TypeDispatchingWikiPageEntityMetaDataAccessor(
			[ 'type2' => function() use ( $entityIdTwo, $mode ) {
				$accessor = $this->createMock( WikiPageEntityMetaDataAccessor::class );
				$accessor->method( 'loadRevisionInformationByRevisionId' )
					->with( $entityIdTwo, 2, $mode )
					->willReturn( 'ID2' );
				return $accessor;
			} ],
			$accessor,
			'db',
			'repo'
		);

		$resultOne = $i->loadRevisionInformationByRevisionId( $entityIdOne, 1, $mode );
		$resultTwo = $i->loadRevisionInformationByRevisionId( $entityIdTwo, 2, $mode );

		$this->assertEquals( 'ID1', $resultOne );
		$this->assertEquals( 'ID2', $resultTwo );
	}

	public function testLoadLatestRevisionIdsDispatching() {
		$entityIdOne = $this->getMockEntityId( 'type1', 'ID1' );
		$entityIdTwo = $this->getMockEntityId( 'type2', 'ID2' );
		$mode = LookupConstants::LATEST_FROM_REPLICA_WITH_FALLBACK;

		$accessor = $this->createMock( WikiPageEntityMetaDataAccessor::class );
		$accessor->method( 'loadLatestRevisionIds' )
			->with( [ $entityIdOne ], $mode )
			->willReturn( [ 'ID1' => 1 ] );

		$i = new TypeDispatchingWikiPageEntityMetaDataAccessor(
			[ 'type2' => function() use ( $entityIdTwo, $mode ) {
				$accessor = $this->createMock( WikiPageEntityMetaDataAccessor::class );
				$accessor->method( 'loadLatestRevisionIds' )
					->with( [ $entityIdTwo ], $mode )
					->willReturn( [ 'ID2' => 2 ] );
				return $accessor;
			} ],
			$accessor,
			'db',
			'repo'
		);

		$result = $i->loadLatestRevisionIds( [ $entityIdOne, $entityIdTwo ], $mode );
		$this->assertEquals(
			[
				'ID1' => 1,
				'ID2' => 2,
			],
			$result
		);
	}

}
