<?php

namespace Wikibase\Client\Tests\Unit\Changes;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RecentChange;
use Title;
use TitleFactory;
use Wikibase\Client\Changes\InjectRCRecordsJob;
use Wikibase\Client\RecentChanges\RecentChangeFactory;
use Wikibase\Client\RecentChanges\RecentChangesFinder;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Diff\EntityDiffer;
use Wikibase\Lib\Changes\EntityChange;
use Wikibase\Lib\Changes\EntityChangeFactory;
use Wikibase\Lib\Changes\EntityDiffChangedAspectsFactory;
use Wikibase\Lib\Changes\ItemChange;
use Wikibase\Lib\Rdbms\ClientDomainDb;
use Wikibase\Lib\Store\Sql\EntityChangeLookup;
use Wikimedia\Rdbms\ConnectionManager;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \Wikibase\Client\Changes\InjectRCRecordsJob
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseChange
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class InjectRCRecordsJobTest extends TestCase {

	/**
	 * @return RecentChangeFactory|MockObject
	 */
	private function getRCFactoryMock() {
		$rcFactory = $this->getMockBuilder( RecentChangeFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$rcFactory->method( 'prepareChangeAttributes' )
			->willReturn( [] );

		return $rcFactory;
	}

	/**
	 * @param EntityChange[] $knownChanges
	 *
	 * @return MockObject|EntityChangeLookup
	 */
	private function getEntityChangeLookupMock( array $knownChanges = [] ) {
		$changeLookup = $this->getMockBuilder( EntityChangeLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$changes = [];
		foreach ( $knownChanges as $change ) {
			$id = $change->getId();
			$changes[$id] = $change;
		}

		$changeLookup->method( 'loadByChangeIds' )
			->willReturnCallback( function ( $ids ) use ( $changes ) {
				return array_values( array_intersect_key( $changes, array_flip( $ids ) ) );
			} );

		return $changeLookup;
	}

	/**
	 * @return EntityChangeFactory
	 */
	private function getEntityChangeFactory() {
		return new EntityChangeFactory(
			new EntityDiffer(),
			new BasicEntityIdParser(),
			[ Item::ENTITY_TYPE => ItemChange::class ]
		);
	}

	/**
	 * @return RecentChangesFinder|MockObject
	 */
	private function getRCDupeDetectorMock() {
		$rcDupeDetector = $this->getMockBuilder( RecentChangesFinder::class )
			->disableOriginalConstructor()
			->getMock();

		return $rcDupeDetector;
	}

	/**
	 * @return TitleFactory|MockObject
	 */
	private function getTitleFactoryMock() {
		$titleFactory = $this->createMock( TitleFactory::class );

		$id = 200;
		$titleFactory->method( 'makeTitle' )
			->willReturnCallback( function( $ns, $text ) use ( &$id ) {
				return $this->getTitleMock( $text, $id++ );
			} );

		return $titleFactory;
	}

	/**
	 * @param string $text
	 * @param int $id
	 *
	 * @return Title|MockObject
	 */
	private function getTitleMock( $text, $id = 23 ) {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$title->method( 'getArticleID' )
			->willReturn( $id );

		$title->method( 'exists' )
			->willReturn( true );

		$title->method( 'getDBkey' )
			->willReturn( $text );

		$title->method( 'getPrefixedDBkey' )
			->willReturn( $text );

		$title->method( 'getNamespace' )
			->willReturn( 0 );

		return $title;
	}

	/**
	 * @param int $id
	 * @param array $fields
	 *
	 * @return MockObject|EntityChange
	 */
	private function getEntityChangeMock( $id = 77, array $fields = [] ) {
		$info = $fields['info'] ?? [];

		$change = $this->getMockBuilder( EntityChange::class )
			->disableOriginalConstructor()
			->getMock();

		$change->method( 'getId' )
			->willReturn( $id );

		$change->method( 'getFields' )
			->willReturn( $fields );

		$change->method( 'getInfo' )
			->willReturn( $info );

		$change->method( 'getSerializedInfo' )
			->willReturn( json_encode( $info ) );

		$change->method( 'getAge' )
			->willReturn( 42 );

		return $change;
	}

	/**
	 * @return RecentChange|MockObject
	 */
	private function getRecentChangeMock() {
		$change = $this->getMockBuilder( RecentChange::class )
			->disableOriginalConstructor()
			->getMock();

		return $change;
	}

	/**
	 * @return ClientDomainDb
	 */
	private function getClientDomainDbMock( IDatabase $dbWrite = null ): ClientDomainDb {
		$dbWrite = $dbWrite ?: $this->createMock( IDatabase::class );

		$connectionManager = $this->createMock( ConnectionManager::class );
		$connectionManager->method( 'getWriteConnection' )->willReturn( $dbWrite );
		$mock = $this->createMock( ClientDomainDb::class );
		$mock->method( 'connections' )->willReturn( $connectionManager );

		return $mock;
	}

	public function provideMakeJobSpecification() {
		$title = $this->getTitleMock( 'Foo', 21 );
		$changeFactory = $this->getEntityChangeFactory();
		$itemId = new ItemId( 'Q7' );

		$child1 = $changeFactory->newFromFieldData( [
			'id' => 101,
			'type' => 'wikibase-item~change',
			'object_id' => $itemId->getSerialization(),
		] );

		$child2 = $changeFactory->newFromFieldData( [
			'id' => 102,
			'type' => 'wikibase-item~change',
			'object_id' => $itemId->getSerialization(),
		] );

		$diff = ( new EntityDiffChangedAspectsFactory() )->newEmpty();

		return [
			'mock change' => [
				[ $title ],
				$this->getEntityChangeMock(
					17,
					[
						'id' => 17,
						'object_id' => 'Q7',
						'type' => 'wikibase-item~change',
						'Test' => 'Kitten',
						'info' => []
					]
				),
			],
			'simple change with ID' => [
				[ $title ],
				$changeFactory->newForEntity(
					'change',
					new ItemId( 'Q7' ),
					[ 'id' => 7 ]
				),
			],
			'simple change with data but no ID' => [
				[ $title ],
				$changeFactory->newForEntity(
					'change',
					$itemId,
					[
						'type' => 'wikibase-item~change',
						'object_id' => $itemId->getSerialization(),
						'info' => [
							'compactDiff' => $diff,
							'stuff' => 'Fun Stuff',
							'metadata' => [
								'foo' => 'Kitten'
							]
						]
					]
				),
			],
			'composite change without ID' => [
				[ $title ],
				$changeFactory->newForEntity(
					'change',
					$itemId,
					[
						'type' => 'wikibase-item~change',
						'object_id' => $itemId->getSerialization(),
						'info' => [
							'compactDiff' => $diff,
							'change-ids' => [ 101, 102 ],
							'changes' => [ $child1, $child2 ],
						]
					]
				),
				[ $child1, $child2 ]
			],
		];
	}

	/**
	 * @dataProvider provideMakeJobSpecification()
	 * @param Title[] $titles
	 * @param EntityChange $change
	 */
	public function testMakeJobSpecification( array $titles, EntityChange $change, array $knownChanges = [] ) {
		$spec = InjectRCRecordsJob::makeJobSpecification( $titles, $change );

		$changeLookup = $this->getEntityChangeLookupMock( $knownChanges );
		$changeFactory = $this->getEntityChangeFactory();
		$rcFactory = $this->getRCFactoryMock();

		/** @var InjectRCRecordsJob $job */
		$job = TestingAccessWrapper::newFromObject( new InjectRCRecordsJob(
			$this->getClientDomainDbMock(),
			$changeLookup,
			$changeFactory,
			$rcFactory,
			$this->getTitleFactoryMock(),
			$spec->getParams()
		) );

		$actualChange = $job->getChange();

		$this->assertEquals( $change->getId(), $actualChange->getId(), 'Change ID' );
		$this->assertEquals( $change->getFields(), $actualChange->getFields(), 'Change Fields' );

		$actualTitles = $job->getTitles();

		$this->assertEquals(
			$this->getTitleIDs( $titles ),
			array_keys( $actualTitles ),
			'Title ID'
		);
		$this->assertEquals(
			$this->getTitleDBKeys( $titles ),
			array_values( $this->getTitleDBKeys( $actualTitles ) ),
			'Title DBKey'
		);
	}

	public function testMakeJobSpecification_rootJobParams() {
		$titles = [ $this->getTitleMock( 'Foo', 21 ) ];
		$change = $this->getEntityChangeFactory()->newForEntity(
			'change',
			new ItemId( 'Q7' ),
			[ 'id' => 7 ]
		);

		$rootJobParams = [
			'rootJobSignature' => 'kittens',
			'rootJobTimestamp' => '20170808010203',
		];

		$spec = InjectRCRecordsJob::makeJobSpecification( $titles, $change, $rootJobParams );

		$this->assertEquals( $rootJobParams, $spec->getRootJobParams() );
	}

	public function provideConstruction() {
		$change = $this->getEntityChangeMock(
			17,
			[
				'id' => 17,
				'object_id' => 'Q7',
				'type' => 'wikibase-item~change',
				'Test' => 'Kitten',
				'info' => []
			]
		);
		$title = $this->getTitleMock( 'Foo', 21 );

		return [
			// TODO: drop the change ID test case once T172394 has been deployed
			//       and old jobs have cleared the queue.
			'job spec using change ID' => [
				[
					'change' => $change->getId(),
					'pages' => $this->getPageSpecData( [ $title ] )
				],
				$change,
				[ $title ],
			],
			'job spec using change field data' => [
				[
					'change' => $change->getFields(),
					'pages' => $this->getPageSpecData( [ $title ] )
				],
				$change,
				[ $title ],
			],
		];
	}

	/**
	 * @param Title[] $titles
	 *
	 * @return array[]
	 */
	private function getPageSpecData( array $titles ) {
		$pages = [];

		foreach ( $titles as $title ) {
			$id = $title->getArticleID();
			$pages[$id] = [ $title->getNamespace(), $title->getDBkey() ];
		}

		return $pages;
	}

	/**
	 * @dataProvider provideConstruction()
	 */
	public function testConstruction(
		array $params,
		EntityChange $expectedChange,
		array $expectedTitles
	) {
		$changeLookup = $this->getEntityChangeLookupMock( [ $expectedChange ] );
		$changeFactory = $this->getEntityChangeFactory();
		$rcFactory = $this->getRCFactoryMock();

		/** @var InjectRCRecordsJob $job */
		$job = TestingAccessWrapper::newFromObject( new InjectRCRecordsJob(
			$this->getClientDomainDbMock(),
			$changeLookup,
			$changeFactory,
			$rcFactory,
			$this->getTitleFactoryMock(),
			$params
		) );

		$actualChange = $job->getChange();

		$this->assertEquals( $expectedChange->getId(), $actualChange->getId(), 'Change ID' );
		$this->assertEquals( $expectedChange->getFields(), $actualChange->getFields(), 'Change Fields' );

		$actualTitles = $job->getTitles();

		$this->assertEquals(
			$this->getTitleIDs( $expectedTitles ),
			array_keys( $actualTitles ),
			'Title ID'
		);
		$this->assertEquals(
			$this->getTitleDBKeys( $expectedTitles ),
			array_values( $this->getTitleDBKeys( $actualTitles ) ),
			'Title DBKey'
		);
	}

	public function testRun() {
		$title = $this->getTitleMock( 'Foo', 21 );
		$change = $this->getEntityChangeMock( 17 );
		$rc = $this->getRecentChangeMock();

		$changeLookup = $this->getEntityChangeLookupMock( [ $change ] );
		$changeFactory = $this->getEntityChangeFactory();

		$rcFactory = $this->getRCFactoryMock();

		$rcFactory->expects( $this->once() )
			->method( 'newRecentChange' )
			->with( $change, $title, [] )
			->willReturn( $rc );

		$rcDupeDetector = $this->getRCDupeDetectorMock();

		$rcDupeDetector->expects( $this->once() )
			->method( 'getRecentChangeId' )
			->with( $rc );

		$params = [
			'change' => $change->getId(),
			'pages' => [
				21 => [ 0, 'Foo' ]
			]
		];

		$dbWrite = $this->createMock( IDatabase::class );

		$dbWrite->expects( $this->once() )
			->method( 'startAtomic' );

		$dbWrite->expects( $this->once() )
			->method( 'endAtomic' );

		$job = new InjectRCRecordsJob(
			$this->getClientDomainDbMock( $dbWrite ),
			$changeLookup,
			$changeFactory,
			$rcFactory,
			$this->getTitleFactoryMock(),
			$params
		);

		$job->setRecentChangesFinder( $rcDupeDetector );

		$job->run();
	}

	/**
	 * @param Title[] $titles
	 *
	 * @return int[]
	 */
	private function getTitleIDs( array $titles ) {
		return array_map(
			function( Title $title ) {
				return $title->getArticleID();
			},
			$titles
		);
	}

	/**
	 * @param Title[] $titles
	 *
	 * @return string[]
	 */
	private function getTitleDBKeys( array $titles ) {
		return array_map(
			function( Title $title ) {
				return $title->getPrefixedDBkey();
			},
			$titles
		);
	}

}
