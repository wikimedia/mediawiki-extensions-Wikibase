<?php

namespace Wikibase\Repo\Tests\Store\Sql;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\EntityRevisionLookup;
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
				return $this->prophesize( WikiPageEntityMetaDataAccessor::class )->reveal();
			} ],
			$this->prophesize( WikiPageEntityMetaDataAccessor::class )->reveal(),
			'db',
			'repo'
		);

		$this->assertInstanceOf( WikiPageEntityMetaDataAccessor::class, $i );
		$i->loadRevisionInformationByRevisionId( $this->getMockEntityId( 'a', 'ID' ), 1 );
		$this->assertTrue( $callbackCalled, 'Assert the callback was called with the correct parameters' );
	}

	/**
	 * @param string $type
	 * @param string $serialization
	 * @return EntityId
	 */
	private function getMockEntityId( $type, $serialization ) {
		$id = $this->prophesize( EntityId::class );
		$id->getSerialization()->willReturn( $serialization );
		$id->getEntityType()->willReturn( $type );
		return $id->reveal();
	}

	public function testLoadRevisionInformationDispatching() {
		$entityIdOne = $this->getMockEntityId( 'type1', 'ID1' );
		$entityIdTwo = $this->getMockEntityId( 'type2', 'ID2' );
		$mode = EntityRevisionLookup::LATEST_FROM_REPLICA_WITH_FALLBACK;

		$accessor = $this->prophesize( WikiPageEntityMetaDataAccessor::class );
		$accessor->loadRevisionInformation( [ $entityIdOne ], $mode )
			->willReturn( [ 'ID1' => 1 ] );
		$accessor = $accessor->reveal();

		$i = new TypeDispatchingWikiPageEntityMetaDataAccessor(
			[ 'type2' => function() use ( $entityIdTwo, $mode ) {
				$accessor = $this->prophesize( WikiPageEntityMetaDataAccessor::class );
				$accessor->loadRevisionInformation( [ $entityIdTwo ], $mode )
					->willReturn( [ 'ID2' => 2 ] );
				return $accessor->reveal();
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
		$mode = EntityRevisionLookup::LATEST_FROM_REPLICA_WITH_FALLBACK;

		$accessor = $this->prophesize( WikiPageEntityMetaDataAccessor::class );
		$accessor->loadRevisionInformationByRevisionId( $entityIdOne, 1, $mode )
			->willReturn( 'ID1' );
		$accessor = $accessor->reveal();

		$i = new TypeDispatchingWikiPageEntityMetaDataAccessor(
			[ 'type2' => function() use ( $entityIdTwo, $mode ) {
				$accessor = $this->prophesize( WikiPageEntityMetaDataAccessor::class );
				$accessor->loadRevisionInformationByRevisionId( $entityIdTwo, 2, $mode )
					->willReturn( 'ID2' );
				return $accessor->reveal();
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
		$mode = EntityRevisionLookup::LATEST_FROM_REPLICA_WITH_FALLBACK;

		$accessor = $this->prophesize( WikiPageEntityMetaDataAccessor::class );
		$accessor->loadLatestRevisionIds( [ $entityIdOne ], $mode )
			->willReturn( [ 'ID1' => 1 ] );
		$accessor = $accessor->reveal();

		$i = new TypeDispatchingWikiPageEntityMetaDataAccessor(
			[ 'type2' => function() use ( $entityIdTwo, $mode ) {
				$accessor = $this->prophesize( WikiPageEntityMetaDataAccessor::class );
				$accessor->loadLatestRevisionIds( [ $entityIdTwo ], $mode )
					->willReturn( [ 'ID2' => 2 ] );
				return $accessor->reveal();
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
