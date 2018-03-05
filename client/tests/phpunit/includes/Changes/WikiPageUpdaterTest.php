<?php

namespace Wikibase\Client\Tests\Changes;

use HTMLCacheUpdateJob;
use IJobSpecification;
use Job;
use JobQueueGroup;
use Liuggio\StatsdClient\Factory\StatsdDataFactoryInterface;
use PHPUnit_Framework_MockObject_MockObject;
use MediaWiki\MediaWikiServices;
use RefreshLinksJob;
use Title;
use Wikibase\Client\Changes\WikiPageUpdater;
use Wikibase\Client\RecentChanges\RecentChangeFactory;
use Wikibase\Client\RecentChanges\RecentChangesDuplicateDetector;
use Wikibase\EntityChange;
use Wikimedia\Rdbms\LBFactory;

/**
 * @covers Wikibase\Client\Changes\WikiPageUpdater
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseChange
 *
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class WikiPageUpdaterTest extends \MediaWikiTestCase {

	/**
	 * @return JobQueueGroup|PHPUnit_Framework_MockObject_MockObject
	 */
	private function getJobQueueGroupMock() {
		$jobQueueGroup = $this->getMockBuilder( JobQueueGroup::class )
			->disableOriginalConstructor()
			->getMock();

		return $jobQueueGroup;
	}

	/**
	 * @return RecentChangeFactory
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
	 * @return RecentChangesDuplicateDetector
	 */
	private function getRCDupeDetectorMock() {
		$rcDupeDetector = $this->getMockBuilder( RecentChangesDuplicateDetector::class )
			->disableOriginalConstructor()
			->getMock();

		return $rcDupeDetector;
	}

	/**
	 * @param string $text
	 * @param int $id
	 *
	 * @return Title
	 */
	private function getTitleMock( $text, $id ) {
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
			->method( 'getPrefixedDBkey' )
			->will( $this->returnValue( $text ) );

		$title->expects( $this->any() )
			->method( 'getDBkey' )
			->will( $this->returnValue( $text ) );

		$title->expects( $this->any() )
			->method( 'getText' )
			->will( $this->returnValue( $text ) );

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( 0 ) );

		$title->expects( $this->any() )
			->method( 'getNsText' )
			->will( $this->returnValue( '' ) );

		return $title;
	}

	/**
	 * @return LBFactory
	 */
	private function getLBFactoryMock() {
		$LBFactory = $this->getMockBuilder( LBFactory::class )
			->disableOriginalConstructor()
			->getMock();

		return $LBFactory;
	}

	/**
	 * @return StatsdDataFactoryInterface
	 */
	private function getStatsdDataFactoryMock( array $expectedStats ) {
		$stats = $this->getMock( StatsdDataFactoryInterface::class );

		$i = 0;
		foreach ( $expectedStats as $updateType => $delta ) {
			$stats->expects( $this->at( $i++ ) )
				->method( 'updateCount' )
				->with( 'wikibase.client.pageupdates.' . $updateType, $delta );
		}

		return $stats;
	}

	public function testPurgeWebCache() {
		$titleFoo = $this->getTitleMock( 'Foo', 21 );
		$titleBar = $this->getTitleMock( 'Bar', 22 );
		$titleCuzz = $this->getTitleMock( 'Cuzz', 23 );

		$jobQueueGroup = $this->getJobQueueGroupMock();

		$pages = [];
		$rootJobParams = [];
		$jobQueueGroup->expects( $this->atLeastOnce() )
			->method( 'lazyPush' )
			->will( $this->returnCallback( function( array $jobs ) use ( &$pages, &$rootJobParams ) {
				/** @var Job $job */
				foreach ( $jobs as $job ) {
					$this->assertInstanceOf( HTMLCacheUpdateJob::class, $job );
					$params = $job->getParams();
					$this->assertArrayHasKey( 'pages', $params, '$params["pages"]' );
					$pages += $params['pages']; // addition uses keys, array_merge does not
					$rootJobParams = $job->getRootJobParams();
				}
			} ) );

		$updater = new WikiPageUpdater(
			$jobQueueGroup,
			$this->getRCFactoryMock(),
			MediaWikiServices::getInstance()->getDBLoadBalancerFactory(),
			$this->getRCDupeDetectorMock(),
			$this->getStatsdDataFactoryMock( [
				'WebCache.jobs' => 2, // 2 batches (batch size 2, 3 titles)
				'WebCache.titles' => 3,
			] )
		);
		$updater->setPurgeCacheBatchSize( 2 );

		$updater->purgeWebCache( [
			$titleFoo, $titleBar, $titleCuzz,
		], [
			'rootJobTimestamp' => '20202211060708',
			'rootJobSignature' => 'Kittens!',
		],
			'test~action',
			'uid:1'
		);

		$this->assertEquals( [ 21, 22, 23 ], array_keys( $pages ) );
		$this->assertEquals( [ 0, 'Foo' ], $pages[21], '$pages[21]' );
		$this->assertEquals( [ 0, 'Bar' ], $pages[22], '$pages[22]' );
		$this->assertEquals( [ 0, 'Cuzz' ], $pages[23], '$pages[23]' );

		$this->assertEquals(
			[
				'rootJobTimestamp' => '20202211060708',
				'rootJobSignature' => 'Kittens!',
			],
			$rootJobParams,
			'$rootJobParams'
		);
	}

	public function testScheduleRefreshLinks() {
		$titleFoo = $this->getTitleMock( 'Foo', 21 );
		$titleBar = $this->getTitleMock( 'Bar', 22 );
		$titleCuzz = $this->getTitleMock( 'Cuzz', 23 );

		$jobQueueGroup = $this->getJobQueueGroupMock();

		$pages = [];
		$rootJobParams = [];
		$jobQueueGroup->expects( $this->atLeastOnce() )
			->method( 'lazyPush' )
			->will( $this->returnCallback( function( IJobSpecification $job ) use ( &$pages, &$rootJobParams ) {
				$this->assertInstanceOf( RefreshLinksJob::class, $job );
				$title = $job->getTitle();

				$id = $title->getArticleID();
				$pages[$id] = [ $title->getNamespace(), $title->getDBkey() ];
				$rootJobParams = $job->getRootJobParams();
			} ) );

		$updater = new WikiPageUpdater(
			$jobQueueGroup,
			$this->getRCFactoryMock(),
			MediaWikiServices::getInstance()->getDBLoadBalancerFactory(),
			$this->getRCDupeDetectorMock(),
			$this->getStatsdDataFactoryMock( [
				'RefreshLinks.jobs' => 3, // no batching
				'RefreshLinks.titles' => 3,
			] )
		);

		$updater->scheduleRefreshLinks(
			[ $titleFoo, $titleBar, $titleCuzz ],
			[
				'rootJobTimestamp' => '20202211060708',
				'rootJobSignature' => 'Kittens!',
			],
			'test~action',
			'uid:1'
		);

		$this->assertEquals( [ 21, 22, 23 ], array_keys( $pages ) );
		$this->assertEquals( [ 0, 'Foo' ], $pages[21], '$pages[21]' );
		$this->assertEquals( [ 0, 'Bar' ], $pages[22], '$pages[22]' );
		$this->assertEquals( [ 0, 'Cuzz' ], $pages[23], '$pages[23]' );

		$this->assertEquals(
			[
				'rootJobTimestamp' => '20202211060708',
				'rootJobSignature' => 'Kittens!',
			],
			$rootJobParams,
			'$rootJobParams'
		);
	}

	public function testInjectRCRecords() {
		$titleFoo = $this->getTitleMock( 'Foo', 21 );
		$titleBar = $this->getTitleMock( 'Bar', 22 );
		$titleCuzz = $this->getTitleMock( 'Cuzz', 23 );

		$jobQueueGroup = $this->getJobQueueGroupMock();

		$pages = [];
		$rootJobParams = [];
		$jobQueueGroup->expects( $this->atLeastOnce() )
			->method( 'lazyPush' )
			->will( $this->returnCallback(
				function( array $jobs ) use ( &$pages, &$rootJobParams ) {
					/** @var Job $job */
					foreach ( $jobs as $job ) {
						$this->assertSame( 'wikibase-InjectRCRecords', $job->getType() );
						$params = $job->getParams();
						$this->assertArrayHasKey( 'pages', $params, '$params["pages"]' );
						$pages += $params['pages']; // addition uses keys, array_merge does not
						$rootJobParams = $job->getRootJobParams();
					}
				}
			) );

		$updater = new WikiPageUpdater(
			$jobQueueGroup,
			$this->getRCFactoryMock(),
			$this->getLBFactoryMock(),
			$this->getRCDupeDetectorMock(),
			$this->getStatsdDataFactoryMock( [
				// FIXME: Because of the hot fix for T177707 we expect only the first batch.
				'InjectRCRecords.jobs' => 1,
				'InjectRCRecords.titles' => 2,
				'InjectRCRecords.discardedTitles' => 1,
			] )
		);
		$updater->setRecentChangesBatchSize( 2 );

		$updater->injectRCRecords(
			[ $titleFoo, $titleBar, $titleCuzz, ],
			new EntityChange(),
			[ 'rootJobTimestamp' => '20202211060708', 'rootJobSignature' => 'Kittens!', ]
		);

		// FIXME: Because of the hot fix for T177707 we expect only the first batch.
		$this->assertSame( [
			21 => [ 0, 'Foo' ],
			22 => [ 0, 'Bar' ],
		], $pages );

		$this->assertEquals(
			[
				'rootJobTimestamp' => '20202211060708',
				'rootJobSignature' => 'Kittens!',
			],
			$rootJobParams,
			'$rootJobParams'
		);
	}

}
