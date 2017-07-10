<?php

namespace Wikibase\Client\Tests\Changes;

use HTMLCacheUpdateJob;
use Job;
use JobQueueGroup;
use MediaWiki\MediaWikiServices;
use RecentChange;
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
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class WikiPageUpdaterTest extends \MediaWikiTestCase {

	/**
	 * @return JobQueueGroup
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
	 *
	 * @return Title
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
	 * @return EntityChange
	 */
	private function getEntityChangeMock() {
		$change = $this->getMockBuilder( EntityChange::class )
			->disableOriginalConstructor()
			->getMock();

		return $change;
	}

	/**
	 * @return RecentChange
	 */
	private function getRecentChangeMock() {
		$change = $this->getMockBuilder( RecentChange::class )
			->disableOriginalConstructor()
			->getMock();

		return $change;
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

	public function testPurgeWebCache() {
		$titleFoo = $this->getTitleMock( 'Foo', 21 );
		$titleBar = $this->getTitleMock( 'Bar', 22 );
		$titleCuzz = $this->getTitleMock( 'Cuzz', 23 );

		$jobQueueGroup = $this->getJobQueueGroupMock();

		$pages = [];
		$jobQueueGroup->expects( $this->atLeastOnce() )
			->method( 'lazyPush' )
			->will( $this->returnCallback( function( array $jobs ) use ( &$pages ) {
				/** @var Job $job */
				foreach ( $jobs as $job ) {
					$this->assertInstanceOf( HTMLCacheUpdateJob::class, $job );
					$params = $job->getParams();
					$this->assertArrayHasKey( 'pages', $params, '$params["pages"]' );
					$pages += $params['pages']; // addition uses keys, array_merge does not
				}
			} ) );

		$updater = new WikiPageUpdater(
			$jobQueueGroup,
			$this->getRCFactoryMock(),
			MediaWikiServices::getInstance()->getDBLoadBalancerFactory(),
			$this->getRCDupeDetectorMock()
		);

		$updater->purgeWebCache( [
			$titleFoo, $titleBar, $titleCuzz,
		] );

		$this->assertEquals( [ 21, 22, 23 ], array_keys( $pages ) );
		$this->assertEquals( [ 0, 'Foo' ], $pages[21], '$pages[21]' );
		$this->assertEquals( [ 0, 'Bar' ], $pages[22], '$pages[22]' );
		$this->assertEquals( [ 0, 'Cuzz' ], $pages[23], '$pages[23]' );
	}

	public function testScheduleRefreshLinks() {
		$titleFoo = $this->getTitleMock( 'Foo', 21 );
		$titleBar = $this->getTitleMock( 'Bar', 22 );
		$titleCuzz = $this->getTitleMock( 'Cuzz', 23 );

		$jobQueueGroup = $this->getJobQueueGroupMock();

		$pages = [];
		$jobQueueGroup->expects( $this->atLeastOnce() )
			->method( 'lazyPush' )
			->will( $this->returnCallback( function( array $jobs ) use ( &$pages ) {
				/** @var Job $job */
				foreach ( $jobs as $job ) {
					$this->assertInstanceOf( RefreshLinksJob::class, $job );
					$params = $job->getParams();
					$this->assertArrayHasKey( 'pages', $params, '$params["pages"]' );
					$pages += $params['pages']; // addition uses keys, array_merge does not
				}
			} ) );

		$updater = new WikiPageUpdater(
			$jobQueueGroup,
			$this->getRCFactoryMock(),
			MediaWikiServices::getInstance()->getDBLoadBalancerFactory(),
			$this->getRCDupeDetectorMock()
		);

		$updater->scheduleRefreshLinks( [
			$titleFoo, $titleBar, $titleCuzz,
		] );

		$this->assertEquals( [ 21, 22, 23 ], array_keys( $pages ) );
		$this->assertEquals( [ 0, 'Foo' ], $pages[21], '$pages[21]' );
		$this->assertEquals( [ 0, 'Bar' ], $pages[22], '$pages[22]' );
		$this->assertEquals( [ 0, 'Cuzz' ], $pages[23], '$pages[23]' );
	}

	public function testInjectRCRecords() {
		$title = $this->getTitleMock( 'Foo' );
		$change = $this->getEntityChangeMock();
		$rc = $this->getRecentChangeMock();

		$rcFactory = $this->getRCFactoryMock();

		$rcFactory->expects( $this->once() )
			->method( 'newRecentChange' )
			->with( $change, $title, [] )
			->will( $this->returnValue( $rc ) );

		$rcDupeDetector = $this->getRCDupeDetectorMock();

		$rcDupeDetector->expects( $this->once() )
			->method( 'changeExists' )
			->with( $rc );

		$updater = new WikiPageUpdater(
			$this->getJobQueueGroupMock(),
			$rcFactory,
			$this->getLBFactoryMock(),
			$rcDupeDetector
		);

		$updater->injectRCRecords( [
			$title,
		], $change );
	}

	public function testInjectRCRecords_batch() {
		$titleFoo = $this->getTitleMock( 'Foo' );
		$titleBar = $this->getTitleMock( 'Bar' );
		$titleCuzz = $this->getTitleMock( 'Cuzz' );

		$change = $this->getEntityChangeMock();
		$rc = $this->getRecentChangeMock();

		$rcFactory = $this->getRCFactoryMock();

		$rcFactory->expects( $this->any() )
			->method( 'newRecentChange' )
			->will( $this->returnValue( $rc ) );

		$rcDupeDetector = $this->getRCDupeDetectorMock();

		$lbFactory = $this->getLBFactoryMock();
		$lbFactory->expects( $this->exactly( 2 ) )
			->method( 'commitAndWaitForReplication' );

		$updater = new WikiPageUpdater(
			$this->getJobQueueGroupMock(),
			$rcFactory,
			$lbFactory,
			$rcDupeDetector
		);

		$updater->setDbBatchSize( 2 );

		$updater->injectRCRecords(
			[ $titleFoo, $titleBar, $titleCuzz ],
			$change
		);
	}

}
