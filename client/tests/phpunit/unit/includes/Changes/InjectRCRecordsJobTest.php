<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\Changes;

use MediaWiki\RecentChanges\RecentChange;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleFactory;
use PHPUnit\Framework\TestCase;
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
use Wikibase\Lib\Store\Sql\EntityChangeLookup;
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

	private function getRCFactoryMock(): RecentChangeFactory {
		$rcFactory = $this->createMock( RecentChangeFactory::class );

		$rcFactory->method( 'prepareChangeAttributes' )
			->willReturn( [] );

		return $rcFactory;
	}

	/**
	 * @param EntityChange[] $knownChanges
	 */
	private function getEntityChangeLookupMock( array $knownChanges = [] ): EntityChangeLookup {
		$changeLookup = $this->createMock( EntityChangeLookup::class );

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

	private static function getEntityChangeFactory(): EntityChangeFactory {
		return new EntityChangeFactory(
			new EntityDiffer(),
			new BasicEntityIdParser(),
			[ Item::ENTITY_TYPE => ItemChange::class ]
		);
	}

	private function getRCDupeDetectorMock(): RecentChangesFinder {
		$rcDupeDetector = $this->createMock( RecentChangesFinder::class );

		return $rcDupeDetector;
	}

	private function getTitleFactoryMock(): TitleFactory {
		$titleFactory = $this->createMock( TitleFactory::class );

		$id = 200;
		$titleFactory->method( 'makeTitle' )
			->willReturnCallback( function ( $ns, $text ) use ( &$id ) {
				return $this->getTitleMock( $text, $id++ );
			} );

		return $titleFactory;
	}

	private function getTitleMock( string $text, int $id = 23 ): Title {
		$title = $this->createMock( Title::class );

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

	private function getEntityChangeMock( int $id = 77, array $fields = [] ): EntityChange {
		$info = $fields['info'] ?? [];

		$change = $this->createMock( EntityChange::class );

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

	private function getRecentChangeMock(): RecentChange {
		$change = $this->createMock( RecentChange::class );

		return $change;
	}

	public static function provideMakeJobSpecification(): array {
		$changeFactory = self::getEntityChangeFactory();
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
				fn ( self $self ) => $self->getEntityChangeMock(
					17,
					[
						'id' => 17,
						'object_id' => 'Q7',
						'type' => 'wikibase-item~change',
						'Test' => 'Kitten',
						'info' => [],
					]
				),
			],
			'simple change with ID' => [
				fn () => $changeFactory->newForEntity(
					'change',
					new ItemId( 'Q7' ),
					[ 'id' => 7 ]
				),
			],
			'simple change with data but no ID' => [
				fn () => $changeFactory->newForEntity(
					'change',
					$itemId,
					[
						'type' => 'wikibase-item~change',
						'object_id' => $itemId->getSerialization(),
						'info' => [
							'compactDiff' => $diff,
							'stuff' => 'Fun Stuff',
							'metadata' => [
								'foo' => 'Kitten',
							],
						],
					]
				),
			],
			'composite change without ID' => [
				fn () => $changeFactory->newForEntity(
					'change',
					$itemId,
					[
						'type' => 'wikibase-item~change',
						'object_id' => $itemId->getSerialization(),
						'info' => [
							'compactDiff' => $diff,
							'change-ids' => [ 101, 102 ],
							'changes' => [ $child1, $child2 ],
						],
					]
				),
				[ $child1, $child2 ],
			],
		];
	}

	/**
	 * @dataProvider provideMakeJobSpecification
	 */
	public function testMakeJobSpecification( callable $expectedChangeFactory, array $knownChanges = [] ): void {
		$titles = [ $this->getTitleMock( 'Foo', 21 ) ];
		$expectedChange = $expectedChangeFactory( $this );
		$spec = InjectRCRecordsJob::makeJobSpecification( $titles, $expectedChange );

		$changeLookup = $this->getEntityChangeLookupMock( $knownChanges );
		$changeFactory = self::getEntityChangeFactory();
		$rcFactory = $this->getRCFactoryMock();

		/** @var InjectRCRecordsJob $job */
		$job = TestingAccessWrapper::newFromObject( new InjectRCRecordsJob(
			$changeLookup,
			$changeFactory,
			$rcFactory,
			$this->getTitleFactoryMock(),
			$spec->getParams()
		) );

		$actualChange = $job->getChange();

		$this->assertEquals( $expectedChange->getId(), $actualChange->getId(), 'Change ID' );
		$this->assertEquals( $expectedChange->getFields(), $actualChange->getFields(), 'Change Fields' );

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

	public function testMakeJobSpecification_rootJobParams(): void {
		$titles = [ $this->getTitleMock( 'Foo', 21 ) ];
		$change = self::getEntityChangeFactory()->newForEntity(
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

	/**
	 * @param Title[] $titles
	 *
	 * @return array[]
	 */
	private function getPageSpecData( array $titles ): array {
		$pages = [];

		foreach ( $titles as $title ) {
			$id = $title->getArticleID();
			$pages[$id] = [ $title->getNamespace(), $title->getDBkey() ];
		}

		return $pages;
	}

	public function testConstruction(): void {
		$expectedChange = $this->getEntityChangeMock(
			17,
			[
				'id' => 17,
				'object_id' => 'Q7',
				'type' => 'wikibase-item~change',
				'Test' => 'Kitten',
				'info' => [],
			]
		);
		$expectedTitles = [ $this->getTitleMock( 'Foo', 21 ) ];
		$params = [
			'change' => $expectedChange->getFields(),
			'pages' => $this->getPageSpecData( $expectedTitles ),
		];
		$changeLookup = $this->getEntityChangeLookupMock( [ $expectedChange ] );
		$changeFactory = self::getEntityChangeFactory();
		$rcFactory = $this->getRCFactoryMock();

		/** @var InjectRCRecordsJob $job */
		$job = TestingAccessWrapper::newFromObject( new InjectRCRecordsJob(
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

	public function testRun(): void {
		$change = $this->getEntityChangeMock(
			17,
			[
				'object_id' => 'Q7',
				'type' => 'wikibase-item~change',
				'time' => time(),
			]
		);
		$rc = $this->getRecentChangeMock();

		$changeLookup = $this->getEntityChangeLookupMock( [ $change ] );
		$changeFactory = self::getEntityChangeFactory();

		$rcFactory = $this->getRCFactoryMock();

		$rcFactory->expects( $this->once() )
			->method( 'newRecentChange' )
			->willReturn( $rc );

		$rcDupeDetector = $this->getRCDupeDetectorMock();

		$rcDupeDetector->expects( $this->once() )
			->method( 'getRecentChangeId' )
			->with( $rc );

		$params = [
			'change' => $change->getFields(),
			'pages' => [
				21 => [ 0, 'Foo' ],
			],
		];

		$job = new InjectRCRecordsJob(
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
	private function getTitleIDs( array $titles ): array {
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
	private function getTitleDBKeys( array $titles ): array {
		return array_map(
			function( Title $title ) {
				return $title->getPrefixedDBkey();
			},
			$titles
		);
	}

}
