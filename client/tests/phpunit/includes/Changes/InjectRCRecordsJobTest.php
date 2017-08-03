<?php

namespace Wikibase\Client\Tests\Changes;

use PHPUnit_Framework_MockObject_MockObject;
use RecentChange;
use Title;
use Wikibase\Client\Changes\InjectRCRecordsJob;
use Wikibase\Client\RecentChanges\RecentChangeFactory;
use Wikibase\Client\RecentChanges\RecentChangesDuplicateDetector;
use Wikibase\Client\Store\TitleFactory;
use Wikibase\EntityChange;
use Wikibase\Lib\Changes\EntityChangeFactory;
use Wikibase\Lib\Store\Sql\EntityChangeLookup;
use Wikibase\Lib\Tests\Changes\EntityChangeFactoryTest;
use Wikimedia\Rdbms\LBFactory;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers Wikibase\Client\Changes\InjectRCRecordsJob
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseChange
 *
 * @group Database
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class InjectRCRecordsJobTest extends \MediaWikiTestCase {

	/**
	 * @return RecentChangeFactory|PHPUnit_Framework_MockObject_MockObject
	 */
	private function getRCFactoryMock() {
		$rcFactory = $this->getMockBuilder( RecentChangeFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$rcFactory->expects( $this->any() )
			->method( 'prepareChangeAttributes' )
			->will( $this->returnValue( [] ) );

		return $rcFactory;
	}

	/**
	 * @param EntityChange $change
	 *
	 * @return PHPUnit_Framework_MockObject_MockObject|EntityChangeLookup
	 */
	private function getEntityChangeLookupMock( EntityChange $change ) {
		$changeLookup = $this->getMockBuilder( EntityChangeLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$changeLookup->expects( $this->any() )
			->method( 'loadByChangeIds' )
			->with( [ $change->getId() ] )
			->will( $this->returnValue( [ $change ] ) );

		return $changeLookup;
	}

	/**
	 * @return RecentChangesDuplicateDetector|PHPUnit_Framework_MockObject_MockObject
	 */
	private function getRCDupeDetectorMock() {
		$rcDupeDetector = $this->getMockBuilder( RecentChangesDuplicateDetector::class )
			->disableOriginalConstructor()
			->getMock();

		return $rcDupeDetector;
	}

	/**
	 * @return TitleFactory|PHPUnit_Framework_MockObject_MockObject
	 */
	private function getTitleFactoryMock() {
		$titleFactory = $this->getMock( TitleFactory::class );

		$id = 200;
		$titleFactory->expects( $this->any() )
			->method( 'makeTitle' )
			->will( $this->returnCallback( function( $ns, $text ) use ( &$id ) {
				return $this->getTitleMock( $text, $id++ );
			} ) );

		return $titleFactory;
	}

	/**
	 * @param string $text
	 * @param int $id
	 *
	 * @return Title|PHPUnit_Framework_MockObject_MockObject
	 */
	private function getTitleMock( $text, $id = 23 ) {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getArticleID' )
			->will( $this->returnValue( $id ) );

		$title->expects( $this->any() )
			->method( 'exists' )
			->will( $this->returnValue( true ) );

		$title->expects( $this->any() )
			->method( 'getDBkey' )
			->will( $this->returnValue( $text ) );

		$title->expects( $this->any() )
			->method( 'getPrefixedDBkey' )
			->will( $this->returnValue( $text ) );

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( 0 ) );

		return $title;
	}

	/**
	 * @param int $id
	 * @param array $fields
	 *
	 * @return PHPUnit_Framework_MockObject_MockObject|EntityChange
	 */
	private function getEntityChangeMock( $id = 77, array $fields = [] ) {
		$change = $this->getMockBuilder( EntityChange::class )
			->disableOriginalConstructor()
			->getMock();

		$change->expects( $this->any() )
			->method( 'getId' )
			->will( $this->returnValue( $id ) );

		$change->expects( $this->any() )
			->method( 'getFields' )
			->will( $this->returnValue( $fields ) );

		return $change;
	}

	/**
	 * @return RecentChange|PHPUnit_Framework_MockObject_MockObject
	 */
	private function getRecentChangeMock() {
		$change = $this->getMockBuilder( RecentChange::class )
			->disableOriginalConstructor()
			->getMock();

		return $change;
	}

	/**
	 * @return LBFactory|PHPUnit_Framework_MockObject_MockObject
	 */
	private function getLBFactoryMock() {
		$LBFactory = $this->getMockBuilder( LBFactory::class )
			->disableOriginalConstructor()
			->getMock();

		return $LBFactory;
	}

	public function provideConstruction() {
		return [
			'mock change' => [
				[ $this->getTitleMock( 'Foo', 21 ) ],
				$change = $this->getEntityChangeMock( 17, [ 'Test' => 'Kitten' ] ),
			],
			'simple change' => [
				[ $this->getTitleMock( 'Foo', 21 ) ],
				$change = new EntityChange( [
					'id' => 17,

				] ),
			],
		];
	}

	/**
	 * @dataProvider provideConstruction()
	 * @param Title[] $titles
	 * @param EntityChange $change
	 */
	public function testConstruction( array $titles, EntityChange $change ) {
		$spec = InjectRCRecordsJob::makeJobSpecification( $titles, $change );
		$changeLookup = $this->getEntityChangeLookupMock( $change );

		$rcFactory = $this->getRCFactoryMock();

		$job = new InjectRCRecordsJob(
			$this->getLBFactoryMock(),
			$changeLookup,
			$rcFactory,
			$spec->getParams()
		);

		$actualChange = TestingAccessWrapper::newFromObject( $job )->getChange();

		$this->assertEquals( $change->getId(), $actualChange->getId(), 'Change ID' );
		$this->assertEquals( $change->getFields(), $actualChange->getFields(), 'Change Fields' );

		$actualTitles = TestingAccessWrapper::newFromObject( $job )->getTitles();

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

	public function testRun() {
		$title = $this->getTitleMock( 'Foo', 21 );
		$change = $this->getEntityChangeMock( 17 );
		$rc = $this->getRecentChangeMock();

		$changeLookup = $this->getEntityChangeLookupMock( $change );

		$rcFactory = $this->getRCFactoryMock();

		$rcFactory->expects( $this->once() )
			->method( 'newRecentChange' )
			->with( $change, $title, [] )
			->will( $this->returnValue( $rc ) );

		$rcDupeDetector = $this->getRCDupeDetectorMock();

		$rcDupeDetector->expects( $this->once() )
			->method( 'changeExists' )
			->with( $rc );

		$params = [
			'change' => $change->getId(),
			'pages' => [
				21 => [ 0, 'Foo' ]
			]
		];

		$job = new InjectRCRecordsJob(
			$this->getLBFactoryMock(),
			$changeLookup,
			$rcFactory,
			$params
		);

		$job->setTitleFactory( $this->getTitleFactoryMock() );
		$job->setRecentChangesDuplicateDetector( $rcDupeDetector );

		$job->run();
	}

	public function testRun_batch() {
		$change = $this->getEntityChangeMock();
		$rc = $this->getRecentChangeMock();
		$changeLookup = $this->getEntityChangeLookupMock( $change );

		$rcFactory = $this->getRCFactoryMock();

		$rcFactory->expects( $this->any() )
			->method( 'newRecentChange' )
			->will( $this->returnValue( $rc ) );

		$lbFactory = $this->getLBFactoryMock();
		$lbFactory->expects( $this->exactly( 2 ) )
			->method( 'commitAndWaitForReplication' );

		$params = [
			'change' => $change->getId(),
			'pages' => [
				21 => [ 0, 'Foo' ],
				22 => [ 0, 'Bar' ],
				23 => [ 0, 'Cuzz' ],
			]
		];

		$job = new InjectRCRecordsJob(
			$lbFactory,
			$changeLookup,
			$rcFactory,
			$params
		);

		$job->setTitleFactory( $this->getTitleFactoryMock() );
		$job->setDbBatchSize( 2 );

		$job->run();
	}

	/**
	 * @param Title[] $titles
	 * @return int[]
	 */
	private function getTitleIDs( array $titles ) {
		return array_map( function( Title $t ) { return $t->getArticleId(); }, $titles );
	}

	/**
	 * @param Title[] $titles
	 * @return string[]
	 */
	private function getTitleDBKeys( array $titles ) {
		return array_map( function( Title $t ) { return $t->getPrefixedDBkey(); }, $titles );
	}

}
